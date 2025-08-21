<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     * This method will gather all data needed for the dashboard view.
     */
//    public function __invoke(Request $request)
//    {
//        // Fetch ALL media, paginated, with user and tag data
//        $allMedia = Media::query()
//            ->with(['user', 'tags', 'encodes']) // Eager load all relationships
//            ->latest()
//            ->paginate(12);
//
//        // Render the SAME component as the "My Media" page
//        return Inertia::render('media/Index', [
//            'media' => $allMedia,
//            'pageTitle' => 'Dashboard', // Pass a title for this view
//        ]);
//    }

    public function index(Request $request)
    {
        $media = Media::query()
            ->with(['user', 'tags', 'encodes']) // Eager load relationships
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'like', "%{$request->search}%")
                        ->orWhere('description', 'like', "%{$request->search}%");
                });
            })
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('media_type'), fn($q) => $q->where('media_type', $request->media_type))
            ->when($request->filled('user_id'), fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->when($request->filled('tag_id'), function ($query) use ($request) {
                $query->whereHas('tags', fn($q) => $q->where('tags.id', $request->tag_id));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Dashboard', [
            'media' => $media,
            'filters' => $request->all(['search', 'status', 'user_id', 'date_from', 'date_to']),
            'users' => User::select('id', 'name')->get(),
        ]);
    }
}
