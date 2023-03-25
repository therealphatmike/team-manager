# Final Touches - Data Validation and Custom Json Resource Definitions
At this point you should have a working API that can Create, Read, Update, and Delete our Team, Driver, and Car models.
This is still a very basic API. Aside from the brief touch on what a policy is, we haven't talked about anything user-y like authentication and authorization. We haven't discussed types of relationships between data other than one-to-many.
So there's still a lot we could discuss as a basic level of what a production app would need. Further, too, we haven't touched on any
of the many wonderful abstrctions Laravel provides for queues, caching, object storage, or anything to do with UIs - Laravel takes an SSR approach to UIs if you want to build a "modern monolithic" application. It's not necessary, but there are a lot of scenarios where it really is overkill to take a microservice approach.

So, the last two items that I want to touch on besides all of that are 
1. Data Validation -- how can we make sure the user is providing the data we expect and return meaningful errors?
1. Resources -- If you're following along in the branches, you'll probably have noticed that I have implemented those as we went, but never really talked about them. We'll do that now.

## Data Validation
A necessity for any API that is persisting it's data to a Database or other data store is to validate that the clients are sending you the data that you expect to receive. This means checking that the types are right, but also that required fields are present or that tightly constrained fields meet the constraint requirements that you need.

Laravel provides a really intuitive and easy way to validate data coming into your API. When we create our models, you'll notice in the output of the artisan command was 2 lines saying that it successfully created Requests. These classes are what we use to validate the incoming data for mutating requests. I'll do one example with code here for each Store and Update requests and let you handle the rest as practice. I am intentionally going to do the driver model as it has a special validation rule that I want to highlight.

If we open up the DrivverController and look at the store method, we will see one of the arguments is of type StoreDriverRequest. Let's open that file.

Right now it probably looks like:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDriverRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }
}
```

Recall that we changed `return false;` to `return true;` on line 14 so that our requests would go through. The rules function returns an associative array that we will use to define the rules for each data attribute that we are expecting in our API endpoint.

As we can recall, our driver model has the following data attributes:
- string first_name
- string last_name
- string email
- uuid team_id

Let's say that all of these are required - we need to know a drivers full name and email, as well as knowing what team they are associated with. So let's set this up. Make your rules function look like:

```php
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'team_id' => 'required|uuid',
        ];
    }
```

This is really straightforward once it's broken down. For a given key, we would expect the value associated with it to match the rules outlined on the right hand oof each key-value pair in our associative array. The values in our rule KV pairs can string multiple validation rules together with the `|` pipe symbol. All 4 our our fields are required, so if any are missing the request will fail with the HTTP status code 422 "Unprocessable Content" meaning that the API couldn't process the request because the client sent malformed content in the request. Our `first_name` and `last_name` fields only need to be of string types, we don't need to validate those any further. For email, Laravel provides a handy email validator, and same goes for `team_id` laravel provides a rule that runs a regex that will match UUIDs.

This is great, now, if we make a request and leave the team id off, we will get the following response

```json
{
  "message": "The team id field is required.",
  "errors": {
    "team_id": [
      "The team id field is required."
    ]
  }
}
```

This is fantastic, if we were building a UI around this, and somehow our UI validation let something through the cracks, our API would be able to give us meaningful information about what went wrong. Laravel's validation will check everything for every possible error. So if we leave off everything to the Store endpoint, we would get a response like:

```json
{
  "message": "The first name field is required. (and 3 more errors)",
  "errors": {
    "first_name": [
      "The first name field is required."
    ],
    "last_name": [
      "The last name field is required."
    ],
    "email": [
      "The email field is required."
    ],
    "team_id": [
      "The team id field is required."
    ]
  }
}
```

So it's telling us all of our errors in one fell swoop, now our clients don't have to play error whackamole, we can give them all the feedback they need to fix the malformed request and resubmit it. There's still one issue -- team id according to our current rules only has to conform to a UUID regex, which doesn't guarantee that it's a valid team id in our database. And yes, Laravel makes this super easy, too. Let's modify the `team_id` rule to look like:

```php
'team_id' => 'required|uuid|exists:teams,id',
```

So this exists rule takes an argument after the colon. That argument is the name of a table. So we're telling Laravel to make sure the `team_id` that gets submitted is a valid id in the team table. So now, if we submit an invalid team id, we would get this error:

```json
{
  "message": "The selected team id is invalid.",
  "errors": {
    "team_id": [
      "The selected team id is invalid."
    ]
  }
}
```

Now we have a high confidence that we can't get improper data into our database, so we can give a higher confidence of data integrity to our users.

## Custom Resources
Ok lastly, we have JSON resources. These are how we can serialize our model classes to JSON before emitting them from our API. What I am going to go over here isn't necessary, as the default implementation will load relationships for you and handle most of your use cases, but if you want to customize what the user sees from your models, such as leaving off timestamps, or obfuscating other data, this is the place to do it.

Currently, we haven't implemented those, so yours probably look something like:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
```

A simple replacement for just driver data would look like:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'team_id' => $this->team_id,
        ];
    }
}
```

So we replace `parent::to_array($request)` with a custom associative array where we map attributes from `$this` (this being our model class) into fields that we want. An example of a tranformation you could make would be combing names and returning just `fullName` to the client:

```php
return [
            'id' => $this->id,
            'fullName' => $this->firstName . ' ' . $this->lastName,
            'email' => $this->email,
            'team_id' => $this->team_id,
        ];
```

In php `.` is the concatenation operator. Ok, but this doesn't load our related data, so lets add the following two lines:

```php
            'team' => TeamResource::make($this->whenLoaded('team')),
            'car' => CarResource::make($this->whenLoaded('car')),
```

So only when we have loaded our team or car relationships for a driver, will those fields show up in our request object. Again, all of this handled by the default implementation of a Resource that Laravel gie you for free, but this is just some peeking beneath the hood to see ways that you can manipulate the default offering that Laravel provides.

And that's that. We'll call it there for this tutorial. I hope it was helpful!

## Thanks
Thanks for following along. If you run into any issues or have any questions, feel free to message me on the MTSU ACM discord.
