<x-layouts.app>
    <div class="container-app py-10">
        <div class="grid gap-8 lg:grid-cols-[240px_minmax(0,1fr)]">
            {{-- Admin nav --}}
            <nav class="lg:sticky lg:top-24 lg:h-fit" aria-label="Navigation administration">
                <div class="card p-3">
                    @php
                        $items = [
                            ['admin.dashboard', 'Dashboard', 'M5.3 3v4M10.3 3v4M4 5h5.3v5H4zM11.7 11h5.3v5h-5.3zM11.7 16v5M19.3 3v5M19.3 11v3M16 21v-5M22 21v-5'],
                            ['admin.vehicles.index', 'Vehicules', 'M2.25 12.75l1.5-4.5A2.25 2.25 0 0 1 5.9 6.75h12.2a2.25 2.25 0 0 1 2.15 1.5l1.5 4.5M2.25 12.75h19.5'],
                            ['admin.sources.index', 'Sources', 'M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757'],
                            ['admin.users.index', 'Utilisateurs', 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0z'],
                            ['admin.alerts.index', 'Alertes', 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75'],
                            ['admin.notifications.index', 'Notifications', 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75'],
                            ['admin.stats', 'Statistiques', 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125z'],
                        ];
                    @endphp

                    @foreach ($items as [$route, $label, $icon])
                        @php $active = request()->routeIs($route); @endphp
                        <a href="{{ route($route) }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition
                                  {{ $active ? 'bg-brand-50 text-brand-700' : 'text-ink-600 hover:bg-ink-50' }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                            </svg>
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </nav>

            <div>{{ $slot }}</div>
        </div>
    </div>
</x-layouts.app>