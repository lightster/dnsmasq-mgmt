<?php

namespace Lstr\DnsmasqMgmt\Service;

use Exception;

use Symfony\Component\Process\Process;

class BrewEnvironmentService implements EnvironmentServiceInterface
{
    private $environment;

    private $setup_commands;
    private $version_commands;

    public function __construct(array $environment)
    {
        $this->environment = $environment;
        $this->dnsmasq_config_template = '/usr/local/opt/dnsmasq/dnsmasq.conf.example';
        $this->dnsmasq_config = '/usr/local/etc/dnsmasq.conf';
        $this->dnsmasq_dir = '/usr/local/etc/dnsmasq.d';
    }

    public function setupDnsmasq()
    {
        $setup_commands = '';
        if (!is_dir($this->dnsmasq_dir) || !is_writable($this->dnsmasq_dir)) {
            $all_commands = $this->getSetupCommands();
            $sudo_commands = array_map(
                function ($command) {
                    if ($command) {
                        return "sudo {$command}";
                    }

                    return '';
                },
                $all_commands
            );
            $setup_commands = implode("\n", $sudo_commands);
        }

        $shell = <<<SHELL
set -e
set -u
set -v

brew install dnsmasq

{$setup_commands}
SHELL;

        $process = new Process($shell);
        $process->setTimeout(60);
        $process->setIdleTimeout(60);
        $process->mustRun(function ($type, $buffer) {
            if (Process::ERR === $type) {
                fwrite(STDERR, $buffer);
            } else {
                fwrite(STDOUT, $buffer);
            }
        });

        $has_file_contents = file_exists($this->dnsmasq_config)
            && filesize($this->dnsmasq_config) <= 0;
        if (!$has_file_contents
            && !copy($this->dnsmasq_config_template, $this->dnsmasq_config)
        ) {
            throw new Exception("Could not create '{$this->dnsmasq_config}'.");
        }
    }

    public function clearDnsCache()
    {
        $all_commands = $this->getClearCacheCommands();
        $sudo_commands = array_map(
            function ($command) {
                if ($command) {
                    return "sudo {$command}";
                }

                return '';
            },
            $all_commands
        );
        $command_string = implode("\n", $sudo_commands);

        $shell = <<<SHELL
set -e
set -u
set -v
{$command_string}
SHELL;

        $process = new Process($shell);
        $process->setTimeout(60);
        $process->setIdleTimeout(60);
        $process->mustRun(function ($type, $buffer) {
            if (Process::ERR === $type) {
                fwrite(STDERR, $buffer);
            } else {
                fwrite(STDOUT, $buffer);
            }
        });

        return;
    }

    public function getSetupCommands()
    {
        if ($this->setup_commands) {
            return $this->setup_commands;
        }

        $user  = posix_getpwuid(posix_geteuid());
        $user_name = $user['name'];

        $this->setup_commands = [
            "mkdir -p {$this->dnsmasq_dir}",
            "touch {$this->dnsmasq_config}",
            "chown {$user_name}:admin {$this->dnsmasq_config} {$this->dnsmasq_dir}",
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
