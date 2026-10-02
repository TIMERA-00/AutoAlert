<?php

namespace App\Livewire\Alerts;

use App\Enums\AlertFrequency;
use App\Enums\BodyType;
use App\Enums\FuelType;
use App\Enums\NotificationChannel;
use App\Enums\Transmission;
use App\Models\Alert;
use App\Models\Vehicle;
use App\Services\MatchingService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AlertForm extends Component
{
    public ?Alert $alert = null;

    public string $name = '';

    public string $brand = '';

    public string $model = '';

    public string $minYear = '';

    public string $maxYear = '';

    public string $minPrice = '';

    public string $maxPrice = '';

    public string $maxMileage = '';

    public string $fuel = '';

    public string $transmission = '';

    public string $bodyType = '';

    public string $location = '';

    public string $frequency = 'IMMEDIATE';

    public bool $isActive = true;

    public function mount(?Alert $alert = null): void
    {
        if ($alert) {
            abort_unless($alert->user_id === auth()->id(), 404);
            $this->alert = $alert;
            $this->fill([
                'name' => $alert->name,
                'brand' => (string) $alert->brand,
                'model' => (string) $alert->model,
                'minYear' => (string) $alert->min_year,
                'maxYear' => (string) $alert->max_year,
                'minPrice' => (string) $alert->min_price,
                'maxPrice' => (string) $alert->max_price,
                'maxMileage' => (string) $alert->max_mileage,
                'fuel' => $alert->fuel?->value ?? '',
                'transmission' => $alert->transmission?->value ?? '',
                'bodyType' => $alert->body_type?->value ?? '',
                'location' => (string) $alert->location,
                'frequency' => $alert->frequency->value,
                'isActive' => $alert->is_active,
            ]);
        }

        $prefill = session('alert_prefill');
        if (is_array($prefill) && ! $this->alert) {
            $this->fill(array_intersect_key($prefill, array_flip([
                'name', 'brand', 'model', 'minYear', 'maxYear', 'minPrice',
                'maxPrice', 'maxMileage', 'fuel', 'transmission', 'bodyType', 'location',
            ])));
        }

        // "Creer une alerte" depuis la fiche vehicule (?vehicule=ID)
        if (! $this->alert && request()->integer('vehicule') > 0) {
            $this->prefillFromVehicle(request()->integer('vehicule'));
        }
    }

    /** Pre-fill from the vehicle detail page ("Creer une alerte" sur ce vehicule). */
    public function prefillFromVehicle(int $vehicleId): void
    {
        $vehicle = Vehicle::published()->find($vehicleId);

        if (! $vehicle) {
            return;
        }

        $this->fill([
            'name' => $vehicle->title(),
            'brand' => $vehicle->brand,
            'model' => $vehicle->model,
            'minYear' => (string) $vehicle->year,
            'maxPrice' => (string) $vehicle->price,
            'maxMileage' => (string) $vehicle->mileage,
            'fuel' => $vehicle->fuel->value,
            'transmission' => $vehicle->transmission->value,
            'bodyType' => $vehicle->body_type->value,
            'location' => (string) $vehicle->location,
        ]);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'brand' => ['nullable', 'string', 'max:60'],
            'model' => ['nullable', 'string', 'max:60'],
            'minYear' => ['nullable', 'integer', 'min:1900', 'max:'.(int) date('Y')],
            'maxYear' => ['nullable', 'integer', 'min:1900', 'max:'.((int) date('Y') + 2)],
            'minPrice' => ['nullable', 'integer', 'min:0'],
            'maxPrice' => ['nullable', 'integer', 'min:0', 'gte:minPrice'],
            'maxMileage' => ['nullable', 'integer', 'min:0'],
            'fuel' => ['nullable', Rule::in(array_keys(FuelType::options()))],
            'transmission' => ['nullable', Rule::in(array_keys(Transmission::options()))],
            'bodyType' => ['nullable', Rule::in(array_keys(BodyType::options()))],
            'location' => ['nullable', 'string', 'max:80'],
            'frequency' => ['required', Rule::in(array_keys(AlertFrequency::options()))],
            'isActive' => ['boolean'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'nom de l alerte',
            'minPrice' => 'prix minimum',
            'maxPrice' => 'prix maximum',
            'maxMileage' => 'kilometrage maximum',
        ];
    }

    /** Turn the current form values into a live preview of matching vehicles. */
    public function getPreviewProperty()
    {
        if ($this->alert) {
            return app(MatchingService::class)->vehiclesFor($this->alert, 6);
        }

        $draft = new Alert([
            'brand' => $this->brand ?: null,
            'model' => $this->model ?: null,
            'min_year' => $this->minYear !== '' ? (int) $this->minYear : null,
            'max_year' => $this->maxYear !== '' ? (int) $this->maxYear : null,
            'min_price' => $this->minPrice !== '' ? (int) $this->minPrice : null,
            'max_price' => $this->maxPrice !== '' ? (int) $this->maxPrice : null,
            'max_mileage' => $this->maxMileage !== '' ? (int) $this->maxMileage : null,
            'fuel' => $this->fuel ?: null,
            'transmission' => $this->transmission ?: null,
            'body_type' => $this->bodyType ?: null,
            'location' => $this->location ?: null,
        ]);

        return app(MatchingService::class)->vehiclesFor($draft, 6);
    }

    public function save(): void
    {
        $this->validate();

        $criteria = collect([
            $this->brand, $this->model, $this->minYear, $this->maxYear,
            $this->minPrice, $this->maxPrice, $this->maxMileage,
            $this->fuel, $this->transmission, $this->bodyType, $this->location,
        ])->filter(fn ($value) => $value !== null && $value !== '')->isEmpty();

        if ($criteria) {
            $this->addError('name', 'Ajoutez au moins un critere de recherche a votre alerte.');

            return;
        }

        $payload = [
            'name' => trim($this->name),
            'brand' => $this->brand ?: null,
            'model' => $this->model ?: null,
            'min_year' => $this->minYear !== '' ? (int) $this->minYear : null,
            'max_year' => $this->maxYear !== '' ? (int) $this->maxYear : null,
            'min_price' => $this->minPrice !== '' ? (int) $this->minPrice : null,
            'max_price' => $this->maxPrice !== '' ? (int) $this->maxPrice : null,
            'max_mileage' => $this->maxMileage !== '' ? (int) $this->maxMileage : null,
            'fuel' => $this->fuel ?: null,
            'transmission' => $this->transmission ?: null,
            'body_type' => $this->bodyType ?: null,
            'location' => $this->location ?: null,
            'frequency' => $this->frequency,
            'is_active' => $this->isActive,
        ];

        if ($this->alert) {
            $this->alert->update($payload);
            $this->dispatch('alert-saved', message: 'Alerte mise a jour.');
        } else {
            auth()->user()->alerts()->create($payload);
            $this->dispatch('alert-saved', message: 'Alerte creee. Vous serez notifie des nouvelles annonces.');
        }

        $this->redirectRoute('alerts.index', navigate: true);
    }

    public function render(MatchingService $matching): View
    {
        return view('livewire.alerts.alert-form', [
            'frequencyOptions' => AlertFrequency::options(),
            'fuelOptions' => FuelType::options(),
            'transmissionOptions' => Transmission::options(),
            'bodyTypeOptions' => BodyType::options(),
            'channelOptions' => NotificationChannel::options(),
        ])->title($this->alert ? 'Modifier mon alerte | AutoAlert' : 'Creer une alerte | AutoAlert');
    }
}
