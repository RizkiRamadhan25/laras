<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'locale',
        'currency_code',
        'timezone',
        'date_format',
        'time_format',
        'week_starts_on',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'week_starts_on' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
