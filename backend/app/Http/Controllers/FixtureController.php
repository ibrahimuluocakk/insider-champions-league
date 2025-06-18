<?php

namespace App\Http\Controllers;

use App\Http\Resources\FixtureResource;
use App\Services\FixtureService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class FixtureController extends Controller
{
    use ApiResponse;

    public function __construct(private FixtureService $fixtureService) {}

    /**
     * Display a listing of all fixtures.
     */
    public function index(): JsonResponse
    {
        $fixtures = $this->fixtureService->getAllFixtures();
        $resource = FixtureResource::collection($fixtures);

        return $this->resourceResponse($resource, 'Fixtures retrieved successfully');
    }

    /**
     * Store all fixtures for the season.
     */
    public function store(): JsonResponse
    {
        try {
            $fixtures = $this->fixtureService->generateFixtures();
            $resource = FixtureResource::collection($fixtures);

            return $this->resourceResponse($resource, 'Fixtures created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
