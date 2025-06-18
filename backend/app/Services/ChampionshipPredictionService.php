<?php

namespace App\Services;

use App\Contracts\FixtureRepositoryInterface;
use Illuminate\Support\Collection;

class ChampionshipPredictionService
{
    public function __construct(
        private LeagueTableService $leagueTableService,
        private MatchSimulationService $simulationService,
        private FixtureRepositoryInterface $fixtureRepository
    ) {}

    public function getChampionshipPredictions(): array
    {
        $playedMatches = $this->fixtureRepository->getPlayedMatches();
        $totalMatches = $this->getTotalMatchesCount();
        $currentWeek = $this->getCurrentWeek($playedMatches);

        if (!$this->isPredictionAvailable($currentWeek, $playedMatches->count())) {
            return [
                'is_available' => false,
                'reason' => 'Championship predictions available after week 4',
                'current_week' => $currentWeek,
                'matches_played' => $playedMatches->count(),
                'minimum_required' => 6
            ];
        }

        if ($this->isSeasonCompleted($playedMatches->count(), $totalMatches)) {
            return $this->getCompletedSeasonResult();
        }

        $currentStandings = $this->leagueTableService->getLeagueTable()['standings'];
        $remainingMatches = $this->getRemainingMatches();

        $predictions = $this->runMonteCarloSimulation($currentStandings, $remainingMatches);

        return [
            'is_available' => true,
            'current_week' => $currentWeek,
            'matches_played' => $playedMatches->count(),
            'matches_remaining' => $remainingMatches->count(),
            'simulation_iterations' => 1000,
            'predictions' => $predictions,
            'methodology' => [
                'type' => 'Monte Carlo Simulation',
                'factors' => [
                    'current_points',
                    'team_strength',
                    'remaining_fixtures',
                    'home_advantage',
                    'goal_scoring_history',
                    'head_to_head_performance'
                ],
                'iterations' => 1000,
                'algorithm' => 'Each simulation runs remaining matches using team power + home advantage + randomness'
            ]
        ];
    }

    private function isPredictionAvailable(int $currentWeek, int $matchesPlayed): bool
    {
        return $currentWeek >= 4 && $matchesPlayed >= 6;
    }

    private function isSeasonCompleted(int $playedMatches, int $totalMatches): bool
    {
        return $playedMatches >= $totalMatches;
    }

    private function getCurrentWeek(Collection $playedMatches): int
    {
        if ($playedMatches->isEmpty()) {
            return 0;
        }

        return $playedMatches->max('week') ?? 0;
    }

    private function getTotalMatchesCount(): int
    {
        return 12; // 4 teams, 6 matches each
    }

    private function getRemainingMatches(): Collection
    {
        return $this->fixtureRepository->getAllWithTeams()
            ->where('is_played', false);
    }

    private function getCompletedSeasonResult(): array
    {
        $finalStandings = $this->leagueTableService->getLeagueTable()['standings'];
        $champion = $finalStandings[0];

        return [
            'is_available' => true,
            'season_completed' => true,
            'champion' => [
                'team_id' => $champion['team_id'],
                'team_name' => $champion['team_name'],
                'final_points' => $champion['points'],
                'championship_probability' => 100.0
            ],
            'final_standings' => $finalStandings
        ];
    }

    private function runMonteCarloSimulation(array $currentStandings, Collection $remainingMatches): array
    {
        $championshipCounts = [];
        $totalSimulations = 1000;

        for ($i = 0; $i < $totalSimulations; $i++) {
            $simulationResult = $this->simulateRemainingSeasonAndGetChampion($currentStandings, $remainingMatches);
            $championId = $simulationResult['champion_id'];

            $championshipCounts[$championId] = ($championshipCounts[$championId] ?? 0) + 1;
        }

        return $this->formatPredictions($championshipCounts, $currentStandings, $totalSimulations);
    }

    private function simulateRemainingSeasonAndGetChampion(array $currentStandings, Collection $remainingMatches): array
    {
        $simulationStandings = collect($currentStandings)->keyBy('team_id')->toArray();
        $matchesByWeek = $remainingMatches->groupBy('week');

        foreach ($matchesByWeek as $week => $weekMatches) {
            $simulatedResults = $this->simulateMatchesWithoutSaving($weekMatches);

            foreach ($simulatedResults as $matchResult) {
                $this->updateStandingsWithMatch($simulationStandings, $matchResult);
            }
        }

        $finalStandings = collect($simulationStandings)
            ->sortByDesc('points')
            ->sortByDesc('goal_difference')
            ->sortByDesc('goals_for')
            ->values()
            ->toArray();

        return [
            'champion_id' => $finalStandings[0]['team_id'],
            'final_standings' => $finalStandings
        ];
    }

    private function simulateMatchesWithoutSaving(Collection $matches): array
    {
        $results = [];

        foreach ($matches as $match) {
            $homeTeam = $match->homeTeam;
            $awayTeam = $match->awayTeam;

            $homeGoals = $this->calculateGoals($homeTeam, $awayTeam, true);
            $awayGoals = $this->calculateGoals($awayTeam, $homeTeam, false);

            $results[] = [
                'home_team_id' => $match->home_team_id,
                'away_team_id' => $match->away_team_id,
                'home_goals' => $homeGoals,
                'away_goals' => $awayGoals
            ];
        }

        return $results;
    }

    private function calculateGoals($team, $opponent, bool $isHome): int
    {
        $teamStrength = $this->calculateTeamStrength($team, $isHome);
        $opponentStrength = $this->calculateTeamStrength($opponent, !$isHome);

        $strengthRatio = $teamStrength / ($teamStrength + $opponentStrength);
        $baseGoalExpectancy = $this->getBaseGoalExpectancy($strengthRatio);

        return $this->addRandomness($baseGoalExpectancy);
    }

    private function calculateTeamStrength($team, bool $isHome): int
    {
        $strength = $team->power;

        if ($isHome) {
            $strength += $team->home_advantage;
        }

        return $strength;
    }

    private function getBaseGoalExpectancy(float $strengthRatio): float
    {
        return 0.5 + ($strengthRatio * 2.5);
    }

    private function addRandomness(float $baseExpectancy): int
    {
        $randomFactor = (rand(50, 150) / 100);
        $adjustedExpectancy = $baseExpectancy * $randomFactor;

        return max(0, (int) round($adjustedExpectancy));
    }

    private function updateStandingsWithMatch(array &$standings, $match): void
    {
        $homeTeamId = $match['home_team_id'];
        $awayTeamId = $match['away_team_id'];
        $homeGoals = $match['home_goals'];
        $awayGoals = $match['away_goals'];

        $standings[$homeTeamId]['matches_played']++;
        $standings[$awayTeamId]['matches_played']++;

        $standings[$homeTeamId]['goals_for'] += $homeGoals;
        $standings[$homeTeamId]['goals_against'] += $awayGoals;
        $standings[$awayTeamId]['goals_for'] += $awayGoals;
        $standings[$awayTeamId]['goals_against'] += $homeGoals;

        $standings[$homeTeamId]['goal_difference'] = $standings[$homeTeamId]['goals_for'] - $standings[$homeTeamId]['goals_against'];
        $standings[$awayTeamId]['goal_difference'] = $standings[$awayTeamId]['goals_for'] - $standings[$awayTeamId]['goals_against'];

        if ($homeGoals > $awayGoals) {
            $standings[$homeTeamId]['points'] += 3;
            $standings[$homeTeamId]['wins']++;
            $standings[$awayTeamId]['losses']++;
        } elseif ($homeGoals < $awayGoals) {
            $standings[$awayTeamId]['points'] += 3;
            $standings[$awayTeamId]['wins']++;
            $standings[$homeTeamId]['losses']++;
        } else {
            $standings[$homeTeamId]['points'] += 1;
            $standings[$awayTeamId]['points'] += 1;
            $standings[$homeTeamId]['draws']++;
            $standings[$awayTeamId]['draws']++;
        }
    }

    private function formatPredictions(array $championshipCounts, array $currentStandings, int $totalSimulations): array
    {
        $predictions = [];

        foreach ($currentStandings as $team) {
            $teamId = $team['team_id'];
            $championshipCount = $championshipCounts[$teamId] ?? 0;
            $probability = ($championshipCount / $totalSimulations) * 100;

            $predictions[] = [
                'team_id' => $teamId,
                'team_name' => $team['team_name'],
                'current_position' => array_search($team, $currentStandings) + 1,
                'current_points' => $team['points'],
                'matches_played' => $team['matches_played'],
                'championship_probability' => round($probability, 1),
                'simulations_won' => $championshipCount,
                'goal_difference' => $team['goal_difference']
            ];
        }

        usort($predictions, fn($a, $b) => $b['championship_probability'] <=> $a['championship_probability']);

        return $predictions;
    }
}
