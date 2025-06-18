<?php

namespace App\Contracts;

use App\Models\Team;

interface TeamRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get team by ID with type hint.
     */
    public function getTeamById(int $id): ?Team;
}
