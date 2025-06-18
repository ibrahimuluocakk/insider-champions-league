<?php

namespace Tests\Unit\Repositories;

use App\Models\GameMatch;
use App\Models\Team;
use App\Repositories\FixtureRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FixtureRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected FixtureRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new FixtureRepository(new GameMatch());
        $this->seedTeams();
    }

    public function test_get_matches_by_week_returns_correct_matches()
    {
        $teams = Team::all();

        GameMatch::create([
            'home_team_id' => $teams[0]->id,
            'away_team_id' => $teams[1]->id,
            'week' => 1,
            'is_played' => false
        ]);

        GameMatch::create([
            'home_team_id' => $teams[2]->id,
            'away_team_id' => $teams[3]->id,
            'week' => 2,
            'is_played' => false
        ]);

        $week1Matches = $this->repository->getMatchesByWeek(1);
        $week2Matches = $this->repository->getMatchesByWeek(2);

        $this->assertCount(1, $week1Matches);
        $this->assertCount(1, $week2Matches);
        $this->assertEquals(1, $week1Matches->first()->week);
        $this->assertEquals(2, $week2Matches->first()->week);
    }

    public function test_get_matches_by_week_returns_empty_for_nonexistent_week()
    {
        $matches = $this->repository->getMatchesByWeek(99);

        $this->assertCount(0, $matches);
    }

    public function test_get_first_unplayed_match_returns_earliest_unplayed()
    {
        $teams = Team::all();

        GameMatch::create([
            'home_team_id' => $teams[0]->id,
            'away_team_id' => $teams[1]->id,
            'week' => 2,
            'is_played' => false
        ]);

        GameMatch::create([
            'home_team_id' => $teams[2]->id,
            'away_team_id' => $teams[3]->id,
            'week' => 1,
            'is_played' => false
        ]);

        GameMatch::create([
            'home_team_id' => $teams[0]->id,
            'away_team_id' => $teams[2]->id,
            'week' => 1,
            'is_played' => true,
            'home_goals' => 2,
            'away_goals' => 1,
            'played_at' => now()
        ]);

        $firstUnplayed = $this->repository->getFirstUnplayedMatch();

        $this->assertNotNull($firstUnplayed);
        $this->assertEquals(1, $firstUnplayed->week);
        $this->assertFalse($firstUnplayed->is_played);
    }

    public function test_get_first_unplayed_match_returns_null_when_all_played()
    {
        $teams = Team::all();

        GameMatch::create([
            'home_team_id' => $teams[0]->id,
            'away_team_id' => $teams[1]->id,
            'week' => 1,
            'is_played' => true,
            'home_goals' => 2,
            'away_goals' => 1,
            'played_at' => now()
        ]);

        $firstUnplayed = $this->repository->getFirstUnplayedMatch();

        $this->assertNull($firstUnplayed);
    }

    public function test_get_first_unplayed_match_skips_played_matches()
    {
        $teams = Team::all();

        // Create matches first
        GameMatch::create([
            'home_team_id' => $teams[0]->id,
            'away_team_id' => $teams[1]->id,
            'week' => 1,
            'is_played' => false
        ]);

        GameMatch::create([
            'home_team_id' => $teams[2]->id,
            'away_team_id' => $teams[3]->id,
            'week' => 2,
            'is_played' => false
        ]);

        // Now update week 1 matches to played
        GameMatch::where('week', 1)->update([
            'is_played' => true,
            'home_goals' => 2,
            'away_goals' => 1,
            'played_at' => now()
        ]);

        $match = $this->repository->getFirstUnplayedMatch();

        $this->assertNotNull($match);
        $this->assertEquals(2, $match->week);
        $this->assertFalse($match->is_played);
    }

    public function test_get_all_with_teams_includes_relationships()
    {
        $teams = Team::all();

        GameMatch::create([
            'home_team_id' => $teams[0]->id,
            'away_team_id' => $teams[1]->id,
            'week' => 1,
            'is_played' => false
        ]);

        $matches = $this->repository->getAllWithTeams();

        $this->assertCount(1, $matches);
        $this->assertTrue($matches->first()->relationLoaded('homeTeam'));
        $this->assertTrue($matches->first()->relationLoaded('awayTeam'));
    }

    public function test_get_all_with_teams_orders_by_week()
    {
        $teams = Team::all();

        GameMatch::create([
            'home_team_id' => $teams[0]->id,
            'away_team_id' => $teams[1]->id,
            'week' => 1,
            'is_played' => false
        ]);

        $matches = $this->repository->getAllWithTeams();

        $previousWeek = 0;
        foreach ($matches as $match) {
            $this->assertGreaterThanOrEqual($previousWeek, $match->week);
            $previousWeek = $match->week;
        }
    }

    public function test_create_many_creates_multiple_fixtures()
    {
        $teams = Team::all();

        $fixtures = [
            [
                'home_team_id' => $teams[0]->id,
                'away_team_id' => $teams[1]->id,
                'week' => 1,
                'is_played' => false
            ],
            [
                'home_team_id' => $teams[2]->id,
                'away_team_id' => $teams[3]->id,
                'week' => 1,
                'is_played' => false
            ]
        ];

        $created = $this->repository->createMany($fixtures);

        $this->assertCount(2, $created);
        $this->assertDatabaseCount('matches', 2);
    }

    public function test_delete_all_removes_all_fixtures()
    {
        $teams = Team::all();

        GameMatch::create([
            'home_team_id' => $teams[0]->id,
            'away_team_id' => $teams[1]->id,
            'week' => 1,
            'is_played' => false
        ]);

        $this->assertDatabaseCount('matches', 1);

        $result = $this->repository->deleteAll();

        $this->assertTrue($result);
        $this->assertDatabaseCount('matches', 0);
    }

    public function test_get_played_matches_returns_only_played_matches()
    {
        $teams = Team::all();

        // Create played match
        GameMatch::create([
            'home_team_id' => $teams[0]->id,
            'away_team_id' => $teams[1]->id,
            'week' => 1,
            'is_played' => true,
            'home_goals' => 2,
            'away_goals' => 1,
            'played_at' => now()
        ]);

        // Create unplayed match
        GameMatch::create([
            'home_team_id' => $teams[2]->id,
            'away_team_id' => $teams[3]->id,
            'week' => 2,
            'is_played' => false
        ]);

        $playedMatches = $this->repository->getPlayedMatches();

        $this->assertCount(1, $playedMatches);
        $this->assertTrue($playedMatches->first()->is_played);
        $this->assertTrue($playedMatches->first()->relationLoaded('homeTeam'));
        $this->assertTrue($playedMatches->first()->relationLoaded('awayTeam'));
    }

    public function test_get_played_matches_returns_empty_when_no_played_matches()
    {
        $teams = Team::all();

        GameMatch::create([
            'home_team_id' => $teams[0]->id,
            'away_team_id' => $teams[1]->id,
            'week' => 1,
            'is_played' => false
        ]);

        $playedMatches = $this->repository->getPlayedMatches();

        $this->assertCount(0, $playedMatches);
    }

    private function seedTeams(): void
    {
        Team::create(['name' => 'Liverpool', 'power' => 90, 'home_advantage' => 6]);
        Team::create(['name' => 'Manchester United', 'power' => 85, 'home_advantage' => 5]);
        Team::create(['name' => 'Arsenal', 'power' => 80, 'home_advantage' => 5]);
        Team::create(['name' => 'Chelsea', 'power' => 75, 'home_advantage' => 4]);
    }
}
