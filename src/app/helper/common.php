<?php

/**
 * Here is your custom functions.
 */

function sidebarMenus()
{
    $path = base_path() . '/config/plugin/panel/menu.yml';

    if (!file_exists($path)) {
        return [];
    }

    return \Symfony\Component\Yaml\Yaml::parseFile($path) ?? [];
}

if (!function_exists('site_url')) {
    function site_url(string $path = ''): string
    {
        $prefix = env('APP_BASE_PATH', ''); // isi '/p' di .env
        return $prefix . '/' . ltrim($path, '/');
    }
}