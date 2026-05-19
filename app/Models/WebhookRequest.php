<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['method', 'url', 'payload', 'headers', 'ip_address'])]
class WebhookRequest extends Model
{
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'headers' => 'array',
        ];
    }
}
