<?php

// Load environment variables from .env file manually 
// since this file is used by Phinx which does not load the full application bootstrap
$dotenvfile = __DIR__ . '/../.env';
$dotenvContent = file_exists($dotenvfile) ? file_get_contents($dotenvfile) : '';
$dotenvArray = [];
if ($dotenvContent) {
    $lines = explode("\n", $dotenvContent);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line && strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (preg_match('/^["\'](.*)["\']$/', $value, $matches)) {
                $value = $matches[1];
            }
            $dotenvArray[$key] = $value;
        }
    }
}

return [
    'paths' => [
        'migrations' => 'database/migrations',
        'seeds' => 'database/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'ph_migrations',
        'default_environment' => 'default',
        'default' => [
            'adapter' => 'mysql',
            'host' => $dotenvArray['database.default.hostname'] ?? 'localhost',
            'name' => $dotenvArray['database.default.database'] ?? '',
            'user' => $dotenvArray['database.default.username'] ?? '',
            'pass' => $dotenvArray['database.default.password'] ?? '',
            'port' => $dotenvArray['database.default.port'] ?? '3306',
            'charset' => 'utf8',
        ],
    ],
    'version_order' => 'creation'
];