<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Contracts\View\View;

class AdminVehiclePreviewController extends Controller
{
    public function __invoke(Vehicle $vehicle): View
    {
        return view('admin.vehicles.preview', [
            'vehicle' => $vehicle->load(['images', 'source']),
        ])->title('Apercu | AutoAlert');
    }
}
