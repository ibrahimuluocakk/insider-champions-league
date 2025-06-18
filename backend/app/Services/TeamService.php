<?php

namespace App\Services;

use App\Contracts\TeamRepositoryInterface;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;

class TeamService extends BaseService
{
    protected TeamRepositoryInterface $teamRepository;

    public function __construct(TeamRepositoryInterface $teamRepository)
    {
        parent::__construct($teamRepository);
        $this->teamRepository = $teamRepository;
    }

    /**
     * Get team by ID with proper type hint.
     */
    public function getTeamById(int $id): ?Team
    {
        return $this->teamRepository->getTeamById($id);
    }

    /**
     * Compare two teams and return power difference.
     */
    public function comparePower(Team $teamA, Team $teamB): array
    {
        $powerDifference = $teamA->power - $teamB->power;

        return [
            'team_a' => $teamA->name,
            'team_b' => $teamB->name,
            'power_difference' => $powerDifference,
            'stronger_team' => $powerDifference > 0 ? $teamA->name : ($powerDifference < 0 ? $teamB->name : 'Equal'),
            'advantage_percentage' => $this->calculateAdvantagePercentage($powerDifference)
        ];
    }

    /**
     * Get team's total strength (power + home advantage).
     */
    public function getTotalStrength(Team $team, bool $isHome = false): int
    {
        return $isHome ? $team->power + $team->home_advantage : $team->power;
    }

    /**
     * Calculate advantage percentage based on power difference.
     */
    private function calculateAdvantagePercentage(int $powerDifference): float
    {
        if ($powerDifference == 0) {
            return 50.0;
        }

        $percentage = 50 + ($powerDifference * 0.4);
        return max(10, min(90, $percentage));
    }
}
