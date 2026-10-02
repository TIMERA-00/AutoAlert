<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\NotificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()
            ->notifications()
            ->with(['vehicle.images', 'alert'])
            ->when($request->boolean('unread'), fn ($q) => $q->whereNull('read_at'))
            ->latest();

        return response()->json($query->paginate(min((int) $request->input('per_page', 25), 100)));
    }

    public function read(Request $request, Notification $notification): JsonResponse
    {
        abort_if($notification->user_id !== $request->user()->id, 403);

        $notification->update(['read_at' => $notification->read_at ?? now(), 'status' => NotificationStatus::Read]);

        return response()->json(['data' => $notification->fresh()]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $count = $request->user()->notifications()->whereNull('read_at')->update([
            'read_at' => now(),
            'status' => NotificationStatus::Read,
        ]);

        return response()->json(['updated' => $count]);
    }
}
