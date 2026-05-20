<?php

namespace App\Models;

use Database\Factories\MtnSubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['channel_id', 'operator_id', 'request_id', 'msisdn', 'status', 'price'])]
class MtnSubscription extends Model
{
    /** @use HasFactory<MtnSubscriptionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'channel_id' => 'integer',
            'operator_id' => 'integer',
            'request_id' => 'integer',
            'price' => 'decimal:2',
        ];
    }
}
