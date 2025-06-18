<?php

namespace Tests\Unit;

use App\Contracts\TeamRepositoryInterface;
use App\Models\Team;
use App\Services\TeamService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TeamServiceTest extends TestCase
{
    private TeamService $teamService;
    private TeamRepositoryInterface|MockInterface $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRepository = Mockery::mock(TeamRepositoryInterface::class);
        $this->teamService = new TeamService($this->mockRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_compare_power_between_two_teams()
    {
        $teamA = new Team([
            'name' => 'Liverpool',
            'power' => 90,
            'home_advantage' => 6
        ]);

        $teamB = new Team([
            'name' => 'Chelsea',
            'power' => 75,
            'home_advantage' => 4
        ]);

        $result = $this->teamService->comparePower($teamA, $teamB);

        $this->assertIsArray($result);
        $this->assertEquals('Liverpool', $result['team_a']);
        $this->assertEquals('Chelsea', $result['team_b']);
        $this->assertEquals(15, $result['power_difference']);
        $this->assertEquals('Liverpool', $result['stronger_team']);
        $this->assertArrayHasKey('advantage_percentage', $result);
    }

    #[Test]
    public function it_returns_equal_when_teams_have_same_power()
    {
        $teamA = new Team([
            'name' => 'Team A',
            'power' => 80,
            'home_advantage' => 5
        ]);

        $teamB = new Team([
            'name' => 'Team B',
            'power' => 80,
            'home_advantage' => 4
        ]);

        $result = $this->teamService->comparePower($teamA, $teamB);

        $this->assertEquals(0, $result['power_difference']);
        $this->assertEquals('Equal', $result['stronger_team']);
        $this->assertEquals(50.0, $result['advantage_percentage']);
    }

    #[Test]
    public function it_calculates_total_strength_without_home_advantage()
    {
        $team = new Team([
            'name' => 'Manchester United',
            'power' => 85,
            'home_advantage' => 5
        ]);

        $totalStrength = $this->teamService->getTotalStrength($team, false);

        $this->assertEquals(85, $totalStrength);
    }

    #[Test]
    public function it_calculates_total_strength_with_home_advantage()
    {
        $team = new Team([
            'name' => 'Manchester United',
            'power' => 85,
            'home_advantage' => 5
        ]);

        $totalStrength = $this->teamService->getTotalStrength($team, true);

        $this->assertEquals(90, $totalStrength);
    }

    #[Test]
    public function it_calculates_advantage_percentage_correctly()
    {
        // Test with positive power difference
        $teamA = new Team([
            'name' => 'Strong Team',
            'power' => 90,
            'home_advantage' => 5
        ]);

        $teamB = new Team([
            'name' => 'Weak Team',
            'power' => 60,
            'home_advantage' => 3
        ]);

        $result = $this->teamService->comparePower($teamA, $teamB);

        $this->assertGreaterThan(50, $result['advantage_percentage']);
        $this->assertLessThanOrEqual(90, $result['advantage_percentage']);
    }

    #[Test]
    public function it_caps_advantage_percentage_between_10_and_90()
    {
        // Test with extreme power difference
        $teamA = new Team([
            'name' => 'Super Strong Team',
            'power' => 100,
            'home_advantage' => 10
        ]);

        $teamB = new Team([
            'name' => 'Very Weak Team',
            'power' => 1,
            'home_advantage' => 0
        ]);

        $result = $this->teamService->comparePower($teamA, $teamB);

        $this->assertGreaterThanOrEqual(10, $result['advantage_percentage']);
        $this->assertLessThanOrEqual(90, $result['advantage_percentage']);
    }

    #[Test]
    public function it_can_get_team_by_id()
    {
        $team = new Team([
            'id' => 1,
            'name' => 'Liverpool',
            'power' => 90,
            'home_advantage' => 6
        ]);

        $this->mockRepository
            ->expects('getTeamById')
            ->with(1)
            ->once()
            ->andReturn($team);

        $result = $this->teamService->getTeamById(1);

        $this->assertInstanceOf(Team::class, $result);
        $this->assertEquals('Liverpool', $result->name);
        $this->assertEquals(90, $result->power);
    }

    #[Test]
    public function it_returns_null_when_team_not_found()
    {
        $this->mockRepository
            ->expects('getTeamById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $result = $this->teamService->getTeamById(999);

        $this->assertNull($result);
    }
}
