{{--
    Thin delegate: the layout itself lives in resources/views/layouts/app.blade.php
    so that `<x-layouts.app>` in a Blade view and #[Layout('layouts.app')] on a
    Livewire component share one single source of truth.
--}}
@include('layouts.app', ['slot' => $slot ?? null])