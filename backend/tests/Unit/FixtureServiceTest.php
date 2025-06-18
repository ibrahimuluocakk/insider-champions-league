<?php

namespace Tests\Unit;

use App\Contracts\FixtureRepositoryInterface;
use App\Contracts\TeamRepositoryInterface;
use App\Models\GameMatch;
use App\Models\Team;
use App\Services\FixtureService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FixtureServiceTest extends TestCase
{
    private FixtureService $fixtureService;
    private $teamRepositoryMock;
    private $fixtureRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teamRepositoryMock = Mockery::mock(TeamRepositoryInterface::class);
        $this->fixtureRepositoryMock = Mockery::mock(FixtureRepositoryInterface::class);

        $this->fixtureService = new FixtureService(
            $this->teamRepositoryMock,
            $this->fixtureRepositoryMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_generates_fixtures_for_4_teams()
    {
        $teams = $this->createTeamCollection();

        $this->teamRepositoryMock
            ->shouldReceive('all')
            ->once()
            ->andReturn($teams);

        $this->fixtureRepositoryMock
            ->shouldReceive('deleteAll')
            ->once();

        $mockFixtures = $this->createMockFixtureCollection();

        $this->fixtureRepositoryMock
            ->shouldReceive('createMany')
            ->once()
            ->with(Mockery::on(function ($fixtures) {
                return count($fixtures) === 12;
            }))
            ->andReturn($mockFixtures);

        $result = $this->fixtureService->generateFixtures();

        $this->assertCount(12, $result);
    }

    #[Test]
    public function it_throws_exception_when_not_exactly_4_teams()
    {
        $teams = new Collection([
            $this->createTeam(1, 'Team 1'),
            $this->createTeam(2, 'Team 2'),
            $this->createTeam(3, 'Team 3')
        ]);

        $this->teamRepositoryMock
            ->shouldReceive('all')
            ->once()
            ->andReturn($teams);

        $this->fixtureRepositoryMock
            ->shouldReceive('deleteAll')
            ->once();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Exactly 4 teams are required for fixture generation');

        $this->fixtureService->generateFixtures();
    }

    #[Test]
    public function it_retrieves_all_fixtures()
    {
        $fixtures = new Collection([
            $this->createMockMatch(1, 1, 2, 1),
            $this->createMockMatch(2, 3, 4, 1)
        ]);

        $this->fixtureRepositoryMock
            ->shouldReceive('getAllWithTeams')
            ->once()
            ->andReturn($fixtures);

        $result = $this->fixtureService->getAllFixtures();

        $this->assertCount(2, $result);
    }

    #[Test]
    public function it_clears_existing_fixtures_before_generating_new_ones()
    {
        $teams = $this->createTeamCollection();

        $this->teamRepositoryMock
            ->shouldReceive('all')
            ->once()
            ->andReturn($teams);

        $this->fixtureRepositoryMock
            ->shouldReceive('deleteAll')
            ->once()
            ->andReturn(true);

        $this->fixtureRepositoryMock
            ->shouldReceive('createMany')
            ->once()
            ->andReturn(new Collection());

        $result = $this->fixtureService->generateFixtures();

        // Verify that the service returns a collection
        $this->assertInstanceOf(Collection::class, $result);
    }

    #[Test]
    public function it_generates_proper_fixture_structure()
    {
        $teams = $this->createTeamCollection();

        $this->teamRepositoryMock
            ->shouldReceive('all')
            ->once()
            ->andReturn($teams);

        $this->fixtureRepositoryMock
            ->shouldReceive('deleteAll')
            ->once()
            ->andReturn(true);

        $this->fixtureRepositoryMock
            ->shouldReceive('createMany')
            ->once()
            ->with(Mockery::on(function ($fixtures) {
                // Check that all fixtures have required fields
                foreach ($fixtures as $fixture) {
                    if (!isset($fixture['week']) ||
                        !isset($fixture['home_team_id']) ||
                        !isset($fixture['away_team_id'])) {
                        return false;
                    }

                    // Check week is between 1-6
                    if ($fixture['week'] < 1 || $fixture['week'] > 6) {
                        return false;
                    }

                    // Check teams are different
                    if ($fixture['home_team_id'] === $fixture['away_team_id']) {
                        return false;
                    }
                }

                return true;
            }))
            ->andReturn(new Collection());

        $result = $this->fixtureService->generateFixtures();

        // Verify the result is a collection
        $this->assertInstanceOf(Collection::class, $result);
    }

    #[Test]
    public function it_generates_fixtures_with_correct_week_distribution()
    {
        $teams = $this->createTeamCollection();

        $this->teamRepositoryMock
            ->shouldReceive('all')
            ->once()
            ->andReturn($teams);

        $this->fixtureRepositoryMock
            ->shouldReceive('deleteAll')
            ->once()
            ->andReturn(true);

        $this->fixtureRepositoryMock
            ->shouldReceive('createMany')
            ->once()
            ->with(Mockery::on(function ($fixtures) {
                // Check that each week has exactly 2 matches
                $weekCounts = [];
                foreach ($fixtures as $fixture) {
                    $week = $fixture['week'];
                    $weekCounts[$week] = ($weekCounts[$week] ?? 0) + 1;
                }

                // Should have 6 weeks with 2 matches each
                if (count($weekCounts) !== 6) {
                    return false;
                }

                foreach ($weekCounts as $count) {
                    if ($count !== 2) {
                        return false;
                    }
                }

                return true;
            }))
            ->andReturn(new Collection());

        $result = $this->fixtureService->generateFixtures();

        // Verify the result is a collection
        $this->assertInstanceOf(Collection::class, $result);
    }

    #[Test]
    public function it_ensures_each_team_plays_once_per_week()
    {
        $teams = $this->createTeamCollection();

        $this->teamRepositoryMock
            ->shouldReceive('all')
            ->once()
            ->andReturn($teams);

        $this->fixtureRepositoryMock
            ->shouldReceive('deleteAll')
            ->once()
            ->andReturn(true);

        $this->fixtureRepositoryMock
            ->shouldReceive('createMany')
            ->once()
            ->with(Mockery::on(function ($fixtures) {
                // Group fixtures by week
                $weekFixtures = [];
                foreach ($fixtures as $fixture) {
                    $weekFixtures[$fixture['week']][] = $fixture;
                }

                // Check each week
                foreach ($weekFixtures as $week => $matches) {
                    $teamsInWeek = [];

                    foreach ($matches as $match) {
                        $homeTeam = $match['home_team_id'];
                        $awayTeam = $match['away_team_id'];

                        // Check if team already played this week
                        if (in_array($homeTeam, $teamsInWeek) || in_array($awayTeam, $teamsInWeek)) {
                            return false;
                        }

                        $teamsInWeek[] = $homeTeam;
                        $teamsInWeek[] = $awayTeam;
                    }

                    // Each week should have all 4 teams playing
                    if (count($teamsInWeek) !== 4) {
                        return false;
                    }
                }

                return true;
            }))
            ->andReturn(new Collection());

        $result = $this->fixtureService->generateFixtures();

        // Verify the result is a collection
        $this->assertInstanceOf(Collection::class, $result);
    }

    private function createTeamCollection()
    {
        return new Collection([
            $this->createTeam(1, 'Liverpool'),
            $this->createTeam(2, 'Manchester United'),
            $this->createTeam(3, 'Arsenal'),
            $this->createTeam(4, 'Chelsea')
        ]);
    }

    private function createMockFixtureCollection()
    {
        return new Collection([
            $this->createMockMatch(1, 1, 2, 1),
            $this->createMockMatch(2, 3, 4, 1),
            $this->createMockMatch(3, 1, 3, 2),
            $this->createMockMatch(4, 2, 4, 2),
            $this->createMockMatch(5, 1, 4, 3),
            $this->createMockMatch(6, 2, 3, 3),
            $this->createMockMatch(7, 2, 1, 4),
            $this->createMockMatch(8, 4, 3, 4),
            $this->createMockMatch(9, 3, 1, 5),
            $this->createMockMatch(10, 4, 2, 5),
            $this->createMockMatch(11, 4, 1, 6),
            $this->createMockMatch(12, 3, 2, 6),
        ]);
    }

    private function createTeam(int $id, string $name)
    {
        $team = new Team();
        $team->id = $id;
        $team->name = $name;
        $team->power = 80;
        $team->home_advantage = 5;

        return $team;
    }

    private function createMockMatch(int $id, int $homeTeamId, int $awayTeamId, int $week)
    {
        $match = new GameMatch();
        $match->id = $id;
        $match->week = $week;
        $match->home_team_id = $homeTeamId;
        $match->away_team_id = $awayTeamId;
        $match->is_played = false;
        $match->home_goals = null;
        $match->away_goals = null;
        $match->played_at = null;

        return $match;
    }
}
