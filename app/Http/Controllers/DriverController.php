<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDriverRequest;
use App\Http\Requests\UpdateDriverRequest;
use App\Http\Resources\DriverResource;
use App\Models\Driver;
use Illuminate\Support\Facades\Log;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Log::info("DriverController->index()");
        return DriverResource::collection(Driver::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDriverRequest $request)
    {
        Log::info("DriverController->store()");

        $driver = Driver::create([
            'firstName' => $request->firstName,
            'lastName' => $request->lastName,
            'email' => $request->email,
            'teamId' => $request->teamId,
        ]);

        return new DriverResource($driver);
    }

    /**
     * Display the specified resource.
     */
    public function show(Driver $driver)
    {
        Log::info("DriverController->show($driver->id)");
        return new DriverResource($driver);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDriverRequest $request, Driver $driver)
    {
        Log::info("DriverController->update($driver->id)");

        $driver->update($request->only([
            'firstName',
            'lastName',
            'email',
            'teamId',
        ]));

        return new DriverResource($driver);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Driver $driver)
    {
        Log::info("DriverController->destroy($driver->id)");
        $driver->delete();
        return response()->json(null, 204);
    }
}
