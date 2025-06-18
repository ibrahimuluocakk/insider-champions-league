<?php

namespace Tests\Feature;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TeamControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test teams
        $this->createTestTeams();
    }

    #[Test]
    public function it_can_get_all_teams()
    {
        $response = $this->getJson('/api/teams');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'power',
                            'home_advantage',
                            'total_strength',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_returns_correct_response_format()
    {
        $response = $this->getJson('/api/teams');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Teams retrieved successfully'
                ]);
    }

    /** @test */
    public function it_returns_exactly_four_teams()
    {
        $response = $this->getJson('/api/teams');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(4, $data);
    }

    /** @test */
    public function it_returns_teams_with_correct_data_structure()
    {
        $response = $this->getJson('/api/teams');

        $response->assertStatus(200);

        $teams = $response->json('data');

        foreach ($teams as $team) {
            $this->assertArrayHasKey('id', $team);
            $this->assertArrayHasKey('name', $team);
            $this->assertArrayHasKey('power', $team);
            $this->assertArrayHasKey('home_advantage', $team);
            $this->assertArrayHasKey('total_strength', $team);
            $this->assertArrayHasKey('created_at', $team);
            $this->assertArrayHasKey('updated_at', $team);

            // Verify data types
            $this->assertIsInt($team['id']);
            $this->assertIsString($team['name']);
            $this->assertIsInt($team['power']);
            $this->assertIsInt($team['home_advantage']);
            $this->assertIsInt($team['total_strength']);
        }
    }

    /** @test */
    public function it_calculates_total_strength_correctly()
    {
        $response = $this->getJson('/api/teams');

        $response->assertStatus(200);

        $teams = $response->json('data');

        foreach ($teams as $team) {
            $expectedTotalStrength = $team['power'] + $team['home_advantage'];
            $this->assertEquals($expectedTotalStrength, $team['total_strength']);
        }
    }

    /** @test */
    public function it_returns_teams_with_expected_names()
    {
        $response = $this->getJson('/api/teams');

        $response->assertStatus(200);

        $teamNames = collect($response->json('data'))->pluck('name')->toArray();

        $expectedNames = ['Manchester United', 'Liverpool', 'Chelsea', 'Arsenal'];

        foreach ($expectedNames as $expectedName) {
            $this->assertContains($expectedName, $teamNames);
        }
    }

    /** @test */
    public function it_returns_empty_data_when_no_teams_exist()
    {
        // Clear all teams
        Team::truncate();

        $response = $this->getJson('/api/teams');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Teams retrieved successfully',
                    'data' => []
                ]);
    }

    private function createTestTeams(): void
    {
        $teams = [
            [
                'name' => 'Manchester United',
                'power' => 85,
                'home_advantage' => 5,
            ],
            [
                'name' => 'Liverpool',
                'power' => 90,
                'home_advantage' => 6,
            ],
            [
                'name' => 'Chelsea',
                'power' => 75,
                'home_advantage' => 4,
            ],
            [
                'name' => 'Arsenal',
                'power' => 80,
                'home_advantage' => 5,
            ],
        ];

        foreach ($teams as $team) {
            Team::create($team);
        }
    }
}
