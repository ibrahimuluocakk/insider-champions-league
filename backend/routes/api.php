<?php

use App\Http\Controllers\FixtureController;
use App\Http\Controllers\LeagueTableController;
use App\Http\Controllers\MatchSimulationController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\ChampionshipPredictionController;
use Illuminate\Support\Facades\Route;

// Teams Resource
Route::get('/teams', [TeamController::class, 'index']);

// Fixtures Resource
Route::get('/fixtures', [FixtureController::class, 'index']);
Route::post('/fixtures', [FixtureController::class, 'store']);

// Match Simulation
Route::post('/simulate/next-week', [MatchSimulationController::class, 'simulateNextWeek']);
Route::post('/simulate/all', [MatchSimulationController::class, 'simulateAll']);

// League Analysis
Route::get('/league-table', [LeagueTableController::class, 'index']);
Route::get('/championship-predictions', [ChampionshipPredictionController::class, 'index']);
