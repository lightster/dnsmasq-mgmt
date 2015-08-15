<?php

namespace Lstr\DnsmasqMgmt\Service;

interface EnvironmentServiceInterface
{
    /**
     * Runs all of the commands that need to be
     * ran to clear the DNS cache.
     *
     * @return void
     */
    public function clearDnsCache();

    /**
     * Returns all commands that need to be ran
     * to clear the DNS cache.
     *
     * @return array
     */
    public function getClearCacheCommands();

    /**
     * Installs dnsmasq and does the initial
     * dnsmasq configuration.
     *
     * @return void
     */
    public function setupDnsmasq();
}
