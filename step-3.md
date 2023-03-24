
# Relationships
Ok now we have our basic Driver model implemented. But it's pretty useless. We can't attach a driver to a team, so let's set that up now. We need our models to know about their related entities. We want our Database to know about the relationships between tables. And then we need ways to allow users of the API to get at related models and to be able to return those models in our JSON resources.

## Relationships on Models
Laravel's ORM (Object Relational Model) is called Eloquent. To define relationships to Laravel Eloquent, we just write functions on our Model that return descriptions of the relationships. I'm going to go over the basics here, but if you're wanting to dive deeper, go to [The docs](https://laravel.com/docs/10.x/eloquent-relationships). Laravel's docs are very rich and descriptive.

Ok, so according to the ERD in the README file, a team `has many` drivers, and said inversely a driver `belongs to` a team.
So, let's define these relationships on our models. 

In the `Team.php` model we will write the following function:

```php
public function drivers(): HasMany
{
    return $this->hasMany(Driver::class);
}
```

Now Eloquent is aware that our Team will have many drivers associated to it. So how do we tell Eloquent what team a driver belongs to?

```php
public function team(): BelongsTo
{
    return $this->belongsTo(Team::class, 'team_id');
}
```

The second argument here is to tell Eloquent what column in the `drivers` table to look at to find the id for the team. This is our `foreign key`.

And that's it. All we do is tell Eloquent that our Team `HasMany` drivers and that our Drivers will `BelongTo` a team and where to find that ID. Now we need to set these relationships up in our database.

## Relationships in Migrations
Remember that our migration files are just PHP code that create our database schemas and undo database schema changes for us. So, while it is not necessarily required to tell the database about what fields are foriegn keys, etc., it is a good practice and in general can increase read performance from your database. Regardless of if you want to tell the database about relationships, we still need to add the Team ID column to the driver migration.

You can either rollback your last migration and change that file or you can run `php artisan make:migration connect_drivers_and_teams` to generate a new migration.

### Working with the original migration
So, in your `up()` function in the driver migration file, add:

```php
$table->uuid('team_id');
```

And if you're going to inform the database of your foreign keys add the following:

```php
$table->foreign('team_id')->references('id')->on('teams');
```

So your driver migration, should look like:

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
        Schema::create('drivers', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->string('firstName');
            $table->string('lastName');
            $table->string('email');
            $table->uuid('teamId');
            $table->foreign('teamId')->references('id')->on('teams');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
```

So now our database knows that we will have a team associated with each driver.

## Relationships in Controllers
### Inserting the Team ID At Creation Time
When we create a new Driver, we now need to take in the team id and insert it into the team id column in our drivers table. That's it for creation time, Laravel and our DB will handle the rest.

### Fetching Related Team
Now we can get into showing users related models based on query parameters. It makes sense for certain views on a UI or for certain programs consuming an API that they would also need to get at the relevant related data. Eloquent provides ways of loading related models that don't lead to an N+1 query problem. These are all considered `Eager Loading`, and there's different methods to it like `constrained eager loading` and `lazy eager loading`. The last of which is what we will be doing. What `lazy eager loading` is a mixture of lazy loading and eager loading. Essentially what will happen is we will query the DB and get our model(s), and then we will tell the system to eager load our relationships based on a query parameter. We will do this in 2 places: the `index()` method and the `show()` method.

There are two methods in eloquent to perform eager loading. One is `with()` which will execute the eager load directly after the initial query and the other option is `load()` which will load the realtionships at some later time.

#### index() and with()
For the `index()` function, we want to use the `with()` method, since we will run one statement to load the drivers and the relationships.

So we will modify our index function to look like:

```php
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Log::info("DriverController->index()");
        $relationships = [];
        if($request->query('withTeam')) {
            array_push($relationships, 'team');
        }

        return DriverResource::collection(Driver::with($relationships)->get());
    }
```

We create an empty relationships array, and then if we have a truthy value in the `withTeam` query parameter, we will add the 'team' string to the relationships array. It is important to note that you must use the name of the relationship function on your models here. Then when we go to fetch our Driver models, we call the static function `with()` and pass in the relationships we want to load, and then we call `get()` to execute the query and load the results. What this last line of code in our function will do can be summed up in these steps:
1. build a query that loads defined relationships.
1. execute queries that are relevant to load the data we want.
    1. In this particular case the `SELECT * FROM drivers` is impllicit.
    1. Then our with clause will use the team_id on the driver to collate a list of team_ids to fetch from the teams table.
1. use the returned collection from eloquent to build our collection of DriverResources (the resource class will inject a Team object into our response if a team is loaded in a particular case).
1. return the collection to the user.

#### show() and load()
The `show()` function needs to use the `load()` function instead of with. The reason for this is explainable by looking at the signature of the show function

```php
public function show(Request $request, Driver $driver)
```

As  you can see, we are already getting a hydrated Driver model passed to this function. How? Laravel uses the IDs in the routes to perform `route-model binding` which is fancy speak for loading your object from the database for you since it knows what model we're after and the id from the route. Remember our route for this particular method is `GET /drivers/{id}` so laravel sees that we're trying to load the details of a driver given an ID and it goes ahead and fetches it for us, so we will be `loading` the relationships at a later time than when the initial query was ran.

The idea is relatively the same, we need to create an empty relationships array, add the relevant relationships based on query parameters and then call load. So, our `show()` function should look like this now:

```php
    /**
     * Display the specified resource.
     */
    public function show(Request $request, Driver $driver)
    {
        Log::info("DriverController->show($driver->id)");

        $relationships = [];
        if($request->query('withTeam')) {
            array_push($relationships, 'team');
        }

        return new DriverResource($driver->load($relationships));
    }
```

## Inversing the load ing of relationships
As practice, try writing some code to load all the drivers for a team in both the index and show methods based on a query parameter called `withDrivers`.


## Moving On
Once you're done and have tested this and understand the concepts, please move on to the branch `4-Car-Model`
