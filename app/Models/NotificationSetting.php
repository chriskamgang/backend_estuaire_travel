<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'category',
        'enabled',
        'user_can_disable',
        'metadata',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'user_can_disable' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get notification setting by key
     */
    public static function getByKey(string $key): ?self
    {
        return self::where('key', $key)->first();
    }

    /**
     * Check if notification type is enabled
     */
    public static function isEnabled(string $key): bool
    {
        $setting = self::getByKey($key);
        return $setting ? $setting->enabled : false;
    }

    /**
     * Get all settings by category
     */
    public static function getByCategory(string $category): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('category', $category)->get();
    }
}
