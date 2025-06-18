<?php

namespace Tests\Unit\Services;

use App\Contracts\FixtureRepositoryInterface;
use App\Contracts\TeamRepositoryInterface;
use App\Models\GameMatch;
use App\Models\Team;
use App\Services\LeagueTableService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LeagueTableServiceTest extends TestCase
{
    protected $fixtureRepository;
    protected $teamRepository;
    protected $leagueTableService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureRepository = Mockery::mock(FixtureRepositoryInterface::class);
        $this->teamRepository = Mockery::mock(TeamRepositoryInterface::class);

        $this->leagueTableService = new LeagueTableService(
            $this->fixtureRepository,
            $this->teamRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_generates_league_table_with_played_matches()
    {
        $teams = $this->createRealTeams();
        $matches = $this->createRealMatches();

        $this->teamRepository
            ->shouldReceive('all')
            ->once()
            ->andReturn($teams);

        $this->fixtureRepository
            ->shouldReceive('getPlayedMatches')
            ->once()
            ->andReturn($matches);

        $result = $this->leagueTableService->getLeagueTable();

        $this->assertArrayHasKey('standings', $result);
        $this->assertArrayHasKey('total_teams', $result);
        $this->assertArrayHasKey('matches_played', $result);
        $this->assertEquals(2, $result['total_teams']);
        $this->assertEquals(1, $result['matches_played']);
        $this->assertCount(2, $result['standings']);
    }

    #[Test]
    public function it_calculates_points_correctly()
    {
        $teams = $this->createRealTeams();

        $match1 = new GameMatch([
            'home_team_id' => 1,
            'away_team_id' => 2,
            'home_goals' => 3,
            'away_goals' => 1,
            'is_played' => true
        ]);

        $match2 = new GameMatch([
            'home_team_id' => 2,
            'away_team_id' => 1,
            'home_goals' => 1,
            'away_goals' => 1,
            'is_played' => true
        ]);

        $matches = new Collection([$match1, $match2]);

        $this->teamRepository->shouldReceive('all')->andReturn($teams);
        $this->fixtureRepository->shouldReceive('getPlayedMatches')->andReturn($matches);

        $result = $this->leagueTableService->getLeagueTable();
        $standings = $result['standings'];

        $team1Stats = collect($standings)->firstWhere('team_id', 1);
        $team2Stats = collect($standings)->firstWhere('team_id', 2);

        $this->assertEquals(4, $team1Stats['points']); // 3 + 1 = 4 points
        $this->assertEquals(1, $team2Stats['points']); // 1 point
    }

    #[Test]
    public function it_calculates_goal_difference_correctly()
    {
        $teams = $this->createRealTeams();

        $match = new GameMatch([
            'home_team_id' => 1,
            'away_team_id' => 2,
            'home_goals' => 3,
            'away_goals' => 1,
            'is_played' => true
        ]);

        $matches = new Collection([$match]);

        $this->teamRepository->shouldReceive('all')->andReturn($teams);
        $this->fixtureRepository->shouldReceive('getPlayedMatches')->andReturn($matches);

        $result = $this->leagueTableService->getLeagueTable();
        $team1Stats = collect($result['standings'])->firstWhere('team_id', 1);

        $this->assertEquals(3, $team1Stats['goals_for']);
        $this->assertEquals(1, $team1Stats['goals_against']);
        $this->assertEquals(2, $team1Stats['goal_difference']);
    }

    #[Test]
    public function it_sorts_teams_by_points_then_goal_difference()
    {
        $teams = $this->createRealTeams();

        // Team 1 plays 2 home matches: wins both = 6 points
        $match1 = new GameMatch([
            'home_team_id' => 1,
            'away_team_id' => 2,
            'home_goals' => 2,
            'away_goals' => 1,  // Team 1 wins: +3 points
            'is_played' => true
        ]);

        $match2 = new GameMatch([
            'home_team_id' => 1,
            'away_team_id' => 2,
            'home_goals' => 1,
            'away_goals' => 0,  // Team 1 wins: +3 points
            'is_played' => true
        ]);

        // Team 2 plays 1 away match: loses = 0 points
        $match3 = new GameMatch([
            'home_team_id' => 2,
            'away_team_id' => 1,
            'home_goals' => 1,
            'away_goals' => 0,  // Team 2 wins: +3 points
            'is_played' => true
        ]);

        $matches = new Collection([$match1, $match2, $match3]);

        $this->teamRepository->shouldReceive('all')->andReturn($teams);
        $this->fixtureRepository->shouldReceive('getPlayedMatches')->andReturn($matches);

        $result = $this->leagueTableService->getLeagueTable();
        $standings = $result['standings'];

        // Debug: Let's see what we actually get
        $team1Stats = collect($standings)->firstWhere('team_id', 1);
        $team2Stats = collect($standings)->firstWhere('team_id', 2);

        // Team 1: 2 home wins = 6 points, goals for: 3, goals against: 1, GD: +2
        // BUT Team 1 also plays away in match3 and loses: goals for: 0, goals against: 1
        // So Team 1 total: goals for: 3, goals against: 2, GD: +1
        $this->assertEquals(1, $standings[0]['team_id']);
        $this->assertEquals(6, $standings[0]['points']);
        $this->assertEquals(1, $standings[0]['goal_difference']); // 3 - 2 = 1

        // Team 2: 1 home win = 3 points, 2 away losses = 0 points, total = 3 points
        // Team 2 goals for: 1, goals against: 2, GD: -1
        $this->assertEquals(2, $standings[1]['team_id']);
        $this->assertEquals(3, $standings[1]['points']);
        $this->assertEquals(-1, $standings[1]['goal_difference']);
    }

    #[Test]
    public function it_handles_empty_fixtures()
    {
        $teams = $this->createRealTeams();
        $matches = new Collection();

        $this->teamRepository->shouldReceive('all')->andReturn($teams);
        $this->fixtureRepository->shouldReceive('getPlayedMatches')->andReturn($matches);

        $result = $this->leagueTableService->getLeagueTable();

        foreach ($result['standings'] as $standing) {
            $this->assertEquals(0, $standing['matches_played']);
            $this->assertEquals(0, $standing['points']);
            $this->assertEquals(0, $standing['goals_for']);
            $this->assertEquals(0, $standing['goals_against']);
        }
    }

    #[Test]
    public function it_ignores_unplayed_matches()
    {
        $teams = $this->createRealTeams();
        $matches = new Collection();

        $this->teamRepository->shouldReceive('all')->andReturn($teams);
        $this->fixtureRepository->shouldReceive('getPlayedMatches')->andReturn($matches);

        $result = $this->leagueTableService->getLeagueTable();

        foreach ($result['standings'] as $standing) {
            $this->assertEquals(0, $standing['matches_played']);
            $this->assertEquals(0, $standing['points']);
            $this->assertEquals(0, $standing['goals_for']);
            $this->assertEquals(0, $standing['goals_against']);
        }
    }

    #[Test]
    public function it_calculates_win_draw_loss_correctly()
    {
        $teams = $this->createRealTeams();

        $match1 = new GameMatch([
            'home_team_id' => 1,
            'away_team_id' => 2,
            'home_goals' => 2,
            'away_goals' => 1,
            'is_played' => true
        ]);

        $match2 = new GameMatch([
            'home_team_id' => 2,
            'away_team_id' => 1,
            'home_goals' => 1,
            'away_goals' => 1,
            'is_played' => true
        ]);

        $match3 = new GameMatch([
            'home_team_id' => 1,
            'away_team_id' => 2,
            'home_goals' => 0,
            'away_goals' => 2,
            'is_played' => true
        ]);

        $matches = new Collection([$match1, $match2, $match3]);

        $this->teamRepository->shouldReceive('all')->andReturn($teams);
        $this->fixtureRepository->shouldReceive('getPlayedMatches')->andReturn($matches);

        $result = $this->leagueTableService->getLeagueTable();
        $team1Stats = collect($result['standings'])->firstWhere('team_id', 1);

        $this->assertEquals(3, $team1Stats['matches_played']);
        $this->assertEquals(1, $team1Stats['wins']);
        $this->assertEquals(1, $team1Stats['draws']);
        $this->assertEquals(1, $team1Stats['losses']);
        $this->assertEquals(4, $team1Stats['points']); // 3 + 1 + 0 = 4
    }

    private function createRealTeams(): Collection
    {
        $team1 = new Team(['name' => 'Team 1', 'power' => 80, 'home_advantage' => 5]);
        $team1->id = 1;

        $team2 = new Team(['name' => 'Team 2', 'power' => 75, 'home_advantage' => 4]);
        $team2->id = 2;

        return new Collection([$team1, $team2]);
    }

    private function createRealMatches(): Collection
    {
        $match = new GameMatch([
            'home_team_id' => 1,
            'away_team_id' => 2,
            'home_goals' => 2,
            'away_goals' => 1,
            'is_played' => true
        ]);

        return new Collection([$match]);
    }
}
