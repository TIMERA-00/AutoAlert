@php
    $unread = auth()->check()
        ? \App\Models\Notification::where('user_id', auth()->id())->whereNull('read_at')->count()
        : 0;
@endphp

<header class="sticky top-0 z-40 border-b border-ink-200 bg-white/90 backdrop-blur" x-data="{ mobile: false }">
    <div class="container-app flex h-16 items-center justify-between gap-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2 font-bold text-ink-900">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-600 text-white">AA</span>
            <span class="text-lg tracking-tight">AutoAlert</span>
        </a>

        <nav class="hidden items-center gap-1 md:flex" aria-label="Navigation principale">
            <a href="{{ route('vehicles.index') }}"
               class="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100 hover:text-ink-900">Catalogue</a>
            @auth
                <a href="{{ route('alerts.index') }}"
                   class="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100 hover:text-ink-900">Mes alertes</a>
                <a href="{{ route('favorites.index') }}"
                   class="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100 hover:text-ink-900">Favoris</a>
            @endauth
            <a href="{{ route('help') }}"
               class="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100 hover:text-ink-900">Comment ca marche</a>
        </nav>

        <div class="hidden items-center gap-2 md:flex">
            @auth
                <a href="{{ route('notifications.index') }}"
                   class="relative rounded-lg p-2 text-ink-600 hover:bg-ink-100"
                   aria-label="Notifications ({{ $unread }} non lues)">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                    </svg>
                    @if ($unread > 0)
                        <span class="absolute -right-0.5 -top-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                            {{ $unread > 9 ? '9+' : $unread }}
                        </span>
                    @endif
                </a>

                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="btn-secondary btn-sm">Administration</a>
                @endif

                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="btn-ghost btn-sm" aria-haspopup="true" aria-expanded="false">
                        <span class="grid h-7 w-7 place-items-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">
                            {{ auth()->user()->initials }}
                        </span>
                        <span class="hidden lg:inline">{{ auth()->user()->first_name }}</span>
                    </button>
                    <div x-cloak x-show="open" @click.outside="open = false"
                         class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-ink-200 bg-white py-1 shadow-lg">
                        <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-sm text-ink-700 hover:bg-ink-50">Mon tableau de bord</a>
                        <a href="{{ route('alerts.index') }}" class="block px-4 py-2 text-sm text-ink-700 hover:bg-ink-50">Mes alertes</a>
                        <a href="{{ route('notifications.index') }}" class="block px-4 py-2 text-sm text-ink-700 hover:bg-ink-50">Notifications</a>
                        <a href="{{ route('preferences.edit') }}" class="block px-4 py-2 text-sm text-ink-700 hover:bg-ink-50">Preferences</a>
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-ink-700 hover:bg-ink-50">Profil</a>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-ink-100">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                                Deconnexion
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn-ghost btn-sm">Connexion</a>
                <a href="{{ route('register') }}" class="btn-primary btn-sm">Creer un compte</a>
            @endauth
        </div>

        <button @click="mobile = !mobile" class="btn-ghost btn-sm md:hidden" aria-label="Menu">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
            </svg>
        </button>
    </div>

    <div x-cloak x-show="mobile" class="border-t border-ink-200 bg-white md:hidden">
        <nav class="container-app flex flex-col gap-1 py-3 text-sm">
            <a href="{{ route('vehicles.index') }}" class="rounded-lg px-3 py-2 font-medium text-ink-700 hover:bg-ink-100">Catalogue</a>
            @auth
                <a href="{{ route('alerts.index') }}" class="rounded-lg px-3 py-2 font-medium text-ink-700 hover:bg-ink-100">Mes alertes</a>
                <a href="{{ route('favorites.index') }}" class="rounded-lg px-3 py-2 font-medium text-ink-700 hover:bg-ink-100">Mes favoris</a>
                <a href="{{ route('notifications.index') }}" class="rounded-lg px-3 py-2 font-medium text-ink-700 hover:bg-ink-100">Notifications ({{ $unread }})</a>
                <a href="{{ route('dashboard') }}" class="rounded-lg px-3 py-2 font-medium text-ink-700 hover:bg-ink-100">Mon tableau de bord</a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="rounded-lg px-3 py-2 font-medium text-brand-700 hover:bg-brand-50">Administration</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg px-3 py-2 text-left font-medium text-red-600 hover:bg-red-50">Deconnexion</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 font-medium text-ink-700 hover:bg-ink-100">Connexion</a>
                <a href="{{ route('register') }}" class="btn-primary mt-2">Creer un compte</a>
            @endauth
        </nav>
    </div>
</header>