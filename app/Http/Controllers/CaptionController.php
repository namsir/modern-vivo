<?php

// 1. Create this new controller at: app/Http/Controllers/CaptionController.php
// Command: sail artisan make:controller CaptionController

namespace App\Http\Controllers;

use App\Models\Media;
use App\Rules\VttContent;
use Illuminate\Http\Request;

class CaptionController extends Controller
{
    /**
     * Store a new caption file uploaded by a user.
     */
    /**
     * Store a new caption file uploaded by a user.
     * This method handles the file upload from the edit page.
     */
    public function store(Request $request, Media $media)
    {
        $request->validate([
            'caption_file' => ['required', 'file', 'mimes:vtt', new VttContent],
        ]);

        $content = $request->file('caption_file')->get();

        // Find the existing English caption or create a new one.
        // This will overwrite any existing caption content.
        $media->captions()->updateOrCreate(
            ['media_id' => $media->id, 'language_code' => 'en'],
            [
                'caption' => $content,
                'status' => 'approved', // Assume self-uploads are pre-approved
                'uploaded_by' => auth()->user()->name,
                'language' => 'English',
                'language_code' => 'en',
            ]
        );

        return back()->with('success', 'Captions uploaded successfully.');
    }

    /**
     * Update the text content of an existing caption.
     * This method handles saving edits from the textarea.
     */
    public function update(Request $request, Media $media)
    {
        $request->validate([
            'caption_content' => ['required', 'string', new VttContent],
        ]);

        // Find the first caption for this media and update it.
        $caption = $media->captions()->first();

        if ($caption) {
            $caption->update(['caption' => $request->caption_content]);
            return back()->with('success', 'Captions updated successfully.');
        }

        // If no caption exists yet, create one.
        $media->captions()->create([
            'caption' => $request->caption_content,
            'status' => 'approved',
            'uploaded_by' => auth()->user()->name,
            'language' => 'English',
            'language_code' => 'en',
        ]);

        return back()->with('success', 'Captions saved successfully.');
    }
}
