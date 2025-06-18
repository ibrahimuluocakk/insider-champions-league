<?php

namespace App\Http\Controllers;

use App\Services\ChampionshipPredictionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ChampionshipPredictionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ChampionshipPredictionService $championshipPredictionService
    ) {}

    public function index(): JsonResponse
    {
        try {
            $predictions = $this->championshipPredictionService->getChampionshipPredictions();

            if (!$predictions['is_available']) {
                return $this->errorResponse(
                    $predictions['reason'],
                    400
                );
            }

            $message = isset($predictions['season_completed']) && $predictions['season_completed']
                ? 'Season completed - Final championship result'
                : 'Championship predictions calculated successfully';

            return $this->successResponse($predictions, $message);

        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                'Failed to calculate championship predictions: ' . $e->getMessage()
            );
        }
    }
}
