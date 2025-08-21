<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\Tag;
use App\Models\MediaCaption;
use App\Models\MediaEncode;
class Media extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'media_type',
        'file_hash',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // The 'sources' column is no longer used, so it's removed from casts.
    ];

    /**
     * Get the user that owns the media.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The tags that belong to the media.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'media_tag');
    }

    /**
     * Get the caption requests for the media file.
     */
    public function captions(): HasMany
    {
        return $this->hasMany(MediaCaption::class);
    }

    /**
     * Get all of the encodes for the Media.
     */
    public function encodes(): HasMany
    {
        return $this->hasMany(MediaEncode::class);
    }


    /**
     * Get all of the events for the Media.
     */
    public function events(): HasMany
    {
        return $this->hasMany(MediaEvent::class)->orderBy('created_at', 'desc');
    }
}
