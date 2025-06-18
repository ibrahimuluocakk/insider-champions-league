<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChampionshipPredictionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTeams();
    }

    public function test_championship_predictions_not_available_before_week_4()
    {
        $this->createMatches(3);

        $response = $this->getJson('/api/championship-predictions');

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Championship predictions available after week 4'
                ]);
    }

    public function test_championship_predictions_available_after_week_4()
    {
        $this->createFixtures();
        $this->simulateWeeks([1, 2, 3, 4]);

        $response = $this->getJson('/api/championship-predictions');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'is_available',
                        'current_week',
                        'matches_played',
                        'matches_remaining',
                        'simulation_iterations',
                        'predictions' => [
                            '*' => [
                                'team_id',
                                'team_name',
                                'current_position',
                                'current_points',
                                'matches_played',
                                'championship_probability',
                                'simulations_won',
                                'goal_difference'
                            ]
                        ],
                        'methodology' => [
                            'type',
                            'factors'
                        ]
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertTrue($response->json('data.is_available'));
        $this->assertEquals(4, $response->json('data.current_week'));
        $this->assertEquals(8, $response->json('data.matches_played'));
        $this->assertEquals(4, $response->json('data.matches_remaining'));
        $this->assertEquals(1000, $response->json('data.simulation_iterations'));
    }

    public function test_predictions_include_all_teams()
    {
        $this->createFixtures();
        $this->simulateWeeks([1, 2, 3, 4]);

        $response = $this->getJson('/api/championship-predictions');

        $predictions = $response->json('data.predictions');

        $this->assertCount(4, $predictions);

        $teamNames = collect($predictions)->pluck('team_name')->toArray();
        $this->assertContains('Liverpool', $teamNames);
        $this->assertContains('Manchester United', $teamNames);
        $this->assertContains('Arsenal', $teamNames);
        $this->assertContains('Chelsea', $teamNames);
    }

    public function test_predictions_probabilities_sum_to_100()
    {
        $this->createFixtures();
        $this->simulateWeeks([1, 2, 3, 4]);

        $response = $this->getJson('/api/championship-predictions');

        $predictions = $response->json('data.predictions');
        $totalProbability = collect($predictions)->sum('championship_probability');

        $this->assertEqualsWithDelta(100.0, $totalProbability, 0.1);
    }

    public function test_predictions_are_sorted_by_probability()
    {
        $this->createFixtures();
        $this->simulateWeeks([1, 2, 3, 4]);

        $response = $this->getJson('/api/championship-predictions');

        $predictions = $response->json('data.predictions');

        for ($i = 1; $i < count($predictions); $i++) {
            $this->assertGreaterThanOrEqual(
                $predictions[$i]['championship_probability'],
                $predictions[$i-1]['championship_probability']
            );
        }
    }

    public function test_stronger_teams_have_higher_probability()
    {
        $this->createFixtures();

        $this->simulateMatchesWithResults([
            ['home' => 'Liverpool', 'away' => 'Chelsea', 'home_goals' => 3, 'away_goals' => 0],
            ['home' => 'Manchester United', 'away' => 'Arsenal', 'home_goals' => 2, 'away_goals' => 1],
            ['home' => 'Arsenal', 'away' => 'Liverpool', 'home_goals' => 0, 'away_goals' => 2],
            ['home' => 'Chelsea', 'away' => 'Manchester United', 'home_goals' => 1, 'away_goals' => 2],
            ['home' => 'Liverpool', 'away' => 'Manchester United', 'home_goals' => 1, 'away_goals' => 1],
            ['home' => 'Arsenal', 'away' => 'Chelsea', 'home_goals' => 2, 'away_goals' => 0],
            ['home' => 'Manchester United', 'away' => 'Liverpool', 'home_goals' => 0, 'away_goals' => 3],
            ['home' => 'Chelsea', 'away' => 'Arsenal', 'home_goals' => 1, 'away_goals' => 1],
        ]);

        $response = $this->getJson('/api/championship-predictions');

        $predictions = $response->json('data.predictions');
        $liverpoolPrediction = collect($predictions)->firstWhere('team_name', 'Liverpool');

        $this->assertEquals($predictions[0]['team_name'], 'Liverpool');
        $this->assertGreaterThan(30, $liverpoolPrediction['championship_probability']);
    }

    public function test_season_completed_returns_final_champion()
    {
        $this->createFixtures();

        // Simulate all matches
        $this->postJson('/api/simulate/all');

        $response = $this->getJson('/api/championship-predictions');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'is_available',
                        'season_completed',
                        'champion' => [
                            'team_id',
                            'team_name',
                            'final_points',
                            'championship_probability'
                        ],
                        'final_standings'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertTrue($response->json('data.season_completed'));
        $this->assertEquals(100.0, $response->json('data.champion.championship_probability'));
    }

    public function test_dominant_team_scenario_from_documentation()
    {
        $this->createFixtures();

        $this->simulateMatchesWithResults([
            ['home' => 'Liverpool', 'away' => 'Chelsea', 'home_goals' => 3, 'away_goals' => 0],
            ['home' => 'Manchester United', 'away' => 'Arsenal', 'home_goals' => 1, 'away_goals' => 1],
            ['home' => 'Arsenal', 'away' => 'Liverpool', 'home_goals' => 0, 'away_goals' => 3],
            ['home' => 'Chelsea', 'away' => 'Manchester United', 'home_goals' => 0, 'away_goals' => 0],
            ['home' => 'Liverpool', 'away' => 'Manchester United', 'home_goals' => 2, 'away_goals' => 0],
            ['home' => 'Arsenal', 'away' => 'Chelsea', 'home_goals' => 1, 'away_goals' => 0],
            ['home' => 'Manchester United', 'away' => 'Liverpool', 'home_goals' => 0, 'away_goals' => 3],
            ['home' => 'Chelsea', 'away' => 'Arsenal', 'home_goals' => 0, 'away_goals' => 1],
        ]);

        $response = $this->getJson('/api/championship-predictions');

        $predictions = $response->json('data.predictions');
        $liverpoolPrediction = collect($predictions)->firstWhere('team_name', 'Liverpool');

        $this->assertGreaterThan(90, $liverpoolPrediction['championship_probability']);
        $this->assertEquals('Liverpool', $predictions[0]['team_name']);
    }

    public function test_endpoint_handles_errors_gracefully()
    {
        Team::truncate();

        $response = $this->getJson('/api/championship-predictions');

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false
                ]);
    }

    private function seedTeams(): void
    {
        Team::create(['name' => 'Liverpool', 'power' => 90, 'home_advantage' => 6]);
        Team::create(['name' => 'Manchester United', 'power' => 85, 'home_advantage' => 5]);
        Team::create(['name' => 'Arsenal', 'power' => 80, 'home_advantage' => 5]);
        Team::create(['name' => 'Chelsea', 'power' => 75, 'home_advantage' => 4]);
    }

    private function createMatches(int $count): void
    {
        $teams = Team::all();

        for ($i = 0; $i < $count; $i++) {
            GameMatch::create([
                'home_team_id' => $teams[$i % 4]->id,
                'away_team_id' => $teams[($i + 1) % 4]->id,
                'week' => intval($i / 2) + 1,
                'is_played' => true,
                'home_goals' => rand(0, 3),
                'away_goals' => rand(0, 3),
                'played_at' => now()
            ]);
        }
    }

    private function createFixtures(): void
    {
        $this->postJson('/api/fixtures');
    }

    private function simulateWeeks(array $weeks): void
    {
        foreach ($weeks as $week) {
            $this->postJson('/api/simulate/next-week');
        }
    }

    private function simulateMatchesWithResults(array $results): void
    {
        $teams = Team::all()->keyBy('name');

        foreach ($results as $index => $result) {
            $homeTeam = $teams[$result['home']];
            $awayTeam = $teams[$result['away']];

            GameMatch::create([
                'home_team_id' => $homeTeam->id,
                'away_team_id' => $awayTeam->id,
                'week' => intval($index / 2) + 1,
                'is_played' => true,
                'home_goals' => $result['home_goals'],
                'away_goals' => $result['away_goals'],
                'played_at' => now()
            ]);
        }
    }
}
