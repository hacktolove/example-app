<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['mdn', 'package', 'subscribed_at', 'subscribed_channel', 'unsubscribed_at', 'unsubscribed_channel'])]
class VasSubscriptionHistory extends Model
{
    protected $connection = 'profiles';

    protected $table = 'vas_subscription_history';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }
}
