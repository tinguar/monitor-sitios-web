<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'name',
    'url',
    'country_code',
    'phone',
    'whatsapp_e164',
    'timeout_seconds',
    'consecutive_failures',
    'last_status',
    'last_checked_at',
    'last_response_ms',
    'last_http_code',
    'last_error',
    'ssl_days_left',
    'created_at',
])]
class Site extends Model
{
    public $timestamps = false;

    public function checks(): HasMany
    {
        return $this->hasMany(SiteCheck::class);
    }

    public function latestCheck(): HasOne
    {
        return $this->hasOne(SiteCheck::class)->orderByDesc('id');
    }
}
