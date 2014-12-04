<?php

namespace Lstr\DnsmasqMgmt\Service;

class DnsmasqMgmtConductor
{
    private $environment_service;
    private $config_service;

    public function __construct(array $services)
    {
        $this->environment_service = $services['environment_service'];
        $this->config_service = $services['config_service'];
    }

    public function clearDnsCache()
    {
        return $this->environment_service->clearDnsCache();
    }

    public function getConfig()
    {
        return $this->config_service->getConfig();
    }

    public function addAddress($hostname, $address)
    {
        $this->config_service->addAddress($hostname, $address);
        $this->environment_service->clearDnsCache();
    }
}
