<?php

namespace Lstr\DnsmasqMgmt\Service;

use Exception;

use Symfony\Component\Process\Process;

class SudoersService
{
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

        $sudoers .= <<<'TXT'

#BEGIN-DNSMASQ-MGMT
Cmnd_Alias LCTL_DNSMASQ_STOP = /bin/launchctl stop homebrew.mxcl.dnsmasq
Cmnd_Alias LCTL_DNSMASQ_START = /bin/launchctl start homebrew.mxcl.dnsmasq
Cmnd_Alias DISCOVERYUTIL = /usr/sbin/discoveryutil udnsflushcaches
%admin ALL=(root) NOPASSWD: LCTL_DNSMASQ_STOP, LCTL_DNSMASQ_START, DISCOVERYUTIL
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
