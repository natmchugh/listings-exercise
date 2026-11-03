<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlertResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertController extends Controller
{
    /**
     * The current user's alerts, newest first. Viewing a page marks the
     * alerts on it as read.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_if($user === null, 403);

        // Capped like the listings index, since every alert on the page is
        // marked read below.
        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $alerts = $user->alerts()
            ->with(['listing.branch', 'savedSearch'])
            ->latest()
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        // Only the alerts on this page are marked, because those are the ones
        // the user has seen. This is a query update on purpose: the models
        // already loaded keep `read_at = null`, so this response still
        // highlights them as new.
        //
        // A prefetch or prerender isn't the user looking, so it marks nothing.
        // If that response is later shown from the prefetch cache, the alerts
        // stay unread until the next real visit: erring towards "new" rather
        // than losing alerts nobody saw.
        if (! $this->isPrefetch($request)) {
            $user->alerts()
                ->whereKey($alerts->getCollection()->pluck('id'))
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return Inertia::render('Alerts/Index', [
            'alerts' => AlertResource::collection($alerts),
        ]);
    }

    /**
     * Laravel's prefetch() covers Inertia's `Purpose: prefetch` and browser
     * link prefetching, but compares exactly, so it misses Chrome's
     * speculative prerender (`Sec-Purpose: prefetch;prerender`).
     */
    private function isPrefetch(Request $request): bool
    {
        return $request->prefetch()
            || str_starts_with(strtolower((string) $request->header('Sec-Purpose')), 'prefetch');
    }
}
