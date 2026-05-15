<?php

use Yllumi\Wmpanel\libraries\Migration;

$pluginPath = getenv('PLUGIN_PATH') ?: '';
$migration = new Migration($pluginPath);
return $migration->getConfig();