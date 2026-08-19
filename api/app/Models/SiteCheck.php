<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'site_id',
    'ok',
    'http_code',
    'response_ms',
    'error',
    'ssl_days_left',
    'checked_at',
])]
class SiteCheck extends Model
{
    protected $table = 'checks';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'ok' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
