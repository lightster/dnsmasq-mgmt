<?php

namespace Lstr\DnsmasqMgmt\Service;

use Exception;

class BrewEnvironmentService implements EnvironmentServiceInterface
{
    private $environment;
    private $resolver_dir;
    private $dnsmasq_template;
    private $dnsmasq_config;
    private $dnsmasq_dir;

    private $process_service;

    private $setup_commands;
    private $version_commands;

    public function __construct(array $environment, ProcessService $process_service)
    {
        $this->environment = $environment;
        $this->resolver_dir = '/etc/resolver';
        $this->dnsmasq_template = '/usr/local/opt/dnsmasq/dnsmasq.conf.example';
        $this->dnsmasq_config = '/usr/local/etc/dnsmasq.conf';
        $this->dnsmasq_dir = '/usr/local/etc/dnsmasq.d';

        $this->process_service = $process_service;
    }

    public function setupDnsmasq()
    {
        $setup_commands = $this->getSetupCommands();
        $sudo_commands = $this->process_service->prependSudo($setup_commands);
        $all_commands = [
            'brew install dnsmasq',
        ] + $sudo_commands;

        $this->process_service->mustRun($all_commands);

        $config_contents = null;

        $has_file_contents = file_exists($this->dnsmasq_config)
            && filesize($this->dnsmasq_config) <= 0;
        if (!$has_file_contents) {
            $config_contents = file_get_contents(
                $this->dnsmasq_template
            );
        } else {
            $config_contents = preg_replace(
                '/\r?\n?#BEGIN-DNSMASQ-MGMT.*#END-DNSMASQ-MGMT\r?\n?/s',
                '',
                file_get_contents($this->dnsmasq_config)
            );
        }

        $config_contents .= <<<TXT


#BEGIN-DNSMASQ-MGMT
conf-dir={$this->dnsmasq_dir}
#END-DNSMASQ-MGMT

TXT;

        if (!file_put_contents($this->dnsmasq_config, $config_contents)) {
            throw new Exception("Could not write '{$this->dnsmasq_config}'");
        }
    }

    public function clearDnsCache()
    {
        $all_commands = $this->getClearCacheCommands();
        $sudo_commands = $this->process_service->prependSudo($all_commands);

        $this->process_service->mustRun($sudo_commands);
    }

    public function getSetupCommands()
    {
        if ($this->setup_commands) {
            return $this->setup_commands;
        }

        $user  = posix_getpwuid(posix_geteuid());
        $user_name = $user['name'];

        $this->setup_commands = [
            "mkdir -p {$this->dnsmasq_dir} {$this->resolver_dir}",
            "touch {$this->dnsmasq_config}",
            "chown {$user_name}:admin {$this->dnsmasq_config} "
                . "{$this->dnsmasq_dir} {$this->resolver_dir}",
            "cp /usr/local/opt/dnsmasq/homebrew.mxcl.dnsmasq.plist /Library/LaunchDaemons",
            "launchctl unload /Library/LaunchDaemons/homebrew.mxcl.dnsmasq.plist",
            "launchctl load /Library/LaunchDaemons/homebrew.mxcl.dnsmasq.plist",
        ];

        return $this->setup_commands;
    }

    public function getClearCacheCommands()
    {
        $all_commands   = [];
        $all_commands[] = '/bin/launchctl stop homebrew.mxcl.dnsmasq';
        $all_commands[] = '/bin/launchctl start homebrew.mxcl.dnsmasq';
        $all_commands[] = '';
        $all_commands   = array_merge(
            $all_commands,
            $this->getVersionCommand($this->environment['release'])
        );

        return $all_commands;
    }

    private function getVersionCommand($darwin_version)
    {
        $version_commands = [];
        foreach ($this->getVersionCommands() as $bin => $command) {
            if (file_exists($bin)) {
                $version_commands[] = $command;
            }
        }

        return $version_commands;
    }

    private function getVersionCommands()
    {
        if ($this->version_commands) {
            return $this->version_commands;
        }

        $this->version_commands = [
            '/usr/sbin/discoveryutil' => '/usr/sbin/discoveryutil udnsflushcaches',
            '/usr/bin/dscacheutil'    => '/usr/bin/dscacheutil -flushcache',
            '/usr/bin/killall'        => '/usr/bin/killall -HUP mDNSResponder',
        ];

        return $this->version_commands;
    }
}
