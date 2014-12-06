<?php

namespace Lstr\DnsmasqMgmt\Service;

use Exception;

use Symfony\Component\Process\Process;

class SudoersService
{
    private $env_service;

    public function __construct(EnvironmentServiceInterface $env_service)
    {
        $this->env_service = $env_service;
    }

    public function setupSudoers()
    {
        $sudoers = file_get_contents('/etc/sudoers');
        if (false === $sudoers) {
            throw new Exception("Could not read '/etc/sudoers'");
        }

        $sudoers = preg_replace(
            '/\r?\n?#BEGIN-DNSMASQ-MGMT.*#END-DNSMASQ-MGMT\r?\n?/s',
            '',
            $sudoers
        );

        $all_commands = $this->env_service->getClearCacheCommands();
        $prepared_commands = [];
        foreach ($all_commands as $key => $command) {
            if (!$command) {
                continue;
            }

            $alias = "DNSMASQ_MGMT_{$key}";
            $prepared_commands[$alias] = "Cmnd_Alias {$alias} = {$command}";
        }

        $command_string = implode("\n", $prepared_commands);
        $alias_list = implode(', ', array_keys($prepared_commands));

        $sudoers .= <<<TXT

#BEGIN-DNSMASQ-MGMT
{$command_string}
%admin ALL=(root) NOPASSWD: {$alias_list}
#END-DNSMASQ-MGMT

TXT;

        if (!file_put_contents('/etc/sudoers.dnsmasq-mgmt', $sudoers)) {
            throw new Exception("Could not write '/etc/sudoers.dnsmasq-mgmt'");
        }

        chmod('/etc/sudoers.dnsmasq-mgmt', 0440);

        $shell = <<<SHELL
set -e
set -u
set -v

# check syntax of file
visudo -c -s -f /etc/sudoers.dnsmasq-mgmt

# moving into place
mv /etc/sudoers.dnsmasq-mgmt /etc/sudoers
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
}
