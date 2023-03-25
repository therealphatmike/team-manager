# Car Model
Up to this point you should know everything you need to know to create and test the car model, and loading the relevant relationships. I'll leave some steps, but leave the programming up to you. The next and final step will talk about a few things like Resources, Request Validation, etc.

## Steps
1. use the artisan CLI to create the necessary files for a model called Car
1. write the model and the migration for a car with the attributes
    1. integer number
    1. uuid team id
    1. uuid car id
1. a car belongs to a team (a team has many cars)
1. a car has a driver (a driver belongs to a car)
1. write your controller functions (we want to be able to load teams and drivers based on query parameters in the show and index functions)
1. add car relationship queries to team and driver as well
1. make all of your policies return true
1. add routes to your route file

## Moving On
Once you've done and tested that, move on to `5-Clean-Up-Resources`, which is admittedly a poorly named branch.
