<?php

namespace Webuild\ThemeKit;

use Illuminate\Support\Facades\File;

class ThemeAssetRenderer
{
    public static function render(string $themeKey): string
    {
        if (! preg_match('/^[a-z0-9_-]+$/', $themeKey)) {
            return '';
        }
        $root = dirname(__DIR__, 2).'/themes/'.$themeKey;
        $manifestPath = $root.'/theme.json';
        if (! is_file($manifestPath)) {
            return '';
        }
        $manifest = json_decode(File::get($manifestPath), true);
        if (! is_array($manifest)) {
            return '';
        }
        $html = '';
        foreach (($manifest['assets']['styles'] ?? []) as $asset) {
            $path = is_array($asset) ? ($asset['path'] ?? '') : $asset;
            $url = self::url($root, $themeKey, $path, 'css');
            if ($url) {
                $html .= '<link rel="stylesheet" href="'.e($url).'">';
            }
        }
        foreach (($manifest['assets']['scripts'] ?? []) as $asset) {
            $path = is_array($asset) ? ($asset['path'] ?? '') : $asset;
            $type = is_array($asset) ? ($asset['type'] ?? 'classic') : 'classic';
            $url = self::url($root, $themeKey, $path, 'js');
            if ($url) {
                $html .= '<script'.($type === 'module' ? ' type="module"' : ' defer').' src="'.e($url).'"></script>';
            }
        }

        return $html;
    }

    private static function url(string $root, string $themeKey, mixed $path, string $extension): ?string
    {
        if (! is_string($path) || ! preg_match('#^dist/[a-zA-Z0-9_./-]+\.'.preg_quote($extension, '#').'$#', $path) || str_contains($path, '..')) {
            return null;
        }
        $dist = realpath($root.'/dist');
        $file = realpath($root.'/'.$path);
        if (! $dist || ! $file || ! str_starts_with($file, $dist.DIRECTORY_SEPARATOR) || ! is_file($file)) {
            return null;
        }

        return url('/theme-assets/'.$themeKey.'/'.$path);
    }
}
