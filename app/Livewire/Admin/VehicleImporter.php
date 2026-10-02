<?php

namespace App\Livewire\Admin;

use App\Enums\BodyType;
use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Enums\VehicleStatus;
use App\Jobs\MatchAlertsForVehicle;
use App\Models\Source;
use App\Models\Vehicle;
use App\Services\VehicleImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Import flow: URL -> preview -> admin corrections -> creation.
 * Duplicate listings are surfaced before anything is written.
 */
#[Layout('layouts.admin')]
class VehicleImporter extends Component
{
    public string $url = '';

    public string $step = 'url';

    /** @var array<string, mixed>|null */
    public ?array $preview = null;

    public array $form = [
        'brand' => '',
        'model' => '',
        'year' => '',
        'price' => '',
        'mileage' => '',
        'fuel' => '',
        'transmission' => '',
        'body_type' => '',
        'color' => '',
        'location' => '',
        'description' => '',
    ];

    public bool $publishNow = false;

    public string $sourceId = '';

    public string $error = '';

    public function analyse(): void
    {
        $this->reset('error', 'preview');

        $this->validate([
            'url' => ['required', 'url:http,https', 'max:2048'],
        ], attributes: ['url' => 'URL de l annonce']);

        try {
            $preview = app(VehicleImportService::class)->preview($this->url);
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->preview = $preview;
        $this->sourceId = (string) ($preview['fields']['source_id'] ?? '');
        $this->form = array_merge($this->form, array_map(
            fn ($value) => is_bool($value) ? $value : ($value instanceof \BackedEnum ? $value->value : (string) $value),
            array_filter(
                $preview['fields'],
                fn ($key) => $key !== 'images',
                ARRAY_FILTER_USE_KEY
            )
        ));

        $this->step = 'preview';
    }

    /** Manual fallback: the admin fills everything by hand. */
    public function startManual(): void
    {
        $this->preview = null;
        $this->step = 'form';
    }

    public function create()
    {
        $this->validate([
            'form.brand' => ['required', 'string', 'max:60'],
            'form.model' => ['required', 'string', 'max:60'],
            'form.year' => ['required', 'integer', 'min:1900', 'max:'.((int) date('Y') + 2)],
            'form.price' => ['required', 'integer', 'min:0'],
            'form.mileage' => ['nullable', 'integer', 'min:0'],
            'form.fuel' => ['nullable', Rule::in(array_keys(FuelType::options()))],
            'form.transmission' => ['nullable', Rule::in(array_keys(Transmission::options()))],
            'form.body_type' => ['nullable', Rule::in(array_keys(BodyType::options()))],
            'sourceId' => ['nullable', 'integer', 'exists:sources,id'],
        ]);

        $status = $this->publishNow ? VehicleStatus::Published : VehicleStatus::Draft;

        $vehicle = Vehicle::create([
            'source_id' => $this->sourceId !== '' ? (int) $this->sourceId : null,
            'source_url' => $this->url ?: null,
            'brand' => trim($this->form['brand']),
            'model' => trim($this->form['model']),
            'year' => (int) $this->form['year'],
            'price' => (int) $this->form['price'],
            'mileage' => (int) ($this->form['mileage'] ?: 0),
            'fuel' => $this->form['fuel'] ?: FuelType::Petrol->value,
            'transmission' => $this->form['transmission'] ?: Transmission::Manual->value,
            'body_type' => $this->form['body_type'] ?: BodyType::Sedan->value,
            'color' => $this->form['color'] ?: null,
            'location' => $this->form['location'] ?: null,
            'description' => $this->form['description'] ?: null,
            'status' => $status,
            'reference' => app(VehicleImportService::class)->nextReference(),
            'published_at' => $status === VehicleStatus::Published ? now() : null,
        ]);

        if (is_array($this->preview)) {
            $sort = 0;
            foreach (array_slice($this->preview['fields']['images'] ?? [], 0, 8) as $imageUrl) {
                $vehicle->images()->create([
                    'url' => $imageUrl,
                    'provider' => 'external',
                    'sort_order' => $sort,
                    'is_primary' => $sort === 0,
                    'alt' => $vehicle->title(),
                ]);
                $sort++;
            }
        }

        if ($status === VehicleStatus::Published) {
            MatchAlertsForVehicle::dispatch($vehicle->id);
        }

        session()->flash('success', 'Vehicule cree'.($status === VehicleStatus::Published ? ' et publie.' : ' en brouillon.'));

        return $this->redirectRoute('admin.vehicles.index', navigate: false);
    }

    public function render(): View
    {
        return view('livewire.admin.vehicle-importer', [
            'sources' => Source::query()->orderBy('name')->get(),
            'fuelOptions' => FuelType::options(),
            'transmissionOptions' => Transmission::options(),
            'bodyTypeOptions' => BodyType::options(),
            'importEnabled' => (bool) config('import.enabled'),
        ])->title('Importer une annonce | AutoAlert');
    }
}
