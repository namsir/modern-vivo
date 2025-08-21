<?php

// routes/web.php
use App\Http\Controllers\Admin\CaptionRequestController;
use App\Http\Controllers\CaddyController;
use App\Http\Controllers\CaptionController;
use App\Http\Controllers\CaptionProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\WebhookController;
use App\Models\Media;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// ...

//$originalEncode = Media::where('id', 7)->get();
//$originalEncode->refresh();
//$originalEncode = $originalEncode->encodes;
//dd($originalEncode);
// Add a safety check in case no encode record was created.


//$originalAbsolutePath = Storage::disk('public')->path($originalEncode->url);
//dd($originalAbsolutePath);
//Storage::disk('public')->path($originalEncode->url)

Route::post('/webhooks/aws/mediaconvert', [WebhookController::class, 'handleMediaConvert'])->name('webhooks.aws.mediaconvert');
Route::get('/caddy', CaddyController::class)->name('caddy');


Route::middleware(['auth', 'verified'])->group(function () {
    // Redirect dashboard to the videos index
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // The main videos page

    Route::post('/media/upload-chunk', [MediaController::class, 'uploadChunk'])->name('media.upload.chunk');
    // The pages from the previous response
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::get('/media/upload', [MediaController::class, 'create'])->name('media.upload');
    Route::get('/media/{media}/edit', [MediaController::class, 'edit'])->name('media.edit');
    Route::put('/media/{media}', [MediaController::class, 'update'])->name('media.update');



    Route::get('/home', fn () => redirect()->route('videos.index'))->name('home');

    Route::get('/media/{media}/captions.vtt', [MediaController::class, 'captionsVtt'])->name('media.captions.vtt');
    // Routes for uploading and editing captions
    Route::post('/media/{media}/captions', [CaptionController::class, 'store'])->name('media.captions.store');
    Route::put('/media/{media}/captions', [CaptionController::class, 'update'])->name('media.captions.update');

});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    /**
     * Routes for managing caption requests.
     * These names must match what's used in your Vue component's `route()` helper.
     */
    Route::get('/caption-requests', [CaptionRequestController::class, 'index'])->name('caption-requests.index');
    Route::put('/caption-requests/{captionRequest}', [CaptionRequestController::class, 'update'])->name('caption-requests.update');

    // This line creates the 'admin.caption-profiles.create' route
    Route::resource('caption-profiles', CaptionProfileController::class);
    Route::get('/caption-requests', [CaptionRequestController::class, 'index'])->name('caption-requests.index');
    Route::put('/caption-requests/{captionRequest}', [CaptionRequestController::class, 'update'])->name('caption-requests.update');
});



require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
