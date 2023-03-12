<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCarRequest;
use App\Http\Requests\UpdateCarRequest;
use App\Http\Resources\CarResource;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Log::info("CarController->index()");
        $relationships = [];
        if($request->query('withTeam')) {
            array_push($relationships, 'team');
        }
        if($request->query('withDriver')) {
            array_push($relationships, 'driver');
        }

        return CarResource::collection(Car::with($relationships)->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCarRequest $request)
    {
        Log::info("CarController->store()");

        $car = Car::create([
            'number' => $request->number,
            'teamId' => $request->teamId,
            'driverId' => $request->driverId,
        ]);

        return new CarResource($car);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Car $car)
    {
        Log::info("CarController->show($car->id)");

        $relationships = [];
        if($request->query('withTeam')) {
            array_push($relationships, 'team');
        }
        if($request->query('withDriver')) {
            array_push($relationships, 'driver');
        }

        return new CarResource($car->load($relationships));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCarRequest $request, Car $car)
    {
        Log::info("CarController->update($car->id)");

        $car->update($request->only([
            'number',
            'driverId',
        ]));

        return new CarResource($car);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Car $car)
    {
        Log::info("CarController->destroy($car->id)");
        $car->delete();
        return response()->json(null, 204);
    }
}
