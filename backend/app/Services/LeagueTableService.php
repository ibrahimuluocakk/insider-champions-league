<?php

namespace App\Services;

use App\Contracts\FixtureRepositoryInterface;
use App\Contracts\TeamRepositoryInterface;
use App\Models\Team;
use Illuminate\Support\Collection;

class LeagueTableService
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

    public function getLeagueTable(): array
    {
        $teams = $this->teamRepository->all();
        $playedMatches = $this->fixtureRepository->getPlayedMatches();

        $standings = $teams->map(function (Team $team) use ($playedMatches) {
            return $this->calculateTeamStats($team, $playedMatches);
        });

        $sortedStandings = $this->sortStandings($standings);

        return [
            'standings' => $sortedStandings->values()->toArray(),
            'total_teams' => $teams->count(),
            'matches_played' => $playedMatches->count(),
        ];
    }

    private function calculateTeamStats(Team $team, Collection $matches): array
    {
        $homeMatches = $matches->where('home_team_id', $team->id);
        $awayMatches = $matches->where('away_team_id', $team->id);

        $stats = [
            'team_id' => $team->id,
            'team_name' => $team->name,
            'matches_played' => 0,
            'wins' => 0,
            'draws' => 0,
            'losses' => 0,
            'goals_for' => 0,
            'goals_against' => 0,
            'goal_difference' => 0,
            'points' => 0,
        ];

        foreach ($homeMatches as $match) {
            $this->processMatch($stats, $match->home_goals, $match->away_goals, true);
        }

        foreach ($awayMatches as $match) {
            $this->processMatch($stats, $match->away_goals, $match->home_goals, false);
        }

        $stats['goal_difference'] = $stats['goals_for'] - $stats['goals_against'];

        return $stats;
    }

    private function processMatch(array &$stats, int $teamGoals, int $opponentGoals, bool $isHome): void
    {
        $stats['matches_played']++;
        $stats['goals_for'] += $teamGoals;
        $stats['goals_against'] += $opponentGoals;

        if ($teamGoals > $opponentGoals) {
            $stats['wins']++;
            $stats['points'] += 3;
        } elseif ($teamGoals === $opponentGoals) {
            $stats['draws']++;
            $stats['points'] += 1;
        } else {
            $stats['losses']++;
        }
    }

    private function sortStandings(Collection $standings): Collection
    {
        return $standings->sortByDesc(function ($team) {
            return [
                $team['points'],
                $team['goal_difference'],
                $team['goals_for']
            ];
        });
    }
}
