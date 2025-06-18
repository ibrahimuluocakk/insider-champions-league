<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Team::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                'Liverpool', 'Manchester United', 'Arsenal', 'Chelsea',
                'Manchester City', 'Tottenham', 'Newcastle', 'Brighton'
            ]),
            'power' => $this->faker->numberBetween(70, 90),
            'home_advantage' => $this->faker->numberBetween(3, 7),
        ];
    }
}
