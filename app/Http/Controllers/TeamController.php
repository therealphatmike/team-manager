<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Log::info("TeamController->index()");

        $relationships = [];
        if($request->query('withDrivers')) {
            array_push($relationships, 'drivers');
        }

        return TeamResource::collection(Team::with($relationships)->get());
    }

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

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Team $team)
    {
        Log::info("TeamController->show($team->id)");

        $relationships = [];
        if($request->query('withDrivers')) {
            array_push($relationships, 'drivers');
        }

        return new TeamResource($team->load($relationships));
    }

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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        Log::info("TeamController->destroy($team->id)");
        $team->delete();
        return response()->json(null, 204);
    }
}
