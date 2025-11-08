<?php

namespace App\Models;

use App\Enums\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'value' => 'string',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            static::clearCache();
        });

        static::deleted(function () {
            static::clearCache();
        });
    }

    ### Static Methods for Settings Management ###

    /**
     * Get all settings as key-value pairs
     */
    public static function getAllSettings(): array
    {
        return Cache::rememberForever('site_settings', function () {
            return static::query()
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    /**
     * Get a specific setting value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::getAllSettings()[$key] ?? $default;
    }

    /**
     * Set a setting value
     */
    public static function set(string|Settings $key, mixed $value): void
    {
        $keyString = $key instanceof Settings ? $key->value : $key;

        $settingEnum = Settings::tryFrom($keyString);

        if (!$settingEnum) {
            throw new \InvalidArgumentException("Invalid setting key: {$keyString}");
        }

        $metadata = $settingEnum->metadata();

        static::query()->updateOrCreate(
            ['key' => $keyString],
            [
                'value' => $value,
                'type' => $metadata['type'],
                'group' => $metadata['group'],
                'description' => $metadata['label'] ?? null,
            ]
        );

        static::clearCache();
    }

    /**
     * Initialize all settings from enum
     */
    public static function initializeFromEnum(): void
    {
        foreach (Settings::cases() as $setting) {
            $metadata = $setting->metadata();
            $keyString = $setting->value;

            static::query()->firstOrCreate(
                ['key' => $keyString],
                [
                    'value' => static::getDefaultValue($metadata['type']),
                    'type' => $metadata['type'],
                    'group' => $metadata['group'],
                    'description' => $metadata['label'],
                ]
            );
        }

        static::clearCache();
    }

    /**
     * Get default value based on type
     */
    protected static function getDefaultValue(string $type): string|null|bool
    {
        return match($type) {
            'boolean' => false,
            'number' => 0,
            default => ''
        };
    }

    /**
     * Clear settings cache
     */
    public static function clearCache(): void
    {
        Cache::forget('site_settings');
    }

    /**
     * Refresh settings cache
     */
    public static function refreshCache(): array
    {
        static::clearCache();
        return static::getAllSettings();
    }

    /**
     * Get settings by group
     */
    public static function getByGroup(string $group): array
    {
        return Cache::remember("settings_group_{$group}", 3600, function () use ($group) {
            return static::query()
                ->where('group', $group)
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    /**
     * Check if a setting exists
     */
    public static function has(string $key): bool
    {
        return array_key_exists($key, static::getAllSettings());
    }

    /**
     * Bulk update settings
     */
    public static function bulkUpdate(array $settings): void
    {
        foreach ($settings as $key => $value) {
            static::set($key, $value);
        }
    }
}
