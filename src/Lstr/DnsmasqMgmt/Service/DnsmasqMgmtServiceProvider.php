<?php

namespace Lstr\DnsmasqMgmt\Service;

use Silex\Application;
use Silex\ServiceProviderInterface;

class DnsmasqMgmtServiceProvider implements ServiceProviderInterface
{
    private $environment_services = [
        'darwin' => 'Lstr\DnsmasqMgmt\Service\BrewEnvironmentService',
    ];

    public function register(Application $app)
    {
        $app['lstr.dnsmasq'] = $app->share(function ($app) {
            if (empty($app['lstr.dnsmasq.environment'])) {
                $app['lstr.dnsmasq.environment'] = [
                    'os_name' => php_uname('s'),
                    'release' => php_uname('r'),
                    'version' => php_uname('v'),
                ];
            }

            $os_name = strtolower($app['lstr.dnsmasq.environment']['os_name']);

            if (!array_key_exists($os_name, $this->environment_services)) {
                throw new Exception("Unknown environment '{$os_name}'");
            }

            $env_service_name = $this->environment_services[$os_name];
            $env_service = new $env_service_name($app['lstr.dnsmasq.environment']);

            return new DnsmasqMgmtConductor([
                'environment_service' => $env_service,
                'config_service' => new ConfigService($app['config']['home_dir']),
            ]);
        });
    }

    public function boot(Application $app)
    {
    }
}
