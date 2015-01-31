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

    public function setupResolvDir()
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
}
