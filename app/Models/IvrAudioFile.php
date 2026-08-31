<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['service_id', 'original_name', 'filename', 'position', 'size_bytes', 'uploaded_by'])]
class IvrAudioFile extends Model
{
    protected function casts(): array
    {
        return [
            'service_id' => 'integer',
            'position' => 'integer',
            'size_bytes' => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
