<?php

namespace Tests\Unit\Services;

use App\Contracts\FixtureRepositoryInterface;
use App\Models\GameMatch;
use App\Models\Team;
use App\Services\ChampionshipPredictionService;
use App\Services\LeagueTableService;
use App\Services\MatchSimulationService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChampionshipPredictionServiceTest extends TestCase
{
    protected $leagueTableService;
    protected $simulationService;
    protected $fixtureRepository;
    protected $championshipService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->leagueTableService = Mockery::mock(LeagueTableService::class);
        $this->simulationService = Mockery::mock(MatchSimulationService::class);
        $this->fixtureRepository = Mockery::mock(FixtureRepositoryInterface::class);

        $this->championshipService = new ChampionshipPredictionService(
            $this->leagueTableService,
            $this->simulationService,
            $this->fixtureRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function test_predictions_not_available_before_week_4()
    {
        $playedMatches = $this->createPlayedMatches(3);

        $this->fixtureRepository
            ->shouldReceive('getPlayedMatches')
            ->once()
            ->andReturn($playedMatches);

        $result = $this->championshipService->getChampionshipPredictions();

        $this->assertFalse($result['is_available']);
        $this->assertEquals('Championship predictions available after week 4', $result['reason']);
        $this->assertEquals(2, $result['current_week']);
        $this->assertEquals(3, $result['matches_played']);
    }

    #[Test]
    public function test_predictions_available_after_week_4()
    {
        $playedMatches = $this->createPlayedMatches(8);
        $remainingMatches = $this->createRemainingMatches(4);
        $currentStandings = $this->createMockStandings();

        $this->fixtureRepository
            ->shouldReceive('getPlayedMatches')
            ->once()
            ->andReturn($playedMatches);

        $this->fixtureRepository
            ->shouldReceive('getAllWithTeams')
            ->once()
            ->andReturn($remainingMatches);

        $this->leagueTableService
            ->shouldReceive('getLeagueTable')
            ->once()
            ->andReturn(['standings' => $currentStandings]);

        $result = $this->championshipService->getChampionshipPredictions();

        $this->assertTrue($result['is_available']);
        $this->assertEquals(4, $result['current_week']);
        $this->assertEquals(8, $result['matches_played']);
        $this->assertEquals(4, $result['matches_remaining']);
        $this->assertArrayHasKey('predictions', $result);
        $this->assertArrayHasKey('methodology', $result);
    }

    #[Test]
    public function test_season_completed_returns_final_result()
    {
        $playedMatches = $this->createPlayedMatches(12);
        $finalStandings = $this->createMockStandings();

        $this->fixtureRepository
            ->shouldReceive('getPlayedMatches')
            ->once()
            ->andReturn($playedMatches);

        $this->leagueTableService
            ->shouldReceive('getLeagueTable')
            ->once()
            ->andReturn(['standings' => $finalStandings]);

        $result = $this->championshipService->getChampionshipPredictions();

        $this->assertTrue($result['is_available']);
        $this->assertTrue($result['season_completed']);
        $this->assertArrayHasKey('champion', $result);
        $this->assertEquals(100.0, $result['champion']['championship_probability']);
        $this->assertEquals('Liverpool', $result['champion']['team_name']);
    }

    #[Test]
    public function test_predictions_format_is_correct()
    {
        $playedMatches = $this->createPlayedMatches(8);
        $remainingMatches = $this->createRemainingMatches(4);
        $currentStandings = $this->createMockStandings();

        $this->fixtureRepository
            ->shouldReceive('getPlayedMatches')
            ->once()
            ->andReturn($playedMatches);

        $this->fixtureRepository
            ->shouldReceive('getAllWithTeams')
            ->once()
            ->andReturn($remainingMatches);

        $this->leagueTableService
            ->shouldReceive('getLeagueTable')
            ->once()
            ->andReturn(['standings' => $currentStandings]);

        $result = $this->championshipService->getChampionshipPredictions();

        $this->assertArrayHasKey('predictions', $result);

        foreach ($result['predictions'] as $prediction) {
            $this->assertArrayHasKey('team_id', $prediction);
            $this->assertArrayHasKey('team_name', $prediction);
            $this->assertArrayHasKey('current_position', $prediction);
            $this->assertArrayHasKey('current_points', $prediction);
            $this->assertArrayHasKey('championship_probability', $prediction);
            $this->assertIsFloat($prediction['championship_probability']);
            $this->assertGreaterThanOrEqual(0, $prediction['championship_probability']);
            $this->assertLessThanOrEqual(100, $prediction['championship_probability']);
        }
    }

    #[Test]
    public function test_predictions_are_sorted_by_probability()
    {
        $playedMatches = $this->createPlayedMatches(8);
        $remainingMatches = $this->createRemainingMatches(4);
        $currentStandings = $this->createMockStandings();

        $this->fixtureRepository
            ->shouldReceive('getPlayedMatches')
            ->once()
            ->andReturn($playedMatches);

        $this->fixtureRepository
            ->shouldReceive('getAllWithTeams')
            ->once()
            ->andReturn($remainingMatches);

        $this->leagueTableService
            ->shouldReceive('getLeagueTable')
            ->once()
            ->andReturn(['standings' => $currentStandings]);

        $result = $this->championshipService->getChampionshipPredictions();

        $predictions = $result['predictions'];

        for ($i = 1; $i < count($predictions); $i++) {
            $this->assertGreaterThanOrEqual(
                $predictions[$i]['championship_probability'],
                $predictions[$i-1]['championship_probability']
            );
        }
    }
    private function createPlayedMatches(int $count): Collection
    {
        $matches = [];
        $week = 1;

        for ($i = 0; $i < $count; $i++) {
            if ($i > 0 && $i % 2 === 0) {
                $week++;
            }

            $match = new GameMatch();
            $match->id = $i + 1;
            $match->week = $week;
            $match->is_played = true;
            $matches[] = $match;
        }

        return new Collection($matches);
    }

    private function createRemainingMatches(int $count): Collection
    {
        $matches = [];

        for ($i = 0; $i < $count; $i++) {
            $match = new GameMatch();
            $match->id = $i + 100;
            $match->home_team_id = 1;
            $match->away_team_id = 2;
            $match->week = 5 + intval($i / 2);
            $match->is_played = false;

            $homeTeam = new Team();
            $homeTeam->id = 1;
            $homeTeam->power = 90;
            $homeTeam->home_advantage = 6;

            $awayTeam = new Team();
            $awayTeam->id = 2;
            $awayTeam->power = 85;
            $awayTeam->home_advantage = 5;

            $match->setRelation('homeTeam', $homeTeam);
            $match->setRelation('awayTeam', $awayTeam);

            $matches[] = $match;
        }

        return new Collection($matches);
    }

    private function createMockStandings(): array
    {
        return [
            [
                'team_id' => 1,
                'team_name' => 'Liverpool',
                'matches_played' => 4,
                'points' => 12,
                'wins' => 4,
                'draws' => 0,
                'losses' => 0,
                'goals_for' => 10,
                'goals_against' => 2,
                'goal_difference' => 8
            ],
            [
                'team_id' => 2,
                'team_name' => 'Manchester United',
                'matches_played' => 4,
                'points' => 9,
                'wins' => 3,
                'draws' => 0,
                'losses' => 1,
                'goals_for' => 8,
                'goals_against' => 4,
                'goal_difference' => 4
            ],
            [
                'team_id' => 3,
                'team_name' => 'Arsenal',
                'matches_played' => 4,
                'points' => 6,
                'wins' => 2,
                'draws' => 0,
                'losses' => 2,
                'goals_for' => 6,
                'goals_against' => 6,
                'goal_difference' => 0
            ],
            [
                'team_id' => 4,
                'team_name' => 'Chelsea',
                'matches_played' => 4,
                'points' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 4,
                'goals_for' => 2,
                'goals_against' => 14,
                'goal_difference' => -12
            ]
        ];
    }
}
