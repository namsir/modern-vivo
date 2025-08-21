<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendTo3PlayJob;
use App\Mail\CaptionStatusUpdated;
use App\Models\MediaCaption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class CaptionRequestController extends Controller
{
    /**
     * Display a listing of the caption requests.
     */
    public function index(Request $request)
    {
        $requests = MediaCaption::query()
            ->with('media.user') // Eager load relationships for efficiency
            ->where('status', 'requested')
            ->when($request->input('search'), function ($query, $search) {
                // Filter results based on the title of the related media
                $query->whereHas('media', function ($subQuery) use ($search) {
                    $subQuery->where('title', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();

        return Inertia::render('admin/caption-requests/Index', [
            'captionRequests' => $requests,
            'filters' => $request->only(['search']), // Pass the current filters back to the view
        ]);
    }

    /**
     * Update the status of the specified caption request.
     * This method handles the form submission from your modal.
     */
    public function update(Request $request, MediaCaption $captionRequest)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'reason' => 'nullable|string|max:1000',
        ]);

        $captionRequest->update([
            'status' => $validated['status'],
            'reason' => $validated['reason'],
            'approved_by' => auth()->user()->name,
        ]);

        // LOG EVENT: Admin approved or rejected the request
        $captionRequest->media->events()->create([
            'event_type' => 'Caption Request ' . ucfirst($validated['status']),
            'status' => 'info',
            'details' => "Action taken by: " . auth()->user()->name . "\nReason: " . ($validated['reason'] ?? 'N/A'),
        ]);

        // If the request was approved, dispatch the job to send it to the vendor
        if ($validated['status'] === 'approved') {
            SendTo3PlayJob::dispatch($captionRequest);
            // LOG EVENT: Job dispatched to vendor
            $captionRequest->media->events()->create([
                'event_type' => 'Caption Job Dispatched to Vendor',
                'status' => 'info',
            ]);
        }

        // Send the notification email to the user
        $userToNotify = $captionRequest->media->user;
        if ($userToNotify) {
            Mail::to($userToNotify)->send(new CaptionStatusUpdated($captionRequest));
        }

        return redirect()->back()->with('success', 'Caption request updated and user notified.');
    }
}
