<?php

namespace App\Livewire\Admin;

use App\Enums\BodyType;
use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Enums\VehicleStatus;
use App\Jobs\MatchAlertsForVehicle;
use App\Models\Source;
use App\Models\Vehicle;
use App\Services\ImageStorageService;
use App\Services\VehicleImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
class VehicleForm extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?Vehicle $vehicle = null;

    public string $brand = '';

    public string $model = '';

    public string $year = '';

    public string $price = '';

    public string $mileage = '';

    public string $fuel = 'PETROL';

    public string $transmission = 'MANUAL';

    public string $bodyType = 'SEDAN';

    public string $color = '';

    public string $location = '';

    public string $description = '';

    public string $status = 'DRAFT';

    public string $sourceUrl = '';

    public string $sourceId = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $images = [];

    public function mount(?Vehicle $vehicle = null): void
    {
        if (! $vehicle) {
            return;
        }

        $this->vehicle = $vehicle->load('images');

        $this->fill([
            'brand' => $vehicle->brand,
            'model' => $vehicle->model,
            'year' => (string) $vehicle->year,
            'price' => (string) $vehicle->price,
            'mileage' => (string) $vehicle->mileage,
            'fuel' => $vehicle->fuel->value,
            'transmission' => $vehicle->transmission->value,
            'bodyType' => $vehicle->body_type->value,
            'color' => (string) $vehicle->color,
            'location' => (string) $vehicle->location,
            'description' => (string) $vehicle->description,
            'status' => $vehicle->status->value,
            'sourceUrl' => (string) $vehicle->source_url,
            'sourceId' => (string) $vehicle->source_id,
        ]);
    }

    protected function rules(): array
    {
        return [
            'brand' => ['required', 'string', 'min:1', 'max:60'],
            'model' => ['required', 'string', 'min:1', 'max:60'],
            'year' => ['required', 'integer', 'min:1900', 'max:'.((int) date('Y') + 2)],
            'price' => ['required', 'integer', 'min:0', 'max:2000000000'],
            'mileage' => ['required', 'integer', 'min:0', 'max:2000000'],
            'fuel' => ['required', Rule::in(array_keys(FuelType::options()))],
            'transmission' => ['required', Rule::in(array_keys(Transmission::options()))],
            'bodyType' => ['required', Rule::in(array_keys(BodyType::options()))],
            'color' => ['nullable', 'string', 'max:40'],
            'location' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:6000'],
            'status' => ['required', Rule::in(array_keys(VehicleStatus::options()))],
            'sourceUrl' => ['nullable', 'url:http,https', 'max:2048'],
            'sourceId' => ['nullable', 'integer', 'exists:sources,id'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'brand' => 'marque',
            'model' => 'modele',
            'year' => 'annee',
            'price' => 'prix',
            'mileage' => 'kilometrage',
            'bodyType' => 'carrosserie',
            'sourceUrl' => 'URL source',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $status = VehicleStatus::from($this->status);

        if ($this->vehicle && ! $this->vehicle->status->canTransitionTo($status)) {
            $this->addError('status', "Transition {$this->vehicle->status->label()} vers {$status->label()} non autorisee.");

            return;
        }

        $payload = [
            'brand' => trim($this->brand),
            'model' => trim($this->model),
            'year' => (int) $this->year,
            'price' => (int) $this->price,
            'mileage' => (int) $this->mileage,
            'fuel' => $this->fuel,
            'transmission' => $this->transmission,
            'body_type' => $this->bodyType,
            'color' => $this->color ?: null,
            'location' => $this->location ?: null,
            'description' => $this->description ?: null,
            'source_url' => $this->sourceUrl ?: null,
            'source_id' => $this->sourceId !== '' ? (int) $this->sourceId : null,
        ];

        $wasPublished = $this->vehicle?->isPublished() ?? false;

        DB::transaction(function () use ($payload, $status): void {
            if ($this->vehicle) {
                $this->vehicle->update($payload + [
                    'status' => $status,
                    'published_at' => $status === VehicleStatus::Published
                        ? ($this->vehicle->published_at ?? now())
                        : $this->vehicle->published_at,
                ]);
            } else {
                $this->vehicle = Vehicle::create($payload + [
                    'status' => $status,
                    'reference' => app(VehicleImportService::class)->nextReference(),
                    'published_at' => $status === VehicleStatus::Published ? now() : null,
                ]);
            }
        });

        $this->storeUploads();

        if ($status === VehicleStatus::Published && ! $wasPublished) {
            MatchAlertsForVehicle::dispatch($this->vehicle->id);
        }

        $this->dispatch('toast', message: 'Vehicule enregistre.', type: 'success');

        $this->redirectRoute('admin.vehicles.index', navigate: true);
    }

    /** @return array<int, string> */
    protected function storeUploads(): array
    {
        if ($this->images === []) {
            return [];
        }

        $this->validateOnly('images');

        $storage = app(ImageStorageService::class);
        $sortOrder = $this->vehicle->images()->count();
        $stored = [];

        foreach ($this->images as $file) {
            $asset = $storage->store($file);

            $image = $this->vehicle->images()->create([
                'url' => $asset['url'],
                'public_id' => $asset['public_id'],
                'provider' => $asset['provider'],
                'alt' => $this->vehicle->title(),
                'sort_order' => $sortOrder++,
                'is_primary' => $sortOrder === 1 && $this->vehicle->images()->count() === 1,
            ]);

            $stored[] = $image->id;
        }

        $this->reset('images');

        $this->ensurePrimaryImage();

        return $stored;
    }

    public function removeImage(int $imageId): void
    {
        $image = $this->vehicle?->images()->find($imageId);

        if (! $image) {
            return;
        }

        app(ImageStorageService::class)->delete($image->public_id, $image->provider);
        $image->delete();

        $this->ensurePrimaryImage();
    }

    public function makePrimary(int $imageId): void
    {
        $images = $this->vehicle->images()->orderBy('sort_order')->get();
        $reordered = $images->sortBy(fn ($image) => $image->id === $imageId ? 0 : 1)->values();

        foreach ($reordered as $index => $image) {
            $image->update(['sort_order' => $index, 'is_primary' => $index === 0]);
        }
    }

    public function moveImage(int $imageId, int $direction): void
    {
        $images = $this->vehicle->images()->orderBy('sort_order')->get()->values();
        $index = $images->search(fn ($image) => $image->id === $imageId);

        if ($index === false) {
            return;
        }

        $target = $index + $direction;
        if ($target < 0 || $target >= $images->count()) {
            return;
        }

        $images->splice($target, 0, $images->splice($index, 1)[0]);

        foreach ($images as $position => $image) {
            $image->update(['sort_order' => $position, 'is_primary' => $position === 0]);
        }
    }

    private function ensurePrimaryImage(): void
    {
        if ($this->vehicle->images()->where('is_primary', true)->exists()) {
            return;
        }

        $first = $this->vehicle->images()->orderBy('sort_order')->first();

        $first?->update(['is_primary' => true]);
    }

    public function render(): View
    {
        return view('livewire.admin.vehicle-form', [
            'vehicle' => $this->vehicle?->fresh(['images', 'source']),
            'sources' => Source::query()->orderBy('name')->get(),
            'fuelOptions' => FuelType::options(),
            'transmissionOptions' => Transmission::options(),
            'bodyTypeOptions' => BodyType::options(),
            'statusOptions' => VehicleStatus::options(),
            'allowedTransitions' => $this->vehicle
                ? collect($this->vehicle->status->allowedTransitions())->mapWithKeys(
                    fn (VehicleStatus $status) => [$status->value => $status->label()]
                )->all()
                : VehicleStatus::options(),
            'imageProvider' => ImageStorageService::provider(),
        ])->title($this->vehicle ? 'Modifier le vehicule | AutoAlert' : 'Ajouter un vehicule | AutoAlert');
    }
}
