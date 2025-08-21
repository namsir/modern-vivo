<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaCaption extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'media_captions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'media_id',
        'caption_profile_id',
        'status',
        'requested_by',
        'uploaded_by',
        'approved_by',
        'language',
        'language_code',
        'order_id',
        'reason',
        'caption',
    ];

    /**
     * Get the media that this caption belongs to.
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * Get the caption profile associated with this caption request.
     */
    public function captionProfile(): BelongsTo
    {
        return $this->belongsTo(CaptionProfile::class);
    }
}
