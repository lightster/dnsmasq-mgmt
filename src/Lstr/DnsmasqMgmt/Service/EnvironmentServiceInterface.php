<?php

namespace Lstr\DnsmasqMgmt\Service;

interface EnvironmentServiceInterface
{
    public function clearDnsCache();
    public function getClearCacheCommands();
    public function setupDnsmasq();
}
