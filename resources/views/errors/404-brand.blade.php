<x-layouts.app>
    <div class="container-app py-24 text-center">
        <h1 class="text-3xl font-bold text-ink-900">Aucune annonce pour « {{ $brand }} »</h1>
        <p class="mt-3 text-ink-500">Cette marque n a pas encore de vehicule disponible.</p>
        <a href="{{ route('vehicles.index') }}" class="btn-primary mt-6">Voir le catalogue</a>
    </div>
</x-layouts.app>
