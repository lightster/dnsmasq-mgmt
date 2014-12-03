<?php

namespace Lstr\DnsmasqMgmt\Service;

use Exception;

use Symfony\Component\Process\Process;

class BrewEnvironmentService implements EnvironmentServiceInterface
{
    private $environment;

    private $version_commands;

    public function __construct(array $environment)
    {
        $this->environment = $environment;
    }

    public function clearDnsCache()
    {
        $version_command = $this->getVersionCommand($this->environment['release']);

        $shell = <<<SHELL
set -e
set -u
set -v

{$version_command}

sudo launchctl stop homebrew.mxcl.dnsmasq
sudo launchctl start homebrew.mxcl.dnsmasq
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
        $this->version_commands['14'] = <<<OSX_COMMAND
sudo discoveryutil udnsflushcaches
OSX_COMMAND;

        // darwin 13 = OS X 10.9
        $this->version_commands['13'] = <<<OSX_COMMAND
sudo dscacheutil -flushcache
sudo killall -HUP mDNSResponder
OSX_COMMAND;

        // darwin 12 = OS X 10.8
        $this->version_commands['12'] = <<<OSX_COMMAND
sudo killall -HUP mDNSResponder
OSX_COMMAND;

        // darwin 11 = OS X 10.7
        $this->version_commands['11'] = $this->version_commands['12'];

        // darwin 10 = OS X 10.6
        $this->version_commands['10'] = <<<OSX_COMMAND
sudo dscacheutil -flushcache
OSX_COMMAND;

        // darwin 9 = OS X 10.5
        $this->version_commands['9'] = $this->version_commands['10'];

        return $this->version_commands;
    }
}
