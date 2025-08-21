<?php

namespace App\Http\Controllers;

use App\Models\CaptionProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CaptionProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('admin/caption-profiles/Index', [
            'profiles' => CaptionProfile::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/caption-profiles/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:caption_profiles',
            'api_key' => 'nullable|string|max:255',
            'profile' => 'nullable|string|max:255',
            'vendor' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        // If the new profile is set to active, deactivate all others first.
        if ($validated['is_active']) {
            CaptionProfile::query()->update(['is_active' => false]);
        }

        CaptionProfile::create($validated);

        return redirect()->route('admin.caption-profiles.index')->with('success', 'Profile created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CaptionProfile $captionProfile)
    {
        return Inertia::render('admin/caption-profiles/Edit', [
            'profile' => $captionProfile,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CaptionProfile $captionProfile)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:caption_profiles,name,' . $captionProfile->id,
            'api_key' => 'nullable|string|max:255',
            'profile' => 'nullable|string|max:255',
            'vendor' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        // If the api_key field is submitted empty, we don't want to overwrite the existing key.
        // So, we'll remove it from the array of data to be updated.
        if (empty($validated['api_key'])) {
            unset($validated['api_key']);
        }

        // This logic ensures only one profile can be active at a time.
        if ($validated['is_active']) {
            // If we are setting this profile to active, first set all other profiles to inactive.
            CaptionProfile::where('id', '!=', $captionProfile->id)->update(['is_active' => false]);
        }

        $captionProfile->update($validated);

        return redirect()->route('admin.caption-profiles.index')->with('success', 'Profile updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CaptionProfile $captionProfile)
    {
        $captionProfile->delete();

        return redirect()->route('admin.caption-profiles.index')->with('success', 'Profile deleted successfully.');
    }
}
