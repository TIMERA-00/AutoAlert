<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Favorites\FavoritesList;
use App\Livewire\VehicleShow;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\NotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/vehicules')->assertRedirect('/login');
        $this->get('/admin/utilisateurs')->assertRedirect('/login');
    }

    public function test_regular_users_cannot_reach_the_admin_area(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get('/admin/vehicules')->assertForbidden();
    }

    public function test_inactive_users_are_logged_out_and_sent_to_login(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => false]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_reaches_every_back_office_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $vehicle = Vehicle::factory()->draft()->create();

        foreach ([
            '/admin',
            '/admin/vehicules',
            '/admin/vehicules/nouveau',
            '/admin/vehicules/import',
            "/admin/vehicules/{$vehicle->slug}/modifier",
            "/admin/vehicules/{$vehicle->slug}",
            '/admin/sources',
            '/admin/utilisateurs',
            '/admin/alertes',
            '/admin/notifications',
            '/admin/statistiques',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_admin_can_preview_a_draft_from_the_back_office(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $vehicle = Vehicle::factory()->draft()->create(['brand' => 'Toyota', 'model' => 'RAV4']);

        $this->actingAs($admin)->get("/admin/vehicules/{$vehicle->slug}")->assertOk();
    }

    public function test_a_draft_vehicle_is_not_reachable_by_a_guest(): void
    {
        $vehicle = Vehicle::factory()->draft()->create(['brand' => 'Toyota', 'model' => 'RAV4']);

        $this->get("/vehicles/{$vehicle->slug}")->assertNotFound();
    }

    public function test_draft_vehicle_is_absent_from_the_public_catalogue(): void
    {
        Vehicle::factory()->draft()->create(['brand' => 'Toyota', 'model' => 'RAV4']);

        $this->get('/vehicles')->assertOk()->assertDontSee('RAV4', false);
    }

    public function test_user_area_renders_for_a_logged_in_user(): void
    {
        $user = User::factory()->create();

        foreach (['/dashboard', '/favoris', '/alertes', '/notifications', '/preferences', '/profile'] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_user_can_add_and_remove_a_favorite(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->published()->create();

        Livewire::actingAs($user)
            ->test(VehicleShow::class, ['vehicle' => $vehicle])
            ->call('toggleFavorite')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'vehicle_id' => $vehicle->id]);

        Livewire::actingAs($user)
            ->test(VehicleShow::class, ['vehicle' => $vehicle])
            ->call('toggleFavorite');

        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'vehicle_id' => $vehicle->id]);
    }

    public function test_favorites_list_can_remove_an_entry(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->published()->create();
        $user->favorites()->create(['vehicle_id' => $vehicle->id]);

        Livewire::actingAs($user)
            ->test(FavoritesList::class)
            ->call('remove', $vehicle->id);

        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'vehicle_id' => $vehicle->id]);
    }

    public function test_notifications_page_only_shows_the_current_user_notifications(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $vehicle = Vehicle::factory()->published()->create();

        NotificationDispatcher::class;
        app(NotificationDispatcher::class)
            ->notifyUser($user->id, $vehicle, subject: 'La mienne');
        app(NotificationDispatcher::class)
            ->notifyUser($other->id, $vehicle, subject: 'Celle de lautre');

        $this->actingAs($user)->get('/notifications')
            ->assertOk()
            ->assertSee('La mienne')
            ->assertDontSee('Celle de lautre');
    }
}
