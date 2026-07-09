<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public const KEY_PAGESPEED_API_KEY = 'pagespeed_api_key';

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return [
            'value' => 'encrypted',
        ];
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::remember("setting:{$key}", 60, function () use ($key, $default) {
            return static::where('key', $key)->first()?->value ?? $default;
        });
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");
    }
}
