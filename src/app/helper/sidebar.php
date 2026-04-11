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
