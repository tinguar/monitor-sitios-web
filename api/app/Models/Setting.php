<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'setting_key';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'setting_key',
        'setting_value',
    ];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $value = static::query()->where('setting_key', $key)->value('setting_value');

        return $value === null ? $default : (string) $value;
    }

    public static function setValue(string $key, string $value): void
    {
        static::query()->updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value],
        );
    }

    public static function failThreshold(): int
    {
        return max(1, (int) static::getValue(
            'fail_threshold',
            (string) config('monitor.fail_threshold', 3),
        ));
    }

    public static function slowThresholdMs(): int
    {
        return max(500, (int) static::getValue(
            'slow_threshold_ms',
            (string) config('monitor.slow_threshold_ms', 3000),
        ));
    }

    public static function checkIntervalMinutes(): int
    {
        return max(1, min(60, (int) static::getValue(
            'check_interval_minutes',
            (string) config('monitor.check_interval_minutes', 1),
        )));
    }

    public static function isCheckDue(): bool
    {
        $last = static::getValue('last_auto_check_at');
        if (! $last) {
            return true;
        }

        $elapsed = time() - (strtotime($last) ?: 0);

        return $elapsed >= static::checkIntervalMinutes() * 60;
    }

    public static function markCheckedNow(): void
    {
        static::setValue('last_auto_check_at', gmdate('c'));
    }

    public static function digestEnabled(): bool
    {
        $raw = static::getValue('digest_enabled');
        if ($raw === null) {
            return (bool) config('monitor.digest_enabled', true);
        }

        return in_array(strtolower($raw), ['1', 'true', 'yes', 'on'], true);
    }
}
