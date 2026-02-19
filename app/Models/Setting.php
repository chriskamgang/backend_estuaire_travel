<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'group',
    ];

    protected $casts = [
        'value' => 'string',
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return static::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value): void
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget("setting_{$key}");
    }

    /**
     * Cast value to appropriate type
     */
    protected static function castValue($value, string $type)
    {
        return match($type) {
            'integer' => (int) $value,
            'boolean' => (bool) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Get user's loyalty status based on points
     */
    public static function getUserStatus(int $points): string
    {
        $platinum = static::get('platinum_threshold', 2000);
        $gold = static::get('gold_threshold', 1000);
        $silver = static::get('silver_threshold', 500);

        if ($points >= $platinum) {
            return 'Platinum';
        } elseif ($points >= $gold) {
            return 'Gold';
        } elseif ($points >= $silver) {
            return 'Silver';
        } else {
            return 'Bronze';
        }
    }
}
