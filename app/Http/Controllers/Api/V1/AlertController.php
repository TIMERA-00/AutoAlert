<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AlertFrequency;
use App\Enums\BodyType;
use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Services\MatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $alerts = $request->user()
            ->alerts()
            ->withCount('notifications')
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->input('per_page', 25), 100));

        return response()->json($alerts);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $alert = $request->user()->alerts()->create($data);

        return response()->json(['data' => $alert->fresh()], 201);
    }

    public function show(Request $request, Alert $alert, MatchingService $matching): JsonResponse
    {
        $this->authorizeAlert($request, $alert);

        return response()->json([
            'data' => $alert,
            'preview' => $matching->vehiclesFor($alert, 10),
        ]);
    }

    public function update(Request $request, Alert $alert): JsonResponse
    {
        $this->authorizeAlert($request, $alert);

        $alert->update($this->validated($request, partial: true));

        return response()->json(['data' => $alert->fresh()]);
    }

    public function toggle(Request $request, Alert $alert): JsonResponse
    {
        $this->authorizeAlert($request, $alert);

        $alert->update(['is_active' => ! $alert->is_active]);

        return response()->json(['data' => $alert->fresh()]);
    }

    public function destroy(Request $request, Alert $alert): JsonResponse
    {
        $this->authorizeAlert($request, $alert);
        $alert->delete();

        return response()->json(status: 204);
    }

    private function authorizeAlert(Request $request, Alert $alert): void
    {
        abort_if($alert->user_id !== $request->user()->id, 403);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';
        $nullable = 'nullable';

        $data = $request->validate([
            'name' => [$required, 'string', 'min:2', 'max:80'],
            'brand' => [$nullable, 'string', 'max:60'],
            'model' => [$nullable, 'string', 'max:60'],
            'min_year' => [$nullable, 'integer', 'min:1900', 'max:'.((int) date('Y') + 2)],
            'max_year' => [$nullable, 'integer', 'min:1900', 'max:'.((int) date('Y') + 2)],
            'min_price' => [$nullable, 'integer', 'min:0'],
            'max_price' => [$nullable, 'integer', 'min:0'],
            'max_mileage' => [$nullable, 'integer', 'min:0'],
            'fuel' => [$nullable, Rule::in(array_keys(FuelType::options()))],
            'transmission' => [$nullable, Rule::in(array_keys(Transmission::options()))],
            'body_type' => [$nullable, Rule::in(array_keys(BodyType::options()))],
            'location' => [$nullable, 'string', 'max:80'],
            'frequency' => [$required, Rule::in(array_keys(AlertFrequency::options()))],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['min_price'], $data['max_price']) && $data['min_price'] > $data['max_price']) {
            abort(422, 'Le prix minimum ne peut pas depasser le prix maximum');
        }

        return $data;
    }
}
