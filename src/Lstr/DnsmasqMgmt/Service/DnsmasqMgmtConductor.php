<?php

namespace Lstr\DnsmasqMgmt\Service;

class DnsmasqMgmtConductor
{
    private $environment_service;
    private $config_service;
    private $sudoers_service;
    private $log_service;

    public function __construct(array $services)
    {
        $this->environment_service = $services['environment_service'];
        $this->config_service = $services['config_service'];
        $this->sudoers_service = $services['sudoers_service'];
        $this->log_service = $services['log_service'];
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

    public function removeAddress($hostname)
    {
        $this->config_service->removeAddress($hostname);
        $this->environment_service->clearDnsCache();
    }

    public function updateAddress($hostname, $ip)
    {
        $this->config_service->updateAddress($hostname, $ip);
        $this->environment_service->clearDnsCache();
    }

    public function getAddresses()
    {
        return $this->config_service->getAddresses();
    }

    public function setupDnsmasq()
    {
        $this->environment_service->setupDnsmasq();
        $this->environment_service->clearDnsCache();
    }

    public function setupSudoers()
    {
        $this->sudoers_service->setupSudoers();
    }

    public function setLoggerIsVerbose($is_verbose)
    {
        $this->log_service->setIsVerbose($is_verbose);
    }
}
