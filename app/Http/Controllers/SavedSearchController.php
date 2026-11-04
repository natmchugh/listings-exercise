<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSavedSearchRequest;
use App\Http\Resources\SavedSearchResource;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SavedSearchController extends Controller
{
    /**
     * The current user's saved searches, newest first.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $searches = $user->savedSearches()
            ->latest()
            ->orderByDesc('id')
            ->get();

        return Inertia::render('SavedSearches/Index', [
            'savedSearches' => SavedSearchResource::collection($searches),
        ]);
    }

    public function store(StoreSavedSearchRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->savedSearches()->create($request->criteria());

        return redirect()->route('saved-searches.index');
    }

    public function destroy(SavedSearch $savedSearch): RedirectResponse
    {
        Gate::authorize('delete', $savedSearch);

        $savedSearch->delete();

        return redirect()->route('saved-searches.index');
    }
}
