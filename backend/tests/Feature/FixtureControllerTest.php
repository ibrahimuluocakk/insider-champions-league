<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FixtureControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test teams
        $this->createTestTeams();
    }

    /** @test */
    #[Test]
    public function it_can_create_fixtures()
    {
        $response = $this->postJson('/api/fixtures');

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        '*' => [
                            'id',
                            'week',
                            'home_team' => ['id', 'name', 'power'],
                            'away_team' => ['id', 'name', 'power'],
                            'is_played',
                            'home_goals',
                            'away_goals',
                            'score',
                            'result',
                            'played_at',
                            'created_at'
                        ]
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Fixtures created successfully'
                ]);
    }

    /** @test */
    #[Test]
    public function it_creates_exactly_12_fixtures()
    {
        $this->postJson('/api/fixtures');

        $this->assertDatabaseCount('matches', 12);
    }

    /** @test */
    #[Test]
    public function it_creates_fixtures_across_6_weeks()
    {
        $this->postJson('/api/fixtures');

        // Check that we have fixtures for weeks 1-6
        for ($week = 1; $week <= 6; $week++) {
            $this->assertDatabaseHas('matches', ['week' => $week]);
        }
    }

    /** @test */
    #[Test]
    public function it_creates_2_matches_per_week()
    {
        $this->postJson('/api/fixtures');

        // Each week should have exactly 2 matches
        for ($week = 1; $week <= 6; $week++) {
            $matchCount = GameMatch::where('week', $week)->count();
            $this->assertEquals(2, $matchCount, "Week {$week} should have 2 matches");
        }
    }

    /** @test */
    #[Test]
    public function it_ensures_each_team_plays_once_per_week()
    {
        $this->postJson('/api/fixtures');

        $teams = Team::all();

        // For each week, each team should appear only once
        for ($week = 1; $week <= 6; $week++) {
            foreach ($teams as $team) {
                $homeCount = GameMatch::where('week', $week)
                                    ->where('home_team_id', $team->id)
                                    ->count();

                $awayCount = GameMatch::where('week', $week)
                                    ->where('away_team_id', $team->id)
                                    ->count();

                $totalCount = $homeCount + $awayCount;

                $this->assertEquals(1, $totalCount,
                    "Team {$team->name} should play exactly once in week {$week}");
            }
        }
    }

    /** @test */
    #[Test]
    public function it_ensures_each_team_plays_6_matches_total()
    {
        $this->postJson('/api/fixtures');

        $teams = Team::all();

        foreach ($teams as $team) {
            $homeMatches = GameMatch::where('home_team_id', $team->id)->count();
            $awayMatches = GameMatch::where('away_team_id', $team->id)->count();
            $totalMatches = $homeMatches + $awayMatches;

            $this->assertEquals(6, $totalMatches,
                "Team {$team->name} should play exactly 6 matches");
        }
    }

    /** @test */
    #[Test]
    public function it_ensures_each_team_plays_3_home_and_3_away_matches()
    {
        $this->postJson('/api/fixtures');

        $teams = Team::all();

        foreach ($teams as $team) {
            $homeMatches = GameMatch::where('home_team_id', $team->id)->count();
            $awayMatches = GameMatch::where('away_team_id', $team->id)->count();

            $this->assertEquals(3, $homeMatches,
                "Team {$team->name} should play exactly 3 home matches");

            $this->assertEquals(3, $awayMatches,
                "Team {$team->name} should play exactly 3 away matches");
        }
    }

    /** @test */
    #[Test]
    public function it_creates_proper_round_robin_fixtures()
    {
        $this->postJson('/api/fixtures');

        $teams = Team::all()->pluck('id')->toArray();

        // Check that every team plays every other team twice (home and away)
        foreach ($teams as $team1) {
            foreach ($teams as $team2) {
                if ($team1 !== $team2) {
                    // Team1 vs Team2 (Team1 home)
                    $this->assertDatabaseHas('matches', [
                        'home_team_id' => $team1,
                        'away_team_id' => $team2
                    ]);

                    // Team2 vs Team1 (Team2 home)
                    $this->assertDatabaseHas('matches', [
                        'home_team_id' => $team2,
                        'away_team_id' => $team1
                    ]);
                }
            }
        }
    }

    /** @test */
    #[Test]
    public function it_clears_existing_fixtures_when_creating_new_ones()
    {
        // Create initial fixtures
        $this->postJson('/api/fixtures');
        $this->assertDatabaseCount('matches', 12);

        // Create fixtures again
        $this->postJson('/api/fixtures');
        $this->assertDatabaseCount('matches', 12); // Still 12, not 24
    }

    /** @test */
    #[Test]
    public function it_can_retrieve_all_fixtures()
    {
        // Create fixtures first
        $this->postJson('/api/fixtures');

        $response = $this->getJson('/api/fixtures');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        '*' => [
                            'id',
                            'week',
                            'home_team' => ['id', 'name', 'power'],
                            'away_team' => ['id', 'name', 'power'],
                            'is_played',
                            'home_goals',
                            'away_goals',
                            'score',
                            'result'
                        ]
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Fixtures retrieved successfully'
                ]);

        $data = $response->json('data');
        $this->assertCount(12, $data);
    }

    /** @test */
    #[Test]
    public function it_returns_empty_fixtures_when_none_exist()
    {
        $response = $this->getJson('/api/fixtures');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Fixtures retrieved successfully',
                    'data' => []
                ]);
    }

    /** @test */
    #[Test]
    public function it_fails_when_not_exactly_4_teams()
    {
        // Clear teams and create only 3
        Team::truncate();
        Team::factory()->count(3)->create();

        $response = $this->postJson('/api/fixtures');

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Exactly 4 teams are required for fixture generation'
                ]);
    }

    private function createTestTeams(): void
    {
        $teams = [
            ['name' => 'Liverpool', 'power' => 90, 'home_advantage' => 6],
            ['name' => 'Manchester United', 'power' => 85, 'home_advantage' => 5],
            ['name' => 'Arsenal', 'power' => 80, 'home_advantage' => 5],
            ['name' => 'Chelsea', 'power' => 75, 'home_advantage' => 4],
        ];

        foreach ($teams as $team) {
            Team::create($team);
        }
    }
}
