<?php

namespace App\Repositories;

use App\Contracts\TeamRepositoryInterface;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;

class TeamRepository extends BaseRepository implements TeamRepositoryInterface
{
    public function __construct(Team $model)
    {
        parent::__construct($model);
    }

    public function all(): Collection
    {
        return Team::all();
    }

    /**
     * Get team by ID with type hint.
     */
    public function getTeamById(int $id): ?Team
    {
        return $this->model->find($id);
    }
}
