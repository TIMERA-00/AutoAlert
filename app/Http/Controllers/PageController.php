<?php

namespace App\Http\Controllers;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PageController extends Controller
{
    public function __invoke(string $page = 'help'): View
    {
        $stats = [
            'vehicles' => Vehicle::where('status', VehicleStatus::Published)->count(),
        ];

        return match ($page) {
            'legal' => view('pages.legal', ['stats' => $stats])->title('Mentions legales | AutoAlert'),
            'contact' => view('pages.contact', ['stats' => $stats])->title('Contact | AutoAlert'),
            default => view('pages.help', ['stats' => $stats])->title('Comment ca marche | AutoAlert'),
        };
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:4000'],
        ]);

        // MVP: la file d'attente est tracee dans les logs. Branchez ici un service
        // de support (email via Resend, ticket interne) selon vos besoins.
        Log::info('Contact message', [
            'user_id' => $request->user()?->id,
            'subject' => $data['subject'],
        ]);

        return back()->with('success', 'Message envoye, nous revenons vers vous rapidement.');
    }
}
