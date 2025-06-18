<?php

namespace Database\Factories;

use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GameMatch>
 */
class GameMatchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GameMatch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'week' => $this->faker->numberBetween(1, 6),
            'home_team_id' => Team::factory(),
            'away_team_id' => Team::factory(),
            'is_played' => false,
            'home_goals' => null,
            'away_goals' => null,
            'played_at' => null,
        ];
    }

    /**
     * Indicate that the match has been played.
     */
    public function played(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_played' => true,
            'home_goals' => $this->faker->numberBetween(0, 5),
            'away_goals' => $this->faker->numberBetween(0, 5),
            'played_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }
}
