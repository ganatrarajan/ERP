<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting by key with fallback default.
     *
     * @param string $key
     * @param string|null $default
     * @return string|null
     */
    public static function getSetting(string $key, ?string $default = null): ?string
    {
        try {
            $record = static::where('key', $key)->first();
            return ($record && $record->value !== null && $record->value !== '') ? $record->value : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Set or update a setting by key.
     *
     * @param string $key
     * @param string|null $value
     * @return void
     */
    public static function setSetting(string $key, ?string $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
