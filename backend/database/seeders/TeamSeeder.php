<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = [
            [
                'name' => 'Manchester United',
                'power' => 90,
                'home_advantage' => 5,
            ],
            [
                'name' => 'Liverpool',
                'power' => 80,
                'home_advantage' => 6,
            ],
            [
                'name' => 'Chelsea',
                'power' => 50,
                'home_advantage' => 4,
            ],
            [
                'name' => 'Arsenal',
                'power' => 25,
                'home_advantage' => 5,
            ],
        ];

        foreach ($teams as $team) {
            Team::create($team);
        }
    }
}
