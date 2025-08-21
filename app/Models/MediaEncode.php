<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaEncode extends Model
{
    use HasFactory;

    protected $table = 'media_encodes';

    protected $fillable = [
        'media_id',
        'width',
        'height',
        'preset_id',
        'status',
        'status_details',
        'type',
        'resolution',
        'url',
    ];

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }
}
