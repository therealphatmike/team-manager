# Step 2 - The Driver Model
This step, and all subsequent steps assumes that you've gone through the prior step(s). We will rely on the concepts discussed in previous steps to complete the work in this step without much explanation.

## Driver
Ok, what's a race team without a driver? Pretty much just a mechanic shop. Let's add a Driver model to our app, and do all of the same things we did for the team. This section is intentionally devoid of code examples since we covered what needs to be done in the prior step.

1. Run `php artisan make:model Driver --all` and then `php artisan make:resource DriverResource`
1. Fill out our model and migration with the following:
    - string first_name
    - string last_name
    - string email
1. Make all functions in our policy `return true`
1. Implement all of the methods in our DriverController
1. Add our routes to `routes/api.php`
1. Run the migrations via `php artisan migrate` and test the endpoints.

Once your endpoints work, switch to the branch `3-Connect-Driver-And-Team`
