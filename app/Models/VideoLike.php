<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['video_archive_id', 'user_id'])]
class VideoLike extends Model
{
    public function video(): BelongsTo
    {
        return $this->belongsTo(VideoArchive::class, 'video_archive_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
