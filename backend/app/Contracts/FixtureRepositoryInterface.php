<?php

namespace App\Contracts;

use App\Models\GameMatch;
use Illuminate\Database\Eloquent\Collection;

interface FixtureRepositoryInterface extends BaseRepositoryInterface
{
    public function getAllWithTeams(): Collection;

    public function createMany(array $fixtures): Collection;

    public function deleteAll(): bool;

    public function getMatchesByWeek(int $week): Collection;

    public function getFirstUnplayedMatch(): ?GameMatch;

    public function getPlayedMatches(): Collection;
}
