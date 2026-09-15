<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Asserts every authenticated GET route is reachable by the role it is
 * built for and rejected for the other role. This is the test that would
 * have caught the Asset_analysis.blade.php case mismatch (a 500, not a
 * blade-not-found on Windows) and the earlier lack of any role gate at all.
 */
class RouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function assetTeamRoutes(): array
    {
        return [
            'asset dashboard' => ['GET', '/dashboard'],
            'asset directory' => ['GET', '/assets'],
            'asset analysis' => ['GET', '/analysis/asset'],
            'location analysis' => ['GET', '/analysis/location'],
            'log history' => ['GET', '/logs'],
            'damage queue' => ['GET', '/damage-queue'],
        ];
    }

    #[DataProvider('assetTeamRoutes')]
    public function test_asset_team_route_is_reachable_by_asset_team(string $method, string $uri): void
    {
        $admin = User::factory()->assetTeam()->create();

        $this->actingAs($admin)->call($method, $uri)->assertOk();
    }

    #[DataProvider('assetTeamRoutes')]
    public function test_asset_team_route_is_forbidden_to_staff(string $method, string $uri): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->call($method, $uri)->assertForbidden();
    }

    public function test_staff_dashboard_is_forbidden_to_asset_team(): void
    {
        $admin = User::factory()->assetTeam()->create();

        $this->actingAs($admin)->get('/staff_dashboard')->assertForbidden();
    }

    public function test_authenticated_routes_reject_guests(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/staff_dashboard')->assertRedirect(route('login'));
    }
}
