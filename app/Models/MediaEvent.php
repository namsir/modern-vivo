<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaEvent extends Model
{
    use HasFactory;

    protected $table = 'media_events';
    public $timestamps = false; // We only use the 'created_at' timestamp

    protected $fillable = [
        'media_id',
        'event_type',
        'status',
        'details',
    ];

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
