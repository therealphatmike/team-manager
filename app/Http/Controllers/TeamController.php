<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Support\Facades\Log;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Log::info("TeamController->index()");
        return TeamResource::collection(Team::all());
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
    public function show(Team $team)
    {
        Log::info("TeamController->show($team->id)");
        return new TeamResource($team);
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
