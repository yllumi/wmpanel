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

    $menus = \Symfony\Component\Yaml\Yaml::parseFile($path) ?? [];

    $filtered = [];
    foreach ($menus as $menu) {
        // Filter children by privilege
        if (!empty($menu['children'])) {
            $menu['children'] = array_values(array_filter(
                $menu['children'],
                fn($child) => empty($child['privilege']) || isAllow($child['privilege'])
            ));
        }

        // Skip parent if it requires a privilege the user doesn't have
        if (!empty($menu['privilege']) && !isAllow($menu['privilege'])) {
            continue;
        }

        // Skip parent with children if no accessible children remain
        if (!empty($menu['children']) || $menu['url'] !== '#') {
            $filtered[] = $menu;
        } elseif (empty($menu['children']) && $menu['url'] === '#') {
            // parent-only container with no visible children — skip
            continue;
        }
    }

    return $filtered;
}

if (!function_exists('site_url')) {
    function site_url(string $path = ''): string
    {
        $prefix = env('APP_BASE_PATH', ''); // isi '/p' di .env
        return $prefix . '/' . ltrim($path, '/');
    }
}

if (!function_exists('setting')) {
    /**
     * Retrieve a setting value from cache.
     * Usage: setting('site.site_title')  →  returns the value or $default.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        $dot = strpos($key, '.');
        if ($dot === false) return $default;

        $group  = substr($key, 0, $dot);
        $name   = substr($key, $dot + 1);

        $fields = \Yllumi\Wmpanel\app\controller\SettingController::getGroupFromCache($group);

        return $fields[$name] ?? $default;
    }
}