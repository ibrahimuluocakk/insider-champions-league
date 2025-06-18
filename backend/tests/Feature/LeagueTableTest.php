<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LeagueTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTeams();
    }

    #[Test]
    public function it_can_get_league_table()
    {
        $response = $this->getJson('/api/league-table');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'standings' => [
                            '*' => [
                                'team_id',
                                'team_name',
                                'matches_played',
                                'wins',
                                'draws',
                                'losses',
                                'goals_for',
                                'goals_against',
                                'goal_difference',
                                'points'
                            ]
                        ],
                        'total_teams',
                        'matches_played'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('League table retrieved successfully', $response->json('message'));
    }

    #[Test]
    public function it_returns_proper_league_table_structure()
    {
        $response = $this->getJson('/api/league-table');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals(4, $data['total_teams']);
        $this->assertEquals(0, $data['matches_played']);

        foreach ($data['standings'] as $standing) {
            $this->assertEquals(0, $standing['matches_played']);
            $this->assertEquals(0, $standing['points']);
            $this->assertEquals(0, $standing['wins']);
            $this->assertEquals(0, $standing['draws']);
            $this->assertEquals(0, $standing['losses']);
        }
    }

    #[Test]
    public function it_calculates_points_correctly()
    {
        $teams = Team::all();

        // Create matches with known results
        GameMatch::create([
            'home_team_id' => $teams[0]->id, // Liverpool
            'away_team_id' => $teams[1]->id, // Man United
            'week' => 1,
            'home_goals' => 3,
            'away_goals' => 1,
            'is_played' => true,
            'played_at' => now()
        ]);

        GameMatch::create([
            'home_team_id' => $teams[2]->id, // Arsenal
            'away_team_id' => $teams[3]->id, // Chelsea
            'week' => 1,
            'home_goals' => 1,
            'away_goals' => 1,
            'is_played' => true,
            'played_at' => now()
        ]);

        $response = $this->getJson('/api/league-table');
        $standings = $response->json('data.standings');

        // Liverpool should be first (3 points, +2 GD)
        $liverpool = collect($standings)->firstWhere('team_name', 'Liverpool');
        $this->assertEquals(3, $liverpool['points']);
        $this->assertEquals(1, $liverpool['wins']);
        $this->assertEquals(0, $liverpool['draws']);
        $this->assertEquals(0, $liverpool['losses']);
        $this->assertEquals(2, $liverpool['goal_difference']);

        // Arsenal and Chelsea should have 1 point each
        $arsenal = collect($standings)->firstWhere('team_name', 'Arsenal');
        $chelsea = collect($standings)->firstWhere('team_name', 'Chelsea');

        $this->assertEquals(1, $arsenal['points']);
        $this->assertEquals(1, $chelsea['points']);
        $this->assertEquals(1, $arsenal['draws']);
        $this->assertEquals(1, $chelsea['draws']);

        // Man United should have 0 points
        $manUnited = collect($standings)->firstWhere('team_name', 'Manchester United');
        $this->assertEquals(0, $manUnited['points']);
        $this->assertEquals(1, $manUnited['losses']);
    }

    #[Test]
    public function it_calculates_goal_difference_correctly()
    {
        $teams = Team::all();

        // Create matches to test sorting
        GameMatch::create([
            'home_team_id' => $teams[0]->id, // Liverpool
            'away_team_id' => $teams[1]->id, // Man United
            'week' => 1,
            'home_goals' => 2,
            'away_goals' => 0,
            'is_played' => true,
            'played_at' => now()
        ]);

        GameMatch::create([
            'home_team_id' => $teams[2]->id, // Arsenal
            'away_team_id' => $teams[3]->id, // Chelsea
            'week' => 1,
            'home_goals' => 1,
            'away_goals' => 0,
            'is_played' => true,
            'played_at' => now()
        ]);

        $response = $this->getJson('/api/league-table');
        $standings = $response->json('data.standings');

        // Both Liverpool and Arsenal have 3 points, but Liverpool has better GD
        $this->assertEquals('Liverpool', $standings[0]['team_name']);
        $this->assertEquals(3, $standings[0]['points']);
        $this->assertEquals(2, $standings[0]['goal_difference']);

        $this->assertEquals('Arsenal', $standings[1]['team_name']);
        $this->assertEquals(3, $standings[1]['points']);
        $this->assertEquals(1, $standings[1]['goal_difference']);
    }

    public function test_league_table_with_multiple_matches_per_team()
    {
        $teams = Team::all();

        // Create multiple matches for one team
        GameMatch::create([
            'home_team_id' => $teams[0]->id, // Liverpool home
            'away_team_id' => $teams[1]->id, // vs Man United
            'week' => 1,
            'home_goals' => 2,
            'away_goals' => 1,
            'is_played' => true,
            'played_at' => now()
        ]);

        GameMatch::create([
            'home_team_id' => $teams[2]->id, // Arsenal home
            'away_team_id' => $teams[0]->id, // vs Liverpool away
            'week' => 2,
            'home_goals' => 0,
            'away_goals' => 3,
            'is_played' => true,
            'played_at' => now()
        ]);

        $response = $this->getJson('/api/league-table');
        $standings = $response->json('data.standings');

        $liverpool = collect($standings)->firstWhere('team_name', 'Liverpool');

        $this->assertEquals(2, $liverpool['matches_played']);
        $this->assertEquals(6, $liverpool['points']); // 2 wins
        $this->assertEquals(2, $liverpool['wins']);
        $this->assertEquals(0, $liverpool['draws']);
        $this->assertEquals(0, $liverpool['losses']);
        $this->assertEquals(5, $liverpool['goals_for']); // 2 + 3
        $this->assertEquals(1, $liverpool['goals_against']);
        $this->assertEquals(4, $liverpool['goal_difference']);
    }

    public function test_league_table_ignores_unplayed_matches()
    {
        $teams = Team::all();

        // Create one played and one unplayed match
        GameMatch::create([
            'home_team_id' => $teams[0]->id,
            'away_team_id' => $teams[1]->id,
            'week' => 1,
            'home_goals' => 2,
            'away_goals' => 1,
            'is_played' => true,
            'played_at' => now()
        ]);

        GameMatch::create([
            'home_team_id' => $teams[2]->id,
            'away_team_id' => $teams[3]->id,
            'week' => 2,
            'home_goals' => null,
            'away_goals' => null,
            'is_played' => false,
            'played_at' => null
        ]);

        $response = $this->getJson('/api/league-table');
        $data = $response->json('data');

        $this->assertEquals(1, $data['matches_played']); // Only played matches counted

        $arsenal = collect($data['standings'])->firstWhere('team_name', 'Arsenal');
        $chelsea = collect($data['standings'])->firstWhere('team_name', 'Chelsea');

        // Arsenal and Chelsea should have no stats from unplayed match
        $this->assertEquals(0, $arsenal['matches_played']);
        $this->assertEquals(0, $chelsea['matches_played']);
    }

    public function test_league_table_handles_all_teams_equal_points()
    {
        $teams = Team::all();

        // Create matches where all teams get 1 point (draws)
        GameMatch::create([
            'home_team_id' => $teams[0]->id,
            'away_team_id' => $teams[1]->id,
            'week' => 1,
            'home_goals' => 1,
            'away_goals' => 1,
            'is_played' => true,
            'played_at' => now()
        ]);

        GameMatch::create([
            'home_team_id' => $teams[2]->id,
            'away_team_id' => $teams[3]->id,
            'week' => 1,
            'home_goals' => 1,
            'away_goals' => 1,
            'is_played' => true,
            'played_at' => now()
        ]);

        $response = $this->getJson('/api/league-table');
        $standings = $response->json('data.standings');

        // All teams should have 1 point and same goal difference
        foreach ($standings as $standing) {
            $this->assertEquals(1, $standing['points']);
            $this->assertEquals(0, $standing['goal_difference']);
            $this->assertEquals(1, $standing['draws']);
        }
    }

    private function seedTeams(): void
    {
        Team::create(['name' => 'Liverpool', 'power' => 90, 'home_advantage' => 6]);
        Team::create(['name' => 'Manchester United', 'power' => 85, 'home_advantage' => 5]);
        Team::create(['name' => 'Arsenal', 'power' => 80, 'home_advantage' => 5]);
        Team::create(['name' => 'Chelsea', 'power' => 75, 'home_advantage' => 4]);
    }
}
