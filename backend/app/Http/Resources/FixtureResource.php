<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FixtureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'week' => $this->week,
            'home_team' => [
                'id' => $this->homeTeam->id,
                'name' => $this->homeTeam->name,
                'power' => $this->homeTeam->power,
            ],
            'away_team' => [
                'id' => $this->awayTeam->id,
                'name' => $this->awayTeam->name,
                'power' => $this->awayTeam->power,
            ],
            'is_played' => $this->is_played,
            'home_goals' => $this->home_goals,
            'away_goals' => $this->away_goals,
            'score' => $this->score,
            'result' => $this->result,
            'played_at' => $this->played_at,
            'created_at' => $this->created_at,
        ];
    }
}
