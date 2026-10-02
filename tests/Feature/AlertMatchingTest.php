<?php

namespace Tests\Feature;

use App\Enums\AlertFrequency;
use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Enums\UserRole;
use App\Jobs\MatchAlertsForVehicle;
use App\Livewire\Alerts\AlertForm;
use App\Models\Alert;
use App\Models\Notification;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\MatchingService;
use App\Services\NotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class AlertMatchingTest extends TestCase
{
    use RefreshDatabase;

    private function alert(array $criteria = [], array $userAttributes = []): Alert
    {
        return Alert::factory()->create(array_merge([
            'user_id' => User::factory()->create(array_merge([
                'notify_email' => true,
                'notify_in_app' => true,
                'notify_whatsapp' => false,
            ], $userAttributes))->id,
            'frequency' => AlertFrequency::Immediate,
            'is_active' => true,
        ], $criteria));
    }

    /** An alert with only the criteria under test, everything else unconstrained. */
    private function alertMatchingOnly(array $criteria): Alert
    {
        return $this->alert(array_merge([
            'brand' => null,
            'model' => null,
            'min_year' => null,
            'max_year' => null,
            'min_price' => null,
            'max_price' => null,
            'max_mileage' => null,
            'fuel' => null,
            'transmission' => null,
            'body_type' => null,
            'location' => null,
        ], $criteria));
    }

    public function test_matching_service_matches_on_brand_and_price(): void
    {
        $matching = app(MatchingService::class);

        Vehicle::factory()->published()->create([
            'brand' => 'Toyota', 'price' => 12_000_000, 'year' => 2022,
            'fuel' => FuelType::Petrol, 'transmission' => Transmission::Automatic,
        ]);
        Vehicle::factory()->published()->create([
            'brand' => 'Renault', 'price' => 40_000_000, 'year' => 2015,
            'fuel' => FuelType::Diesel, 'transmission' => Transmission::Manual,
        ]);

        $matches = $matching->vehiclesFor($this->alertMatchingOnly(['brand' => 'Toyota', 'max_price' => 20_000_000]));

        $this->assertCount(1, $matches);
        $this->assertSame('Toyota', $matches->first()->brand);
    }

    public function test_matching_service_respects_the_price_ceiling(): void
    {
        $matching = app(MatchingService::class);

        Vehicle::factory()->published()->create(['brand' => 'Toyota', 'price' => 45_000_000]);
        $alert = $this->alertMatchingOnly(['brand' => 'Toyota', 'max_price' => 20_000_000]);

        $this->assertSame(0, $matching->countMatches($alert));
    }

    public function test_publishing_a_vehicle_notifies_matching_alerts(): void
    {
        Queue::fake();

        $user = User::factory()->create(['notify_email' => true, 'notify_in_app' => true, 'notify_whatsapp' => false]);
        $this->alertMatchingOnly(['user_id' => $user->id, 'brand' => 'Toyota', 'min_year' => 2020]);

        $vehicle = Vehicle::factory()->published()->create(['brand' => 'Toyota', 'year' => 2023]);

        $created = app(NotificationDispatcher::class)->dispatchForVehicle($vehicle);

        $this->assertSame(2, $created, 'Une notification EMAIL + une IN_APP sont attendues.');
        $this->assertSame(2, Notification::where('vehicle_id', $vehicle->id)->count());
    }

    public function test_digest_alerts_are_not_notified_instantly(): void
    {
        $user = User::factory()->create();
        $this->alertMatchingOnly(['user_id' => $user->id, 'frequency' => AlertFrequency::Daily, 'brand' => 'Toyota']);

        $vehicle = Vehicle::factory()->published()->create(['brand' => 'Toyota']);

        $this->assertSame(0, app(NotificationDispatcher::class)->dispatchForVehicle($vehicle));
    }

    public function test_dispatch_is_idempotent(): void
    {
        $user = User::factory()->create(['notify_email' => true, 'notify_in_app' => false, 'notify_whatsapp' => false]);
        $this->alertMatchingOnly(['user_id' => $user->id, 'brand' => 'Toyota']);
        $vehicle = Vehicle::factory()->published()->create(['brand' => 'Toyota']);

        $dispatcher = app(NotificationDispatcher::class);

        $this->assertSame(1, $dispatcher->dispatchForVehicle($vehicle));
        $this->assertSame(0, $dispatcher->dispatchForVehicle($vehicle), 'Pas de doublon.');
    }

    public function test_inactive_users_are_not_notified(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->alertMatchingOnly(['user_id' => $user->id, 'brand' => 'Toyota']);
        $vehicle = Vehicle::factory()->published()->create(['brand' => 'Toyota']);

        $this->assertSame(0, app(NotificationDispatcher::class)->dispatchForVehicle($vehicle));
    }

    public function test_matching_job_ignores_unpublished_vehicles(): void
    {
        $user = User::factory()->create();
        $this->alertMatchingOnly(['user_id' => $user->id, 'brand' => 'Toyota']);
        $vehicle = Vehicle::factory()->draft()->create(['brand' => 'Toyota']);

        (new MatchAlertsForVehicle($vehicle->id))->handle(app(NotificationDispatcher::class));

        $this->assertSame(0, Notification::where('vehicle_id', $vehicle->id)->count());
    }

    public function test_user_can_create_an_alert_from_the_interface(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AlertForm::class)
            ->set('name', 'SUV moins de 20M')
            ->set('brand', 'Toyota')
            ->set('minYear', '2020')
            ->set('maxPrice', '20000000')
            ->set('frequency', AlertFrequency::Immediate->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('alerts', [
            'user_id' => $user->id,
            'name' => 'SUV moins de 20M',
            'brand' => 'Toyota',
        ]);
    }

    public function test_alert_edit_form_loads_the_existing_alert(): void
    {
        $user = User::factory()->create();
        $alert = $this->alert(['user_id' => $user->id, 'name' => 'Berline diesel']);

        Livewire::actingAs($user)
            ->test(AlertForm::class, ['alert' => $alert])
            ->assertSet('name', 'Berline diesel');
    }

    public function test_alert_list_is_scoped_to_the_current_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->alert(['user_id' => $user->id, 'name' => 'Moi']);
        $this->alert(['user_id' => $other->id, 'name' => 'Pas moi']);

        $this->actingAs($user)->get('/alertes')->assertOk()->assertSee('Moi')->assertDontSee('Pas moi');
    }

    public function test_alert_routes_require_authentication(): void
    {
        $this->get('/alertes')->assertRedirect('/login');
        $this->get('/alertes/nouvelle')->assertRedirect('/login');
    }

    public function test_admins_can_monitor_alerts(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->alert(['name' => 'Alerte surveillee']);

        $this->actingAs($admin)->get('/admin/alertes')->assertOk()->assertSee('Alerte surveillee');
    }
}
