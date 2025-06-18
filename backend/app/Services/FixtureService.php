<?php

namespace App\Services;

use App\Contracts\FixtureRepositoryInterface;
use App\Contracts\TeamRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class FixtureService
{
    protected TeamRepositoryInterface $teamRepository;
    protected FixtureRepositoryInterface $fixtureRepository;

    public function __construct(
        TeamRepositoryInterface $teamRepository,
        FixtureRepositoryInterface $fixtureRepository
    ) {
        $this->teamRepository = $teamRepository;
        $this->fixtureRepository = $fixtureRepository;
    }

    /**
     * Generate all fixtures for the season (round-robin format).
     * 4 teams, each plays 6 matches (3 home, 3 away), total 12 matches over 6 weeks.
     * Each team plays only 1 match per week.
     * Randomized matchups while maintaining round-robin constraints.
     */
    public function generateFixtures(): Collection
    {
        $this->fixtureRepository->deleteAll();
        $teams = $this->teamRepository->all();

        if ($teams->count() !== 4) {
            throw new \Exception('Exactly 4 teams are required for fixture generation');
        }

        $teamIds = $teams->pluck('id')->toArray();
        $allMatchups = $this->generateAllMatchups($teamIds);
        $weeklyFixtures = $this->distributeMatchupsAcrossWeeks($allMatchups);

        return $this->fixtureRepository->createMany($weeklyFixtures);
    }

    /**
     * Generate all possible matchups for round-robin tournament.
     * Each team plays every other team twice (home and away).
     */
    private function generateAllMatchups(array $teamIds): array
    {
        $matchups = [];

        for ($i = 0; $i < count($teamIds); $i++) {
            for ($j = 0; $j < count($teamIds); $j++) {
                if ($i !== $j) {
                    $matchups[] = [
                        'home_team_id' => $teamIds[$i],
                        'away_team_id' => $teamIds[$j],
                    ];
                }
            }
        }

        shuffle($matchups);
        return $matchups;
    }

    /**
     * Distribute matchups across 6 weeks ensuring each team plays exactly once per week.
     * Uses backtracking algorithm to find valid distribution.
     */
    private function distributeMatchupsAcrossWeeks(array $matchups): array
    {
        $weeklyFixtures = [];
        $usedMatchups = [];

        for ($week = 1; $week <= 6; $week++) {
            $weekMatches = $this->findValidWeekMatches($matchups, $usedMatchups);

            if (count($weekMatches) !== 2) {
                return $this->distributeMatchupsAcrossWeeks($this->reshuffleMatchups($matchups));
            }

            foreach ($weekMatches as $match) {
                $weeklyFixtures[] = [
                    'home_team_id' => $match['home_team_id'],
                    'away_team_id' => $match['away_team_id'],
                    'week' => $week,
                    'is_played' => false,
                ];
                $usedMatchups[] = $match;
            }
        }

        return $weeklyFixtures;
    }

    /**
     * Find 2 valid matches for a week where no team plays twice.
     */
    private function findValidWeekMatches(array $allMatchups, array $usedMatchups): array
    {
        $availableMatchups = array_filter($allMatchups, function($matchup) use ($usedMatchups) {
            return !$this->isMatchupUsed($matchup, $usedMatchups);
        });

        foreach ($availableMatchups as $i => $match1) {
            foreach ($availableMatchups as $j => $match2) {
                if ($i >= $j) continue;

                $teamsInMatch1 = [$match1['home_team_id'], $match1['away_team_id']];
                $teamsInMatch2 = [$match2['home_team_id'], $match2['away_team_id']];

                if (empty(array_intersect($teamsInMatch1, $teamsInMatch2))) {
                    return [$match1, $match2];
                }
            }
        }

        return [];
    }

    /**
     * Check if a matchup has already been used.
     */
    private function isMatchupUsed(array $matchup, array $usedMatchups): bool
    {
        foreach ($usedMatchups as $used) {
            if ($used['home_team_id'] === $matchup['home_team_id'] &&
                $used['away_team_id'] === $matchup['away_team_id']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Reshuffle matchups for another attempt.
     */
    private function reshuffleMatchups(array $matchups): array
    {
        shuffle($matchups);
        return $matchups;
    }

    /**
     * Get all fixtures with team information.
     */
    public function getAllFixtures(): Collection
    {
        return $this->fixtureRepository->getAllWithTeams();
    }
}
