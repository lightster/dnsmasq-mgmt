<?php

namespace Lstr\DnsmasqMgmt\Service;

use Exception;

class ConfigService
{
    private $config_file;
    private $config;
    private $saved_config;
    private $resolver_dir;

    public function __construct(array $paths)
    {
        $this->home_dir = $this->pickPath($paths, 'home_dir');
        $this->config_file = "{$this->home_dir}/config.json";
        $this->dnsmasq_config_file = $this->pickPath($paths, 'dnsmasq_config_file');
        $this->resolver_dir = $this->pickPath($paths, 'resolver_dir');
    }

    public function getConfig()
    {
        if ($this->config) {
            return $this->config;
        }

        $this->readConfig();

        $this->config = array_replace_recursive(
            [
                'active_workspace' => 'default',
                'workspaces' => [
                    'default' => [
                        'domains' => [],
                    ],
                ],
            ],
            $this->config
        );

        $this->saved_config = $this->config;

        return $this->config;
    }

    public function getActiveWorkspace()
    {
        $this->getConfig();

        return $this->config['active_workspace'];
    }

    public function getWorkspaces()
    {
        $this->getConfig();

        $workspaces = [];
        foreach ($this->config['workspaces'] as $name => $workspace) {
            $workspaces[$name] = [
                'name' => $name,
            ];
        }

        return $workspaces;
    }

    public function setWorkspace($workspace)
    {
        $this->getConfig();

        $this->config['active_workspace'] = $workspace;

        if (!array_key_exists($workspace, $this->config['workspaces'])) {
            $this->config['workspaces'][$workspace] = ['domains' => []];
        }

        $this->writeConfig();
    }

    public function addAddress($hostname, $ip_address)
    {
        $this->getConfig();

        $workspace = &$this->config['workspaces'][$this->config['active_workspace']];

        if (isset($workspace['domains'][$hostname])) {
            throw new Exception("Address is '{$hostname}' already configured.");
        }

        $workspace['domains'][$hostname] = [
            'hostname' => $hostname,
            'ip_address' => $ip_address,
        ];

        $this->writeConfig();
    }

    public function removeAddress($hostname)
    {
        $this->getConfig();

        $workspace = &$this->config['workspaces'][$this->config['active_workspace']];

        if (!isset($workspace['domains'][$hostname])) {
            throw new Exception("Address is '{$hostname}' not configured.");
        }

        unset($workspace['domains'][$hostname]);

        $this->writeConfig();
    }

    public function updateAddress($hostname, $ip)
    {
        $this->getConfig();

        $workspace = &$this->config['workspaces'][$this->config['active_workspace']];

        if (!isset($workspace['domains'][$hostname])) {
            throw new Exception("Address is '{$hostname}' not configured.");
        }

        if ($ip) {
            $workspace['domains'][$hostname]['ip_address'] = $ip;
        }

        $this->writeConfig();
    }

    public function getAddresses()
    {
        $this->getConfig();

        $workspace = &$this->config['workspaces'][$this->config['active_workspace']];

        return $workspace['domains'];
    }

    private function readConfig()
    {
        if (!file_exists($this->config_file)) {
            $this->config = array();
            return;
        }

        $config_json = file_get_contents($this->config_file);
        if (false === $config_json) {
            throw new Exception("Could not read '{$this->config_file}'.");
        }

        // if the file is readable but contains nothing,
        // do not try to parse it as JSON
        if (empty($config_json)) {
            $this->config = array();
            return;
        }

        $this->config = json_decode($config_json, true);

        if (null === $this->config) {
            throw new Exception("The contents of '{$this->config_file}' is not valid JSON.");
        }
    }

    private function writeConfig()
    {
        $workspace = &$this->config['workspaces'][$this->config['active_workspace']];
        $saved_workspace = &$this->saved_config['workspaces'][$this->saved_config['active_workspace']];

        foreach ($workspace['domains'] as $domain) {
            $resolver_file = "{$this->resolver_dir}/{$domain['hostname']}";
            if (!file_exists($resolver_file)
                && !file_put_contents($resolver_file, "nameserver 127.0.0.1\n")
            ) {
                throw new Exception("Could not create resolver file: '{$resolver_file}'");
            }
        }
        foreach ($saved_workspace['domains'] as $key => $domain) {
            $resolver_file = "{$this->resolver_dir}/{$domain['hostname']}";
            if (!array_key_exists($key, $workspace['domains'])
                && file_exists($resolver_file)
            ) {
                if (!unlink($resolver_file)) {
                    throw new Exception("Could not remove resolver file: '{$resolver_file}'");
                }
            }
        }

        if ($this->config['active_workspace'] != $this->saved_config['active_workspace']
            && !count($saved_workspace['domains'])
        ) {
            unset($this->config['workspaces'][$this->saved_config['active_workspace']]);
        }

        $config_dir = dirname($this->config_file);
        if (!is_dir($config_dir) && !mkdir($config_dir, 0755, true)) {
            throw new Exception("Could not create configuration directory: '{$config_dir}'.");
        }
        file_put_contents($this->config_file, json_encode($this->config));
        $this->saved_config = $this->config;

        $dnsmasq_addresses = array_map(
            function ($domain) {
                return "address=/{$domain['hostname']}/{$domain['ip_address']}";
            },
            $workspace['domains']
        );
        $address_lines = implode("\n", $dnsmasq_addresses);
        file_put_contents($this->dnsmasq_config_file, $address_lines);
    }

    private function pickPath(array $data, $key)
    {
        if (!array_key_exists($key, $data)) {
            throw new Exception("Config option '{$key}' is missing.");
        }

        return $data[$key];
    }
}
