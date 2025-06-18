<?php

namespace App\Http\Controllers;

use App\Http\Resources\TeamResource;
use App\Services\TeamService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class TeamController extends Controller
{
    use ApiResponse;

    public function __construct(private TeamService $teamService) {}

    /**
     * Display all teams (4 teams for simulation).
     */
    public function index(): JsonResponse
    {
        $teams = $this->teamService->getAll();
        $resource = TeamResource::collection($teams);

        return $this->resourceResponse($resource, 'Teams retrieved successfully');
    }
}
