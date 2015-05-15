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
        list($major_version) = explode('.', $darwin_version);

        $version_commands = $this->getVersionCommands();

        if (!isset($version_commands[$major_version])) {
            throw new Exception("Unknown Darwin version: {$major_version} ({$darwin_version}).");
        }

        return $version_commands[$major_version];
    }

    private function getVersionCommands()
    {
        if ($this->version_commands) {
            return $this->version_commands;
        }

        // darwin 14 = OS X 10.10
        $this->version_commands['14'] = [
            '/usr/sbin/discoveryutil udnsflushcaches',
        ];

        // darwin 13 = OS X 10.9
        $this->version_commands['13'] = [
            '/usr/bin/dscacheutil -flushcache',
            '/usr/bin/killall -HUP mDNSResponder',
        ];

        // darwin 12 = OS X 10.8
        $this->version_commands['12'] = [
            '/usr/bin/killall -HUP mDNSResponder',
        ];

        // darwin 11 = OS X 10.7
        $this->version_commands['11'] = $this->version_commands['12'];

        // darwin 10 = OS X 10.6
        $this->version_commands['10'] = [
            '/usr/bin/dscacheutil -flushcache',
        ];

        // darwin 9 = OS X 10.5
        $this->version_commands['9'] = $this->version_commands['10'];

        return $this->version_commands;
    }
}
