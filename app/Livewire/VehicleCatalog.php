<?php

namespace App\Livewire;

use App\Enums\BodyType;
use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Enums\VehicleSort;
use App\Models\SearchQuery;
use App\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class VehicleCatalog extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'marque', except: '')]
    public string $brand = '';

    #[Url(as: 'modele', except: '')]
    public string $model = '';

    #[Url(as: 'prix_min', except: '')]
    public string $minPrice = '';

    #[Url(as: 'prix_max', except: '')]
    public string $maxPrice = '';

    #[Url(as: 'annee_min', except: '')]
    public string $minYear = '';

    #[Url(as: 'km_max', except: '')]
    public string $maxMileage = '';

    /** @var array<int, string> */
    #[Url(as: 'carburant', except: '')]
    public array $fuel = [];

    /** @var array<int, string> */
    #[Url(as: 'boite', except: '')]
    public array $transmission = [];

    /** @var array<int, string> */
    #[Url(as: 'carrosserie', except: '')]
    public array $bodyType = [];

    #[Url(as: 'lieu', except: '')]
    public string $location = '';

    #[Url(as: 'tri', except: 'recent')]
    public string $sort = 'recent';

    public bool $filtersOpen = false;

    public function mount(): void
    {
        $this->sort = (VehicleSort::tryFrom($this->sort) ?? VehicleSort::Recent)->value;
    }

    public function updated(string $property): void
    {
        if ($property === 'sort') {
            $this->sort = (VehicleSort::tryFrom($this->sort) ?? VehicleSort::Recent)->value;
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search', 'brand', 'model', 'minPrice', 'maxPrice',
            'minYear', 'maxMileage', 'fuel', 'transmission', 'bodyType', 'location',
        ]);

        $this->resetPage();
    }

    public function toggleFilter(string $group, string $value): void
    {
        $current = $this->{$group} ?? [];

        $this->{$group} = in_array($value, $current, true)
            ? array_values(array_diff($current, [$value]))
            : [...$current, $value];

        $this->resetPage();
    }

    public function createAlertFromFilters(): void
    {
        session()->flash('alert_prefill', array_filter([
            'name' => $this->brand ? trim($this->brand.' '.$this->model) : 'Ma recherche',
            'brand' => $this->brand ?: null,
            'model' => $this->model ?: null,
            'maxPrice' => $this->maxPrice !== '' ? $this->maxPrice : null,
            'minYear' => $this->minYear !== '' ? $this->minYear : null,
            'maxMileage' => $this->maxMileage !== '' ? $this->maxMileage : null,
            'fuel' => $this->fuel[0] ?? null,
            'transmission' => $this->transmission[0] ?? null,
            'bodyType' => $this->bodyType[0] ?? null,
            'location' => $this->location ?: null,
        ], fn ($value) => $value !== null && $value !== ''));

        $this->redirectRoute('alerts.create', navigate: true);
    }

    // --------------------------------------------------------------- querying

    #[Computed]
    public function query(): Builder
    {
        return Vehicle::query()
            ->published()
            ->searchable($this->search)
            ->filterBy([
                'brand' => $this->brand ?: null,
                'model' => $this->model ?: null,
                'minPrice' => $this->minPrice !== '' ? (int) $this->minPrice : null,
                'maxPrice' => $this->maxPrice !== '' ? (int) $this->maxPrice : null,
                'minYear' => $this->minYear !== '' ? (int) $this->minYear : null,
                'maxMileage' => $this->maxMileage !== '' ? (int) $this->maxMileage : null,
                'fuel' => $this->fuel ?: null,
                'transmission' => $this->transmission ?: null,
                'bodyType' => $this->bodyType ?: null,
                'location' => $this->location ?: null,
            ])
            ->sortBy($this->sort);
    }

    public function getRowsProperty(): LengthAwarePaginator
    {
        return $this->query()->with('images')->paginate(12);
    }

    protected function trackSearch(): void
    {
        if (blank($this->search) && blank($this->brand) && blank($this->model)) {
            return;
        }

        SearchQuery::create([
            'user_id' => auth()->id(),
            'term' => trim(($this->search ?: trim($this->brand.' '.$this->model)) ?: '*') ?: '*',
            'filters' => $this->activeFilterPayload(),
            'results' => 0,
        ]);
    }

    /** @return array<string, mixed> */
    public function activeFilterPayload(): array
    {
        return array_filter([
            'q' => $this->search ?: null,
            'brand' => $this->brand ?: null,
            'model' => $this->model ?: null,
            'minPrice' => $this->minPrice ?: null,
            'maxPrice' => $this->maxPrice ?: null,
            'minYear' => $this->minYear ?: null,
            'maxMileage' => $this->maxMileage ?: null,
            'fuel' => $this->fuel ?: null,
            'transmission' => $this->transmission ?: null,
            'bodyType' => $this->bodyType ?: null,
            'location' => $this->location ?: null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    public function getHasActiveFiltersProperty(): bool
    {
        return $this->activeFilterPayload() !== [];
    }

    // ----------------------------------------------------------------- facets

    /** @return Collection<int, string> */
    #[Computed]
    public function brands(): Collection
    {
        return Vehicle::published()
            ->select('brand')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('brand')
            ->orderByDesc('aggregate')
            ->limit(40)
            ->pluck('brand');
    }

    /** @return Collection<int, string> */
    #[Computed]
    public function locations(): Collection
    {
        return Vehicle::published()
            ->whereNotNull('location')
            ->select('location')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('location')
            ->orderByDesc('aggregate')
            ->limit(30)
            ->pluck('location');
    }

    #[Computed]
    public function priceBounds(): array
    {
        $row = Vehicle::published()
            ->selectRaw('COALESCE(MIN(price),0) as min_price, COALESCE(MAX(price),0) as max_price')
            ->first();

        return ['min' => (int) $row->min_price, 'max' => (int) $row->max_price];
    }

    public function render(): View
    {
        $vehicles = $this->getRowsProperty();

        return view('livewire.vehicle-catalog', [
            'vehicles' => $vehicles,
            'fuelOptions' => FuelType::options(),
            'transmissionOptions' => Transmission::options(),
            'bodyTypeOptions' => BodyType::options(),
            'sortOptions' => VehicleSort::options(),
        ])->title(fn () => $this->pageTitle());
    }

    private function pageTitle(): string
    {
        $parts = [];

        if ($this->search) {
            $parts[] = 'Recherche "'.$this->search.'"';
        }
        if ($this->brand) {
            $parts[] = $this->brand;
        }
        if ($this->maxPrice) {
            $parts[] = 'jusqu a '.number_format((int) $this->maxPrice, 0, ',', ' ').' FCFA';
        }

        return $parts === []
            ? 'Vehicules disponibles a la vente'
            : implode(' - ', $parts).' | AutoAlert';
    }
}
