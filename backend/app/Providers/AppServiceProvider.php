<?php

namespace App\Providers;

use App\Contracts\TeamRepositoryInterface;
use App\Contracts\FixtureRepositoryInterface;
use App\Repositories\TeamRepository;
use App\Repositories\FixtureRepository;
use App\Services\ChampionshipPredictionService;
use App\Services\FixtureService;
use App\Services\LeagueTableService;
use App\Services\MatchSimulationService;
use App\Services\TeamService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Team Repository
        $this->app->bind(
            TeamRepositoryInterface::class,
            TeamRepository::class
        );

        // Fixture Repository
        $this->app->bind(
            FixtureRepositoryInterface::class,
            FixtureRepository::class
        );

        // League Table Service
        $this->app->bind(
            LeagueTableService::class,
            LeagueTableService::class
        );

        // Match Simulation Service
        $this->app->bind(
            MatchSimulationService::class,
            MatchSimulationService::class
        );

        // Championship Prediction Service
        $this->app->bind(
            ChampionshipPredictionService::class,
            ChampionshipPredictionService::class
        );

        // Fixture Service
        $this->app->bind(
            FixtureService::class,
            FixtureService::class
        );

        // Team Service
        $this->app->bind(
            TeamService::class,
            TeamService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
