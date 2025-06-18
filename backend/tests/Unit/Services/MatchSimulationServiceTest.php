<?php

namespace Tests\Unit\Services;

use App\Contracts\FixtureRepositoryInterface;
use App\Contracts\TeamRepositoryInterface;
use App\Models\GameMatch;
use App\Models\Team;
use App\Services\MatchSimulationService;
use App\Services\TeamService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MatchSimulationServiceTest extends TestCase
{
    protected $fixtureRepository;
    protected $teamRepository;
    protected $matchSimulationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureRepository = Mockery::mock(FixtureRepositoryInterface::class);
        $this->teamRepository = Mockery::mock(TeamRepositoryInterface::class);

        $this->matchSimulationService = new MatchSimulationService(
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
    public function test_simulate_next_week_success()
    {
        $unplayedMatch = $this->createMockMatch(1, false);
        $matches = new Collection([$unplayedMatch]);

        $this->fixtureRepository
            ->shouldReceive('getFirstUnplayedMatch')
            ->once()
            ->andReturn($unplayedMatch);

        $this->fixtureRepository
            ->shouldReceive('getMatchesByWeek')
            ->with(1)
            ->once()
            ->andReturn($matches);

        $result = $this->matchSimulationService->simulateNextWeek();

        $this->assertEquals(1, $result['week']);
        $this->assertEquals(1, $result['total_simulated']);
        $this->assertCount(1, $result['matches']);
    }

    #[Test]
    public function test_simulate_next_week_throws_exception_when_all_played()
    {
        $this->fixtureRepository
            ->shouldReceive('getFirstUnplayedMatch')
            ->once()
            ->andReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('All matches have been played');

        $this->matchSimulationService->simulateNextWeek();
    }

    #[Test]
    public function test_simulate_all_remaining_success()
    {
        $match1 = $this->createMockMatch(1, false);
        $match2 = $this->createMockMatch(2, false);

        $week1Matches = new Collection([$match1]);
        $week2Matches = new Collection([$match2]);

        $this->fixtureRepository
            ->shouldReceive('getFirstUnplayedMatch')
            ->times(3)
            ->andReturn($match1, $match2, null);

        $this->fixtureRepository
            ->shouldReceive('getMatchesByWeek')
            ->with(1)
            ->once()
            ->andReturn($week1Matches);

        $this->fixtureRepository
            ->shouldReceive('getMatchesByWeek')
            ->with(2)
            ->once()
            ->andReturn($week2Matches);

        $result = $this->matchSimulationService->simulateAllRemaining();

        $this->assertEquals(2, $result['weeks_simulated']);
        $this->assertEquals(2, $result['total_matches_simulated']);
        $this->assertCount(2, $result['results']);
    }

    #[Test]
    public function test_simulate_all_remaining_throws_exception_when_all_played()
    {
        $this->fixtureRepository
            ->shouldReceive('getFirstUnplayedMatch')
            ->once()
            ->andReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('All matches have already been played');

        $this->matchSimulationService->simulateAllRemaining();
    }

    #[Test]
    public function test_simulate_week_success()
    {
        $match = $this->createMockMatch(1, false);
        $matches = new Collection([$match]);

        $this->fixtureRepository
            ->shouldReceive('getMatchesByWeek')
            ->with(1)
            ->once()
            ->andReturn($matches);

        $result = $this->matchSimulationService->simulateWeek(1);

        $this->assertEquals(1, $result['week']);
        $this->assertEquals(1, $result['total_simulated']);
    }

    #[Test]
    public function test_simulate_week_throws_exception_when_no_matches()
    {
        $this->fixtureRepository
            ->shouldReceive('getMatchesByWeek')
            ->with(1)
            ->once()
            ->andReturn(new Collection());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No matches found for week 1');

        $this->matchSimulationService->simulateWeek(1);
    }

    #[Test]
    public function test_simulate_week_skips_already_played_matches()
    {
        $playedMatch = $this->createMockMatch(1, true);
        $unplayedMatch = $this->createMockMatch(1, false);
        $matches = new Collection([$playedMatch, $unplayedMatch]);

        $this->fixtureRepository
            ->shouldReceive('getMatchesByWeek')
            ->with(1)
            ->once()
            ->andReturn($matches);

        $result = $this->matchSimulationService->simulateWeek(1);

        $this->assertEquals(1, $result['total_simulated']);
    }

    private function createMockMatch(int $week, bool $isPlayed): GameMatch
    {
        $homeTeam = Mockery::mock(Team::class);
        $homeTeam->shouldReceive('getAttribute')->with('power')->andReturn(85);
        $homeTeam->shouldReceive('getAttribute')->with('home_advantage')->andReturn(5);

        $awayTeam = Mockery::mock(Team::class);
        $awayTeam->shouldReceive('getAttribute')->with('power')->andReturn(80);
        $awayTeam->shouldReceive('getAttribute')->with('home_advantage')->andReturn(4);

        $match = Mockery::mock(GameMatch::class);
        $match->shouldReceive('getAttribute')->with('is_played')->andReturn($isPlayed);
        $match->shouldReceive('getAttribute')->with('week')->andReturn($week);
        $match->shouldReceive('getAttribute')->with('homeTeam')->andReturn($homeTeam);
        $match->shouldReceive('getAttribute')->with('awayTeam')->andReturn($awayTeam);

        if (!$isPlayed) {
            $match->shouldReceive('update')->once()->andReturn(true);
        }

        return $match;
    }
}
