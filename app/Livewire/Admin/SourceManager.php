<?php

namespace App\Livewire\Admin;

use App\Enums\SourceType;
use App\Models\Source;
use App\Models\Vehicle;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class SourceManager extends Component
{
    public ?Source $editing = null;

    public string $name = '';

    public string $baseUrl = '';

    public string $type = 'MANUAL';

    public bool $isActive = true;

    public string $notes = '';

    public function edit(int $sourceId): void
    {
        $source = Source::findOrFail($sourceId);
        $this->editing = $source;
        $this->fill([
            'name' => $source->name,
            'baseUrl' => $source->base_url,
            'type' => $source->type->value,
            'isActive' => $source->is_active,
            'notes' => (string) $source->notes,
        ]);
    }

    public function resetForm(): void
    {
        $this->editing = null;
        $this->reset(['name', 'baseUrl', 'type', 'isActive', 'notes']);
        $this->type = 'MANUAL';
        $this->isActive = true;
    }

    public function toggle(int $sourceId): void
    {
        $source = Source::findOrFail($sourceId);
        $source->update(['is_active' => ! $source->is_active]);
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('sources', 'name')->ignore($this->editing?->id)],
            'baseUrl' => ['required', 'url:http,https', 'max:255'],
            'type' => ['required', Rule::in(array_keys(SourceType::options()))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], attributes: ['baseUrl' => 'URL']);

        $payload = [
            'name' => trim($this->name),
            'base_url' => rtrim(trim($this->baseUrl), '/'),
            'type' => $this->type,
            'is_active' => $this->isActive,
            'notes' => $this->notes ?: null,
        ];

        if ($this->editing) {
            $this->editing->update($payload);
        } else {
            Source::create($payload);
        }

        $this->resetForm();
        $this->dispatch('toast', message: 'Source enregistree.', type: 'success');
    }

    public function delete(int $sourceId): void
    {
        $source = Source::withCount('vehicles')->findOrFail($sourceId);

        if ($source->vehicles_count > 0) {
            $this->dispatch('toast', message: 'Desacrivez la source avant de la supprimer.', type: 'error');

            return;
        }

        $source->delete();
    }

    public function render(): View
    {
        return view('livewire.admin.source-manager', [
            'sources' => Source::withCount('vehicles')->orderBy('name')->get(),
            'typeOptions' => SourceType::options(),
            'vehiclesWithoutSource' => Vehicle::whereNull('source_id')->count(),
        ])->title('Administration - sources | AutoAlert');
    }
}
