<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class BrandRedirectController extends Controller
{
    /** /marques/toyota -> catalogue pre-filtered on the brand. */
    public function show(string $brand): Response|RedirectResponse
    {
        $existing = Vehicle::published()->whereRaw('LOWER(brand) = ?', [mb_strtolower($brand)])->exists();

        if (! $existing) {
            return response()->view('errors.404-brand', ['brand' => $brand], 404);
        }

        return redirect()->route('vehicles.index', ['marque' => $brand]);
    }
}
