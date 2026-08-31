<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

#[Fillable(['mdn', 'package', 'subscribed_at', 'subscribed_channel', 'unsubscribed_at', 'unsubscribed_channel'])]
class VasSubscriptionHistory extends Model
{
    protected $table = 'vas_subscription_history';

    public $timestamps = false;

    /**
     * History is stored per service, alongside that service's profiles.
     * Resolve it through App\Support\ServiceStore.
     */
    public function getConnectionName(): string
    {
        if ($this->connection === null) {
            throw new RuntimeException('VasSubscriptionHistory has no service connection; resolve it through ServiceStore.');
        }

        return $this->connection;
    }

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }
}
