<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_register_creates_a_user_and_redirects_to_login(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Staffer',
            'email' => 'newstaffer@example.com',
            'role' => 'staff',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', ['email' => 'newstaffer@example.com', 'role' => 'staff']);
    }

    public function test_staff_login_redirects_to_staff_dashboard(): void
    {
        User::factory()->staff()->create([
            'email' => 'staff@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'staff@example.com',
            'password' => 'password',
            'role' => 'staff',
        ]);

        $response->assertRedirect(route('staff_dashboard'));
        $this->assertAuthenticated();
    }

    public function test_asset_team_login_redirects_to_asset_dashboard(): void
    {
        User::factory()->assetTeam()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'asset_team',
        ]);

        $response->assertRedirect(route('asset_dashboard'));
    }

    public function test_login_fails_when_selected_role_does_not_match_account(): void
    {
        User::factory()->staff()->create([
            'email' => 'staff@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'staff@example.com',
            'password' => 'password',
            'role' => 'asset_team',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        User::factory()->staff()->create(['email' => 'staff@example.com']);

        $response = $this->post('/login', [
            'email' => 'staff@example.com',
            'password' => 'wrong-password',
            'role' => 'staff',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
