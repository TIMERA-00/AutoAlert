<?php

namespace App\Services;

use App\Models\PageView;
use App\Models\Vehicle;
use Illuminate\Http\Request;

/**
 * Lightweight, privacy-conscious traffic tracking used by the admin statistics.
 * IPs are hashed, never stored in clear.
 */
class PageViewTracker
{
    public function record(?Vehicle $vehicle = null, ?Request $request = null): void
    {
        $request ??= request();

        // One record per session and per page, at most one per minute.
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;
        $fingerprint = hash('sha256', ($request->ip() ?? 'unknown').'|'.($request->userAgent() ?? ''));

        $recent = PageView::query()
            ->where('created_at', '>=', now()->subMinute())
            ->where('path', $request->path())
            ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->when(! $sessionId, fn ($q) => $q->where('ip_hash', $fingerprint))
            ->exists();

        if ($recent) {
            return;
        }

        PageView::create([
            'vehicle_id' => $vehicle?->id,
            'user_id' => $request->user()?->id,
            'path' => mb_substr($request->path(), 0, 255),
            'referrer' => $request->headers->get('referer') ? mb_substr($request->headers->get('referer'), 0, 500) : null,
            'user_agent' => $request->userAgent() ? mb_substr($request->userAgent(), 0, 255) : null,
            'ip_hash' => $fingerprint,
            'session_id' => $sessionId,
        ]);
    }
}
