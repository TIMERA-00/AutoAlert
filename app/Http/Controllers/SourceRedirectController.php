<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SourceRedirectController extends Controller
{
    /** Counts the outbound click, then redirects to the original listing. */
    public function show(Vehicle $vehicle): RedirectResponse
    {
        abort_unless($vehicle->isPublished() || auth()->user()?->isAdmin(), 404);

        if (blank($vehicle->source_url)) {
            return back()->with('error', 'Aucune source disponible pour ce vehicule.');
        }

        $vehicle->sourceClicks()->create(['user_id' => auth()->id()]);

        return redirect()->away($vehicle->source_url);
    }

    /** Same as show() but returns JSON for the front-end button. */
    public function store(Request $request, Vehicle $vehicle)
    {
        abort_unless($vehicle->isPublished() || $request->user()?->isAdmin(), 404);

        $vehicle->sourceClicks()->create(['user_id' => $request->user()?->id()]);

        return response()->json([
            'ok' => true,
            'url' => $vehicle->source_url,
        ]);
    }
}
