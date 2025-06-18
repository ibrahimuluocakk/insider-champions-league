<?php

namespace App\Http\Controllers;

use App\Services\MatchSimulationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class MatchSimulationController extends Controller
{
    use ApiResponse;

    public function __construct(private MatchSimulationService $matchSimulationService) {}

    public function simulateNextWeek(): JsonResponse
    {
        try {
            $result = $this->matchSimulationService->simulateNextWeek();

            return $this->successResponse($result, 'Next week simulated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function simulateAll(): JsonResponse
    {
        try {
            $result = $this->matchSimulationService->simulateAllRemaining();

            return $this->successResponse($result, 'All remaining matches simulated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
