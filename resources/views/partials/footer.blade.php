<footer class="mt-20 border-t border-ink-200 bg-white">
    <div class="container-app grid gap-8 py-12 md:grid-cols-4">
        <div>
            <div class="flex items-center gap-2 font-bold text-ink-900">
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-600 text-sm text-white">AA</span>
                AutoAlert
            </div>
            <p class="mt-3 max-w-xs text-sm text-ink-500">
                La centralisation des vehicules a vendre et des alertes qui vous evitent de surveiller le marche tout seul.
            </p>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-ink-900">Catalogue</h3>
            <ul class="mt-3 space-y-2 text-sm text-ink-500">
                <li><a class="hover:text-brand-700" href="{{ route('vehicles.index') }}">Tous les vehicules</a></li>
                <li><a class="hover:text-brand-700" href="{{ route('vehicles.index', ['tri' => 'price_asc']) }}">Les moins chers</a></li>
                <li><a class="hover:text-brand-700" href="{{ route('vehicles.index', ['tri' => 'recent']) }}">Les derniers ajoutes</a></li>
                <li><a class="hover:text-brand-700" href="{{ route('help') }}">Comment ca marche</a></li>
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-ink-900">Mon compte</h3>
            <ul class="mt-3 space-y-2 text-sm text-ink-500">
                <li><a class="hover:text-brand-700" href="{{ route('alerts.index') }}">Mes alertes</a></li>
                <li><a class="hover:text-brand-700" href="{{ route('favorites.index') }}">Mes favoris</a></li>
                <li><a class="hover:text-brand-700" href="{{ route('preferences.edit') }}">Preferences de notification</a></li>
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-ink-900">Informations</h3>
            <ul class="mt-3 space-y-2 text-sm text-ink-500">
                <li><a class="hover:text-brand-700" href="{{ route('contact') }}">Contact</a></li>
                <li><a class="hover:text-brand-700" href="{{ route('legal') }}">Mentions legales</a></li>
                <li><a class="hover:text-brand-700" href="{{ route('help') }}">FAQ</a></li>
            </ul>
        </div>
    </div>

    <div class="border-t border-ink-100 py-5">
        <p class="container-app text-xs text-ink-400">
            &copy; {{ now()->year }} AutoAlert. Toutes les annonces proviennent de sources tierces ; les prix sont indicatifs.
        </p>
    </div>
</footer>