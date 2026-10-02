<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', 'AutoAlert centralise les vehicules disponibles a la vente et vous previent des que vos criteres sont remplis.')">
    <meta property="og:title" content="@yield('meta_title', 'AutoAlert - les vehicules, au bon moment')">
    <meta property="og:description" content="@yield('meta_description', 'Centralisation des annonces et alertes personnalisees.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">

    <title>@yield('title', 'AutoAlert')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-ink-50">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">
        Aller au contenu principal
    </a>

    @include('partials.header')

    <main id="main">
        @if (session('success') || session('error'))
            <div class="container-app pt-4">
                @if (session('success'))
                    <div class="alert-box border-emerald-200 bg-emerald-50 text-emerald-800" role="status">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert-box border-red-200 bg-red-50 text-red-800" role="alert">
                        {{ session('error') }}
                    </div>
                @endif
            </div>
        @endif

        {{ $slot ?? '' }}
        @yield('content')
    </main>

    @include('partials.footer')

    @livewireScripts
</body>
</html>