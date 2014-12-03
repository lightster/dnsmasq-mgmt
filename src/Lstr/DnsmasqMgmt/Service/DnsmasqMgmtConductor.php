<?php

namespace Lstr\DnsmasqMgmt\Service;

class DnsmasqMgmtConductor
{
    private $environment_service;

    public function __construct(array $services)
    {
        $this->environment_service = $services['environment_service'];
    }

    public function clearDnsCache()
    {
        return $this->environment_service->clearDnsCache();
    }
}
