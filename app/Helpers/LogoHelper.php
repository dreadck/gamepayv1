<?php

namespace App\Helpers;

class LogoHelper
{
    /**
     * Get the logo path
     */
    public static function path(string $filename = 'logo.png'): string
    {
        $paths = [
            "images/{$filename}",
            "images/logo.svg",
            "images/logo.png",
        ];

        foreach ($paths as $path) {
            if (file_exists(public_path($path))) {
                return asset($path);
            }
        }

        // Fallback to text logo if image doesn't exist
        return null;
    }

    /**
     * Get logo HTML
     */
    public static function html(string $class = 'logo', string $alt = 'GamePay'): string
    {
        $logoPath = self::path();

        if ($logoPath) {
            return '<img src="' . e($logoPath) . '" alt="' . e($alt) . '" class="' . e($class) . '">';
        }

        // Fallback text logo
        return '<span class="' . e($class) . '">GAMEPAY</span>';
    }

    /**
     * Get site name
     */
    public static function name(): string
    {
        return \App\Models\Setting::get('site_name', 'GamePay');
    }
}

