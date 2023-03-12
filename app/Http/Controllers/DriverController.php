<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDriverRequest;
use App\Http\Requests\UpdateDriverRequest;
use App\Http\Resources\DriverResource;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DriverController extends Controller
{
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
    public function show(Request $request, Driver $driver)
    {
        Log::info("DriverController->show($driver->id)");

        $relationships = [];
        if($request->query('withTeam')) {
            array_push($relationships, 'team');
        }

        return new DriverResource($driver->load($relationships));
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
