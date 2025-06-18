<?php

namespace App\Http\Controllers;

use App\Services\LeagueTableService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class LeagueTableController extends Controller
{
    use ApiResponse;

    public function __construct(private LeagueTableService $leagueTableService) {}

    public function index(): JsonResponse
    {
        try {
            $leagueTable = $this->leagueTableService->getLeagueTable();

            return $this->successResponse($leagueTable, 'League table retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
