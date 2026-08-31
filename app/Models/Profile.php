<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use RuntimeException;

#[Fillable(['msisdn', 'package', 'language', 'channel', 'status', 'subs_date', 'subs_time', 'last_update_date', 'last_update_time', 'last_charge_date', 'last_charge_time'])]
class Profile extends Model
{
    protected $table = 'profiles';

    protected $primaryKey = 'msisdn';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    /**
     * Each VAS service owns its own database, so a Profile is meaningless
     * without one. Resolve it through App\Support\ServiceStore rather than
     * querying this model directly.
     */
    public function getConnectionName(): string
    {
        if ($this->connection === null) {
            throw new RuntimeException('Profile has no service connection; resolve it through ServiceStore.');
        }

        return $this->connection;
    }

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'subs_date' => 'date',
            'last_update_date' => 'date',
            'last_charge_date' => 'date',
        ];
    }

    public function subscribedAt(): ?Carbon
    {
        if (! $this->subs_date) {
            return null;
        }

        return Carbon::parse(
            $this->subs_date->format('Y-m-d').' '.($this->subs_time ?: '00:00:00')
        );
    }

    public static function normalizeMsisdn(string $number): ?string
    {
        $cleaned = preg_replace('/[^0-9+]/', '', $number);

        if (empty($cleaned)) {
            return null;
        }

        if (! str_starts_with($cleaned, '+')) {
            $cleaned = '+249'.ltrim($cleaned, '0');
        }

        return $cleaned;
    }
}
