<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'config';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'string',
    ];

    /**
     * Get a configuration value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getValue(string $key, $default = null)
    {
        $config = static::where('key', $key)->first();

        if (!$config) {
            return $default;
        }

        return match ($config->type) {
            'boolean' => (bool) $config->value,
            'integer' => (int) $config->value,
            'json' => json_decode($config->value, true),
            default => $config->value,
        };
    }

    /**
     * Set a configuration value by key
     *
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @param string|null $description
     * @return static
     */
    public static function setValue(string $key, $value, string $type = 'string', ?string $description = null)
    {
        $processedValue = match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) $value,
            'json' => json_encode($value),
            default => (string) $value,
        };

        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $processedValue,
                'type' => $type,
                'description' => $description,
            ]
        );
    }

    /**
     * Get all configuration as key-value array
     *
     * @return array
     */
    public static function getAllAsArray(): array
    {
        return static::all()->mapWithKeys(function ($config) {
            return [$config->key => static::getValue($config->key)];
        })->toArray();
    }
}
