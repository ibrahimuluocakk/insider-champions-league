<?php

namespace App\Services;

use App\Contracts\FixtureRepositoryInterface;
use App\Contracts\TeamRepositoryInterface;
use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;

class MatchSimulationService
{
    protected FixtureRepositoryInterface $fixtureRepository;
    protected TeamRepositoryInterface $teamRepository;

    public function __construct(
        FixtureRepositoryInterface $fixtureRepository,
        TeamRepositoryInterface $teamRepository
    ) {
        $this->fixtureRepository = $fixtureRepository;
        $this->teamRepository = $teamRepository;
    }

    public function simulateNextWeek(): array
    {
        $nextWeek = $this->findNextWeekToPlay();

        if (!$nextWeek) {
            throw new \Exception('All matches have been played');
        }

        return $this->simulateWeek($nextWeek);
    }

    public function simulateAllRemaining(): array
    {
        $allResults = [];
        $totalSimulated = 0;

        while (true) {
            $nextWeek = $this->findNextWeekToPlay();

            if (!$nextWeek) {
                break;
            }

            $weekResult = $this->simulateWeek($nextWeek);
            $allResults[] = $weekResult;
            $totalSimulated += $weekResult['total_simulated'];
        }

        if (empty($allResults)) {
            throw new \Exception('All matches have already been played');
        }

        return [
            'weeks_simulated' => count($allResults),
            'total_matches_simulated' => $totalSimulated,
            'results' => $allResults
        ];
    }

    public function simulateWeek(int $week): array
    {
        $matches = $this->fixtureRepository->getMatchesByWeek($week);

        if ($matches->isEmpty()) {
            throw new \Exception("No matches found for week {$week}");
        }

        $simulatedMatches = [];

        foreach ($matches as $match) {
            if ($match->is_played) {
                continue;
            }

            $this->simulateMatch($match);
            $simulatedMatches[] = $match;
        }

        return [
            'week' => $week,
            'matches' => $simulatedMatches,
            'total_simulated' => count($simulatedMatches)
        ];
    }
    private function simulateMatch(GameMatch $match): void
    {
        $homeTeam = $match->homeTeam;
        $awayTeam = $match->awayTeam;

        $homeGoals = $this->calculateGoals($homeTeam, $awayTeam, true);
        $awayGoals = $this->calculateGoals($awayTeam, $homeTeam, false);

        $match->update([
            'home_goals' => $homeGoals,
            'away_goals' => $awayGoals,
            'is_played' => true,
            'played_at' => now()
        ]);
    }

    private function calculateGoals(Team $team, Team $opponent, bool $isHome): int
    {
        $teamStrength = $this->calculateTeamStrength($team, $isHome);
        $opponentStrength = $this->calculateTeamStrength($opponent, !$isHome);

        $strengthRatio = $teamStrength / ($teamStrength + $opponentStrength);
        $baseGoalExpectancy = $this->getBaseGoalExpectancy($strengthRatio);

        return $this->addRandomness($baseGoalExpectancy);
    }

    private function calculateTeamStrength(Team $team, bool $isHome): int
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

    private function findNextWeekToPlay(): ?int
    {
        $unplayedMatch = $this->fixtureRepository->getFirstUnplayedMatch();

        return $unplayedMatch ? $unplayedMatch->week : null;
    }
}
