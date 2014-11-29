<?php
/*
 * dnsmasq management tools
 */

error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';

use Lstr\Silex\Config\ConfigServiceProvider;
use Silex\Application;

$app = new Application();
$app['route_class'] = 'Ied\Iaxs\Application\Route';

// lstr-silex components
$app->register(new ConfigServiceProvider());

// dnsmasq-management components

$app['config'] = $app['lstr.config']->load(array(
    __DIR__ . '/config/autoload/*.global.php',
    __DIR__ . '/config/autoload/*.local.php',
));

if (!empty($app['config']['date_time']['timezone'])) {
    date_default_timezone_set($app['config']['date_time']['timezone']);
}

$app['debug'] = false;
if (isset($app['config']['debug'])) {
    $app['debug'] = $app['config']['debug'];
}

return $app;
