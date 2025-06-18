<?php

namespace App\Repositories;

use App\Contracts\FixtureRepositoryInterface;
use App\Models\GameMatch;
use Illuminate\Database\Eloquent\Collection;

class FixtureRepository extends BaseRepository implements FixtureRepositoryInterface
{
    public function __construct(GameMatch $model)
    {
        parent::__construct($model);
    }

    public function getAllWithTeams(): Collection
    {
        return GameMatch::with(['homeTeam', 'awayTeam'])
                        ->orderBy('week')
                        ->get();
    }

    public function createMany(array $fixtures): Collection
    {
        $created = new Collection();

        foreach ($fixtures as $fixture) {
            $created->push(GameMatch::create($fixture));
        }

        return $created;
    }

    public function deleteAll(): bool
    {
        GameMatch::truncate();
        return true;
    }

    public function getMatchesByWeek(int $week): Collection
    {
        return GameMatch::with(['homeTeam', 'awayTeam'])
                        ->where('week', $week)
                        ->get();
    }

    public function getFirstUnplayedMatch(): ?GameMatch
    {
        return GameMatch::where('is_played', false)
                       ->orderBy('week')
                       ->first();
    }

    public function getPlayedMatches(): Collection
    {
        return GameMatch::where('is_played', true)
                       ->with(['homeTeam', 'awayTeam'])
                       ->get();
    }
}
