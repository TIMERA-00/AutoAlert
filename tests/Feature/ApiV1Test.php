<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    private function authHeaders(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
    }

    public function test_register_returns_a_user_and_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Awa',
            'last_name' => 'Ndiaye',
            'email' => 'awa@example.com',
            'password' => 'Password@2024',
            'password_confirmation' => 'Password@2024',
        ]);

        $response->assertCreated()->assertJsonStructure(['user' => ['id', 'first_name', 'last_name', 'email'], 'token']);
        $this->assertDatabaseHas('users', ['email' => 'awa@example.com']);
    }

    public function test_register_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'awa@example.com']);

        $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Awa',
            'last_name' => 'Ndiaye',
            'email' => 'awa@example.com',
            'password' => 'Password@2024',
            'password_confirmation' => 'Password@2024',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'awa@example.com', 'password' => Hash::make('Password@2024')]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'awa@example.com',
            'password' => 'mauvais',
        ])->assertUnprocessable();
    }

    public function test_login_returns_a_token(): void
    {
        $user = User::factory()->create(['email' => 'awa@example.com', 'password' => Hash::make('Password@2024')]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'awa@example.com',
            'password' => 'Password@2024',
        ])->assertOk()->assertJsonStructure(['user', 'token']);

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->getJson('/api/v1/me', $this->authHeaders($this->tokenFor($user)))
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    }

    public function test_protected_endpoints_require_a_token(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
        $this->getJson('/api/v1/favorites')->assertUnauthorized();
        $this->getJson('/api/v1/alerts')->assertUnauthorized();
        $this->getJson('/api/v1/notifications')->assertUnauthorized();
    }

    public function test_vehicle_catalogue_is_public(): void
    {
        Vehicle::factory()->published()->create(['brand' => 'Toyota', 'model' => 'RAV4']);
        Vehicle::factory()->draft()->create(['brand' => 'Renault', 'model' => 'Clio']);

        $this->getJson('/api/v1/vehicles')
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'total', 'per_page'])
            ->assertJsonCount(1, 'data');
    }

    public function test_vehicle_catalogue_accepts_filters(): void
    {
        Vehicle::factory()->published()->create(['brand' => 'Toyota', 'price' => 10_000_000]);
        Vehicle::factory()->published()->create(['brand' => 'Renault', 'price' => 30_000_000]);

        $this->getJson('/api/v1/vehicles?brand=Toyota')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/vehicles?max_price=15000000')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_facets_are_exposed(): void
    {
        Vehicle::factory()->published()->create(['brand' => 'Toyota']);

        $this->getJson('/api/v1/vehicles/facets')->assertOk()->assertJsonStructure(['brands', 'locations']);
    }

    public function test_vehicle_show_is_public(): void
    {
        $vehicle = Vehicle::factory()->published()->create(['brand' => 'Toyota', 'model' => 'RAV4']);

        $this->getJson("/api/v1/vehicles/{$vehicle->slug}")
            ->assertOk()
            ->assertJsonPath('data.id', $vehicle->id);
    }

    public function test_favorite_lifecycle(): void
    {
        $user = User::factory()->create();
        $headers = $this->authHeaders($this->tokenFor($user));
        $vehicle = Vehicle::factory()->published()->create();

        $this->postJson("/api/v1/favorites/{$vehicle->slug}", [], $headers)->assertCreated();
        $this->getJson('/api/v1/favorites', $headers)->assertOk()->assertJsonCount(1, 'data');

        $this->deleteJson("/api/v1/favorites/{$vehicle->slug}", [], $headers)->assertOk();
        $this->getJson('/api/v1/favorites', $headers)->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_alert_crud(): void
    {
        $headers = $this->authHeaders($this->tokenFor(User::factory()->create()));

        $id = $this->postJson('/api/v1/alerts', [
            'name' => 'SUV Dakar',
            'brand' => 'Toyota',
            'min_year' => 2020,
            'max_price' => 20_000_000,
            'frequency' => 'IMMEDIATE',
            'channels' => ['EMAIL', 'IN_APP'],
        ], $headers)->assertCreated()->json('data.id');

        $this->getJson('/api/v1/alerts', $headers)->assertOk()->assertJsonCount(1, 'data');

        $this->patchJson("/api/v1/alerts/{$id}", ['name' => 'SUV renomme'], $headers)->assertOk();
        $this->patchJson("/api/v1/alerts/{$id}/toggle", [], $headers)->assertOk();

        $this->deleteJson("/api/v1/alerts/{$id}", [], $headers)->assertNoContent();
        $this->getJson('/api/v1/alerts', $headers)->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_alert_validation_rejects_a_bad_frequency(): void
    {
        $headers = $this->authHeaders($this->tokenFor(User::factory()->create()));

        $this->postJson('/api/v1/alerts', [
            'name' => 'Alerte',
            'frequency' => 'HEUREMENT',
        ], $headers)->assertUnprocessable()->assertJsonValidationErrors('frequency');
    }

    public function test_notifications_can_be_read(): void
    {
        $headers = $this->authHeaders($this->tokenFor(User::factory()->create()));

        $this->getJson('/api/v1/notifications', $headers)->assertOk()->assertJsonStructure(['data']);

        $this->patchJson('/api/v1/notifications/read-all', [], $headers)->assertOk();
    }

    public function test_logout_revokes_the_token(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);
        $headers = $this->authHeaders($token);

        $this->postJson('/api/v1/logout', [], $headers)->assertOk();

        $this->assertSame(0, $user->fresh()->tokens()->count());

        // The auth guards are cached for the duration of a test process, so they
        // have to be dropped before replaying the request with the dead token.
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/v1/me', $headers)->assertUnauthorized();
    }
}
