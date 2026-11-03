<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Take-home auth stub.
 *
 * A real deployment would authenticate the request (Sanctum, session, etc.).
 * To keep this exercise focused on the feature rather than on auth plumbing,
 * every API request is resolved as the seeded demo user. Build user-scoped
 * work against $request->user() / auth()->user() exactly as you normally would.
 */
class ActAsDemoUser
{
    private ?User $demoUser = null;

    private bool $resolved = false;

    public function handle(Request $request, Closure $next): Response
    {
        // Resolved lazily and only once per request — repeated auth()->user()
        // calls should not each hit the database.
        //
        // This is null until the database is seeded (`php artisan migrate --seed`),
        // which is the same shape as a genuinely unauthenticated request.
        $request->setUserResolver(function (): ?User {
            if (! $this->resolved) {
                $this->demoUser = User::query()->orderBy('id')->first();
                $this->resolved = true;
            }

            return $this->demoUser;
        });

        return $next($request);
    }
}
