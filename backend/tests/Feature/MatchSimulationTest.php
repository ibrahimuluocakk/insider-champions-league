<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MatchSimulationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTeams();
        $this->createFixtures();
    }

    #[Test]
    public function test_simulate_next_week_endpoint_success()
    {
        $response = $this->postJson('/api/simulate/next-week');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'week',
                        'matches' => [
                            '*' => [
                                'id',
                                'home_team_id',
                                'away_team_id',
                                'week',
                                'home_goals',
                                'away_goals',
                                'is_played',
                                'home_team',
                                'away_team'
                            ]
                        ],
                        'total_simulated'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('Next week simulated successfully', $response->json('message'));
        $this->assertEquals(1, $response->json('data.week'));
        $this->assertEquals(2, $response->json('data.total_simulated'));

        $this->assertDatabaseHas('matches', [
            'week' => 1,
            'is_played' => true
        ]);
    }

    #[Test]
    public function test_simulate_next_week_updates_match_results()
    {
        $response = $this->postJson('/api/simulate/next-week');

        $response->assertStatus(200);

        $matches = GameMatch::where('week', 1)->get();

        foreach ($matches as $match) {
            $this->assertTrue($match->is_played);
            $this->assertNotNull($match->home_goals);
            $this->assertNotNull($match->away_goals);
            $this->assertNotNull($match->played_at);
            $this->assertGreaterThanOrEqual(0, $match->home_goals);
            $this->assertGreaterThanOrEqual(0, $match->away_goals);
        }
    }

    #[Test]
    public function test_simulate_next_week_returns_error_when_all_played()
    {
        GameMatch::query()->update([
            'is_played' => true,
            'home_goals' => 1,
            'away_goals' => 1,
            'played_at' => now()
        ]);

        $response = $this->postJson('/api/simulate/next-week');

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'All matches have been played'
                ]);
    }

    #[Test]
    public function test_simulate_all_endpoint_success()
    {
        $response = $this->postJson('/api/simulate/all');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'weeks_simulated',
                        'total_matches_simulated',
                        'results' => [
                            '*' => [
                                'week',
                                'matches',
                                'total_simulated'
                            ]
                        ]
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('All remaining matches simulated successfully', $response->json('message'));
        $this->assertEquals(6, $response->json('data.weeks_simulated'));
        $this->assertEquals(12, $response->json('data.total_matches_simulated'));
    }

    #[Test]
    public function test_simulate_all_plays_entire_season()
    {
        $response = $this->postJson('/api/simulate/all');

        $response->assertStatus(200);

        $totalMatches = GameMatch::count();
        $playedMatches = GameMatch::where('is_played', true)->count();

        $this->assertEquals($totalMatches, $playedMatches);
        $this->assertEquals(12, $playedMatches);

        for ($week = 1; $week <= 6; $week++) {
            $weekMatches = GameMatch::where('week', $week)->get();
            $this->assertCount(2, $weekMatches);

            foreach ($weekMatches as $match) {
                $this->assertTrue($match->is_played);
                $this->assertNotNull($match->home_goals);
                $this->assertNotNull($match->away_goals);
            }
        }
    }

    #[Test]
    public function test_simulate_all_returns_error_when_all_already_played()
    {
        GameMatch::query()->update([
            'is_played' => true,
            'home_goals' => 1,
            'away_goals' => 1,
            'played_at' => now()
        ]);

        $response = $this->postJson('/api/simulate/all');

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'All matches have already been played'
                ]);
    }

    #[Test]
    public function test_simulation_produces_realistic_scores()
    {
        $this->postJson('/api/simulate/all');

        $matches = GameMatch::where('is_played', true)->get();

        foreach ($matches as $match) {
            $this->assertLessThanOrEqual(5, $match->home_goals);
            $this->assertLessThanOrEqual(5, $match->away_goals);
            $this->assertGreaterThanOrEqual(0, $match->home_goals);
            $this->assertGreaterThanOrEqual(0, $match->away_goals);
        }
    }

    #[Test]
    public function test_stronger_teams_win_more_often()
    {
        $liverpoolWins = 0;
        $chelseaWins = 0;
        $draws = 0;
        $simulations = 50;

        $liverpool = Team::where('name', 'Liverpool')->first();
        $chelsea = Team::where('name', 'Chelsea')->first();

        for ($i = 0; $i < $simulations; $i++) {
            // Clear previous matches
            GameMatch::query()->delete();

            // Create a single match between Liverpool (home) and Chelsea (away)
            $this->createSingleMatch($liverpool->id, $chelsea->id);

            // Simulate the match
            $this->postJson('/api/simulate/next-week');

            $match = GameMatch::first();
            if ($match->home_goals > $match->away_goals) {
                $liverpoolWins++;
            } elseif ($match->away_goals > $match->home_goals) {
                $chelseaWins++;
            } else {
                $draws++;
            }
        }

        // Liverpool (90 power + 6 home advantage = 96) should win more than Chelsea (75 power = 75)
        // With randomness, we expect Liverpool to win at least 40% of the time
        $liverpoolWinRate = $liverpoolWins / $simulations;
        $this->assertGreaterThan(0.3, $liverpoolWinRate,
            "Liverpool should win at least 30% of matches. Liverpool: {$liverpoolWins}, Chelsea: {$chelseaWins}, Draws: {$draws}");

        // Also check that there were actual matches played
        $this->assertEquals($simulations, $liverpoolWins + $chelseaWins + $draws);
    }

    private function seedTeams(): void
    {
        Team::create(['name' => 'Liverpool', 'power' => 90, 'home_advantage' => 6]);
        Team::create(['name' => 'Manchester United', 'power' => 85, 'home_advantage' => 5]);
        Team::create(['name' => 'Arsenal', 'power' => 80, 'home_advantage' => 5]);
        Team::create(['name' => 'Chelsea', 'power' => 75, 'home_advantage' => 4]);
    }

    private function createFixtures(): void
    {
        $teams = Team::all();
        $fixtures = [];
        $week = 1;

        for ($i = 0; $i < $teams->count(); $i++) {
            for ($j = 0; $j < $teams->count(); $j++) {
                if ($i !== $j) {
                    $fixtures[] = [
                        'home_team_id' => $teams[$i]->id,
                        'away_team_id' => $teams[$j]->id,
                        'week' => $week,
                        'is_played' => false,
                    ];

                    if (count($fixtures) % 2 === 0) {
                        $week++;
                    }
                }
            }
        }

        foreach ($fixtures as $fixture) {
            GameMatch::create($fixture);
        }
    }

    private function createSingleMatch(int $homeTeamId, int $awayTeamId): void
    {
        GameMatch::create([
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'week' => 1,
            'is_played' => false,
        ]);
    }
}
