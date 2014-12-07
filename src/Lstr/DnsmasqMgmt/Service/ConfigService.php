<?php

namespace Lstr\DnsmasqMgmt\Service;

use Exception;

class ConfigService
{
    private $home_dir;
    private $config_file;
    private $config;

    public function __construct($home_dir)
    {
        $this->home_dir = $home_dir;
        $this->config_file = "{$this->home_dir}/config.json";
    }

    public function getConfig()
    {
        if ($this->config) {
            return $this->config;
        }

        if (!file_exists($this->config_file)) {
            return $this->config = array();
        }

        $config_json = file_get_contents($this->config_file);
        if (false === $config_json) {
            throw new Exception("Could not read '{$this->config_file}'.");
        }

        // if the file is readable but contains nothing,
        // do not try to parse it as JSON
        if (empty($config_json)) {
            return $this->config = array();
        }

        $this->config = json_decode($config_json, true);

        if (null === $this->config) {
            throw new Exception("The contents of '{$this->config_file}' is not valid JSON.");
        }

        $this->config['active_workspace'] = 'default';

        return $this->config;
    }

    public function getActiveWorkspace()
    {
        return $this->config['active_workspace'];
    }

    public function addAddress($hostname, $ip_address)
    {
        $this->getConfig();

        $workspace = &$this->config['workspaces']['default'];

        if (isset($workspace['domains'][$hostname])) {
            throw new Exception("Address is '{$hostname}' already configured.");
        }

        $workspace['domains'][$hostname] = [
            'ip_address' => $ip_address,
        ];

        $this->writeConfig();
    }

    public function removeAddress($hostname)
    {
        $this->getConfig();

        $workspace = &$this->config['workspaces']['default'];

        if (!isset($workspace['domains'][$hostname])) {
            throw new Exception("Address is '{$hostname}' not configured.");
        }

        unset($workspace['domains'][$hostname]);

        $this->writeConfig();
    }

    private function writeConfig()
    {
        file_put_contents($this->config_file, json_encode($this->config));
    }
}
