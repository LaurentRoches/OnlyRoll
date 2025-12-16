<?php

require 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$_SERVER['APP_ENV'] = 'test';
$_ENV['APP_ENV'] = 'test';
putenv('APP_ENV=test');

(new Dotenv())->bootEnv('.env');

$kernel = new \App\Kernel($_ENV['APP_ENV'], (bool) $_ENV['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

echo "APP_ENV: " . $_ENV['APP_ENV'] . PHP_EOL;
echo "Kernel Env: " . $kernel->getEnvironment() . PHP_EOL;
echo "Kernel Debug: " . ($kernel->isDebug() ? 'true' : 'false') . PHP_EOL;

try {
    $config = $container->getParameter('kernel.project_dir') . '/config/packages/test/framework.yaml';
    echo "Test config exists: " . (file_exists($config) ? 'yes' : 'no') . PHP_EOL;

    if ($container->hasParameter('framework')) {
        echo "Framework parameter exists" . PHP_EOL;
    }

    // Essayer de récupérer la configuration du framework
    $frameworkConfig = $container->getParameter('kernel.bundles');
    if (isset($frameworkConfig['FrameworkBundle'])) {
        echo "FrameworkBundle is loaded" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
