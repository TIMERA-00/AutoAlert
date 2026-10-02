<?php

namespace Tests\Feature;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders(): void
    {
        $this->get('/')->assertOk()->assertSee('AutoAlert', false);
    }

    public function test_catalogue_lists_only_published_vehicles(): void
    {
        Vehicle::factory()->published()->create(['brand' => 'Toyota', 'model' => 'RAV4']);
        Vehicle::factory()->draft()->create(['brand' => 'Renault', 'model' => 'Clio']);

        $response = $this->get('/vehicles');

        $response->assertOk();
        $this->assertSame(1, Vehicle::published()->count());
        $response->assertSee('Toyota', false);
        $response->assertDontSee('Clio', false);
    }

    public function test_catalogue_can_be_filtered_by_brand(): void
    {
        Vehicle::factory()->published()->create(['brand' => 'Toyota', 'model' => 'RAV4']);
        Vehicle::factory()->published()->create(['brand' => 'Renault', 'model' => 'Clio']);

        $response = $this->get('/vehicles?marque=Toyota');

        $response->assertOk();
        $this->assertSame(1, Vehicle::published()->where('brand', 'Toyota')->count());
    }

    public function test_vehicle_detail_page_is_reachable_by_slug(): void
    {
        $vehicle = Vehicle::factory()->published()->create(['brand' => 'Toyota', 'model' => 'RAV4']);

        $this->get("/vehicles/{$vehicle->slug}")
            ->assertOk()
            ->assertSee('RAV4', false);
    }

    public function test_draft_vehicle_detail_returns_404(): void
    {
        $vehicle = Vehicle::factory()->draft()->create();

        $this->get("/vehicles/{$vehicle->slug}")->assertNotFound();
    }

    public function test_brand_page_redirects_to_filtered_catalogue(): void
    {
        Vehicle::factory()->published()->create(['brand' => 'Toyota']);

        $this->get('/marques/Toyota')
            ->assertRedirect(route('vehicles.index', ['marque' => 'Toyota']));
    }

    public function test_unknown_brand_returns_404_page(): void
    {
        $this->get('/marques/ferrari')->assertNotFound();
    }

    public function test_source_redirect_sends_visitor_to_the_original_listing(): void
    {
        $vehicle = Vehicle::factory()->published()->create(['source_url' => 'https://example.test/annonce/1']);

        $this->get("/source/{$vehicle->slug}")->assertRedirect('https://example.test/annonce/1');
    }

    public function test_static_pages_render(): void
    {
        $this->get('/aide')->assertOk();
        $this->get('/mentions-legales')->assertOk();
        $this->get('/contact')->assertOk();
    }

    public function test_vehicle_title_helper(): void
    {
        $vehicle = Vehicle::factory()->make(['brand' => 'Toyota', 'model' => 'RAV4', 'year' => 2023]);

        $this->assertSame('Toyota RAV4 2023', $vehicle->title());
    }

    public function test_vehicle_status_helpers(): void
    {
        $this->assertTrue(Vehicle::factory()->make(['status' => VehicleStatus::Published])->isPublished());
        $this->assertFalse(Vehicle::factory()->make(['status' => VehicleStatus::Draft])->isPublished());
    }
}
