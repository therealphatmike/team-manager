# Step 1 - The Team Model
## Team

## Artisan
In this step, our primary concern is setting up the team model, which is sort of our top level parent model that the API builds from.

Laravel provides a rich CLI that will allow us to bootstrap some of the basic things we will need to get up an running when adding a new Model to an API. This CLI is called Artisan and laravel actually provides a mechanism to write our own artisan commands (we won't be doing this in this workshop, though).

## Let's Start
The first thing we want to do is run the following command in a terminal `./vendor/bin/sail shell`. This command gets us a shell inside our running docker container and gives us access to an env that has all of our dependencies installed. Under the hood this is basically executing `docker exec -it <docker-image-name> -- bash`.

Once we have our shell, we can bootstrap our first Laravel model by running `php artisan make:model Team --all`. This command will generate the following
1. our model class
1. a datababase migration file
1. a database factory
1. database seeder
1. a policy to gate access to certain functionality
1. Store and Update request objects for validation
1. a Json resource class that is used to serialize our model instance into JSON for emission by the API
1. and a Controller class which provides the blueprint for the methods we will build around our Models.

### What we have
If you take a peep at our model class now (located in `app/Models/Team.php`), you'll see something like:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;
}
```

Which...admittedly...seems relatively useless. And if you look at our migration file (located in `database/migrations/<timestamp>_create_teams_table.php`) you'll see something like:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
```

Which may also seem a little odd. Lets dig in to how these two files give us 85% of what we need to get things up and running.

---

## Model And Migration
Models and migrations go hand in hand in really any web framework. Your models are instantiable classes that represent the data that your
app allows users to manipualte. Migrations, on the other hand, are how we tell the database what it should look like and what types to
expect from us.

So, with that in mind, you may already see the writing on the walls that what we're about to do is pretty much telling two different
entities in our code some information about the data we want to represent. Let's start with the model since it's a little easier.

### Model
Ok so in our `app/Models/Team.php` file, lets setup our model class with the data we want. If we pull up the README.md, you'll see the data attribute's we're going for. What you're probably expecting is that we'll set them up a little like:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    private string $name;
    private string $website;

    // so on and so forth
}
```

However, laravel kinda makes even this part easy. Our models by default will expect an array attribute called `$fillable` that outlines the pieces of information in our Model that users can....fill in. Which would give us something like:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'website',
    ];
}
```

We use protected so that the magical things that Larvel does behind the scenes can access our fillable attributes and generate the appropriate functions and accesses and that's all we have to do -- with one caveat -- ids. Personally, I prefer UUIDs over auto-incrementing integer primary keys as UUIDs are not succesptible to enumeration attacks, and Laravel makes UUIDs super easy. We use what is called a trait, and Laravel does the rest. So, all we have to do to get auto-generated UUID PKs is add the `HasUuids` trait to our model:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'website',
    ];
}
```

So we now have our basic Team mdoel defined. As we add other entities like Driver and Car, we will have to come back to this model to tell Laravel about the relationships it has.

### Migration
Now that we have our Model defined for PHP so that our API code can interact with it, we need to tell the database what to expect. This is done via migrations. A migration generally contains two functions 

1. up
2. down

Migrating up means you're applying changes to a database and migrating down means you're reversing those changes. It's important to note that we implement down as a way to rollback changes. If you are looking to remove a set of columns or tables from a previous migration all together, you need to do that in an up method of a new migration.

Ok, let's look at our data model and figure out how to tell the db what we want. Per our diagram we have 
```
TEAM{
    string name
    string website
}
```

The only thing we need to account for now is the primary key for the table, and translating the above info into a laravel migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->string('name');
            $table->string('website');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
```

Stepwise we have:

1. change `$table->id()->primary();` to `$table->uuid('id')->primary()->unique();` which basically just tells the DB that we're gonna use a UUID type as our primary key and we want the DB to enforce uniqueness on these keys.
1. add the name and website attributes by calling the `$table->string()` function, and passing the column names as strings.

Pretty much at this point we're done. I do want to point out a couple of things:
1. `$table->timestamps();` will automatically create a `created_at` and `updated_at` column and store the timestamps for those columns automatically for you. 
1. in the `down()` method`Schema::dropIfExists('teams');` will look for a table called teams and drop it if one exists.

Ok, so how do we apply these? Easy. There's an artisan command for that:
`php artisan migrate` will look at your migration files and automatically apply any that have not been run. So, let's do that now.

---

## Using our Model
We've got a model and our database setup. But that doesn't help us create, access, or modify any of the data. So how do we make the model usable? There's two 3 steps:

1. create a controllet method to do what we want
2. create a route in our api file to open up access to the controller
3. allow actions based on policies.

### Controller
In Laravel, controller control actions on models. That is to say, controllers implement our `business logic`. We got a controller for free when we created our Team model. Let's see what that looked like. If you open up `app/Http/Controllers/TeamController.php` you should see something like:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Team;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeamRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Team $team)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTeamRequest $request, Team $team)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        //
    }
}
```

This is sick! Ok, we have the following methods:
1. `index()` - this is a list function. It is used for searching or getting all of 1 Model. We d oa trivial implementation in this workshop, but in the future we could talk about implementing a paginated fuzzy search here.
1. `create()` - We are actually going to delete this method. Laravel, as mentioned previously is a fullstack framework, so this function is actually used to render the form that would allow a user to create a Model and submit it for storage in the DB.
1. `store()` - This function will store a new model in the database.
1. `show()` - We will also be deleting this method, but its purpose goes back to the fullstack-nature of Laravel. It's purpose is to render the view that shows a particular Model.
1. `edit()` - again, we will be deleting this. It's purpose is to render the edit form.
1. `update()` - This function will update an existing model (record) in the DB.
1. `destroy()`- The function allows a user to delete a model.

After the methods we're deleting, we're left with
1. index
1. store
1. show
1. update
1. delete

If you're thinking this sounds very much like CRUD (Create, Read, Update, Delete) you'd be correct and that's the point! We just separate show and index since they are, generally speaking, separate concerns.

So let's implement these:


#### store()
Our store function allows us to create models, so while it's not first in our Controller, we're going to implement it first so we can test the other methods as we make them.

```php
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeamRequest $request)
    {
        Log::info("TeamController->store()");

        $team = Team::create([
            'name' => $request->name,
            'website' => $request->website,
        ]);

        return new TeamResource($team);
    }
```

We start by Logging an informational message so we know what's happening if we encounter a bug and need to read our logs. Then we call a method provided to us under the hood on our Team model called `create()` that allows us to fill in the fillable attributes of our model and stores it in the DB for us. Next we return a singular `TeamResource`. This will store the data submitted into the db and return the newly created model as proof that it did what we asked. This is standard REST practice.

#### index()
```php
    public function index()
    {
        Log::info("TeamController->index()");
        return TeamResource::collection(Team::all());
    }
```

Again, this is a trivial implementation. In reality, you probably want to support fuzzy searching and or pagination. Those are going to be separate conversations.

Essentially all we're doing here is Logging an informational message so we know what's happening if we encounter a bug and need to read our logs. Then we return a collection of `TeamResource`. Remember that Resources are classes to help us serialize our models to JSON before emitting them from the API. What are we passing to the `TeamResource::collection()` function? `Team::all()`. Team is the Model class that we just made, and if you don't recall writing a static function called `all()`, you're correct. This is provided by Laravel, and all (lol) it does is a `SELECT * FROM <model table>`. That's it. So we just get all the rows in our Model's table, create a resource collection from the Model class, and return it to the user.


#### show()
Our show function is resposible for taking in a model id in the route and returning the model requested. Laravel has some nice tricks up it sleeve (namely route-model binding that we'll talk about in the route section) that make it hella easy to implement this function.

```php
/**
 * Display the specified resource.
 */
public function show(Team $team)
{
    Log::info("TeamController->show($team->id)");
    return new TeamResource($team);
}
```

Yup. Since we have route-model binding, by the time this function executes, we already have access to the requested model. So all we have to do is our standard log statement, and then return a TeamResource with the hydrated model. BAM. Easy peasy.

#### update()
Update is also greatly simplified by Laravel magic. Since we want users to be able to only update one field or a certain suset of fields and our `UpdateTeamRequest` class will allow us to only pass in parts of the model, we can use the `$request->only()` function to filter down to only passed attributes and not have to do any merging of fields manually.

```php
/**
 * Update the specified resource in storage.
 */
public function update(UpdateTeamRequest $request, Team $team)
{
    Log::info("TeamController->update($team->id)");
    $team->update($request->only([
        'name',
        'website',
    ]));
    return new TeamResource($team);
}
```

So we make our log statement, then we use the model's `update()` function to update only the fields passed in the request. Then we return a TeamResource of the udpated model.

#### destroy()
Last on our CRUD list is the good ole destroy. Laravel provides ways to do soft-deletes, which is just using a database column to mark something as deleted, which is really halpful for allowing people to recover accidental deletes. But dowe care about good UX? NAW. We're just gonna hard delete things! YOLO!

```php
/**
 * Remove the specified resource from storage.
 */
public function destroy(Team $team)
{
    Log::info("TeamController->destroy($team->id)");
    $team->delete();
    return response()->json(null, 204);
}
```

So, as you probably expected by now, we get a `delete()` function for free with Laravel. What's up with the return though?

Ok, we deleted our record from the database, so we don't have a model to hydrate a resource with. So we're gonna use the global `response()` function to modify the response directly. Specifically we wnat to modify the json of the response to have a null body and a 204 status code. If you look at the spec for HTTP 204 code, it means No Content. So by sending null and 204, we're indicating to the consuming client that there is no content related to that model ID anymore.

### Policy
Policies allow you to define a bunch of rules around who can and can't take what actions on which models. It's a rabbit hole all in and of itself. So to keep this workshop sweet and simple, we're gonna make every method in our Policy class return true. So everyone can do everything all the time to all the models. Wooooooooo.

In the real world you probably want to limit updating and deleting to users that own resources and admins. And there's probably 15 other edge cases that rando product managers would want you to implement as well.

### Routes
Ok, so we have a database table, a model, a controller, and now permissions to do whatever we please. There's just one last problem. We don't have an API endpoint to hit to do any of it. 

We have a folder called `routes` that has a file `api.php` this file allows us to define RESTful endpoints for our API. Laravel let's us write our own route patterns or use built-in routing, which is what I would recommend until you need something not covered by it.

Currently, our routes file looks like:

```php
<?php

use App\Http\Controllers\TeamController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
```

Which basically just lets us fetch a logged-in user, which we aren't going down the path of setting up this time around. So we need to add some routes for our team model. This is the pattern we want:

1. POST /teams -> store()
1. GET /teams -> index()
1. GET /teams/{id} -> show()
1. PUT /teams/{id} -> update()
1. DELETE /teams/{id} -> destroy()

So, let's do that. Add this below what's currently in your `api.php` file

```php
Route::middleware([])->group(function () {
    Route::post('/teams', [TeamController::class, 'store']);
    Route::get('/teams', [TeamController::class, 'index']);
    Route::get('/teams/{id}', [TeamController::class, 'show']);
    Route::put('/teams/{id}', [TeamController::class, 'update']);
    Route::delete('/teams/{id}', [TeamController::class, 'destroy']);
});
```

It would kinda suck to have to repeat this pattern over and over. It's super boiler-platey. So Laravel made it easier. All we have to do is replace the code we just wrote with this:

```php
Route::middleware([])->group(function () {
    Route::apiResources([
        'teams' => TeamController::class,
    ]);
});
```

And we're good to go.

---

Switch to 2-Driver-Model to continue with our API.
