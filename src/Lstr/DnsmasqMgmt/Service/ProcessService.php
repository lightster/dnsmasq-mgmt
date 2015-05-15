<?php

namespace Lstr\DnsmasqMgmt\Service;

use Exception;

use Symfony\Component\Process\Process;

class ProcessService
{
    private $log_service;

    public function __construct(LogService $log_service)
    {
        $this->log_service = $log_service;
    }

    public function mustRun(array $commands, $timeout = 60)
    {
        $command_format = <<<SHELL
echo {{ESCAPED_COMMAND}}
{{COMMAND}}
SHELL;
        $formatted_commands = $this->formatCommands($commands, $command_format);

        $shell = <<<SHELL
set -e
set -u

{$formatted_commands}
SHELL;

        $log_service = $this->log_service;

        $process = new Process($shell);
        $process->setTimeout($timeout);
        $process->setIdleTimeout($timeout);
        $process->mustRun(function ($type, $buffer) use ($log_service) {
            if (Process::ERR === $type) {
                $this->log_service->error($buffer);
            } else {
                $this->log_service->info($buffer);
            }
        });
    }

    public function prependSudo(array $commands)
    {
        return array_map(
            function ($command) {
                if (!$command) {
                    return '';
                }

                return "sudo {$command}";
            },
            $commands
        );
    }

    private function formatCommands(array $commands, $format)
    {
        $formatted_commands = array_map(
            function ($command) use ($format) {
                if (!$command) {
                    return '';
                }

                return str_replace(
                    [
                        '{{COMMAND}}',
                        '{{ESCAPED_COMMAND}}',
                    ],
                    [
                        $command,
                        escapeshellarg($command),
                    ],
                    $format
                );
            },
            $commands
        );

        return implode("\n", $formatted_commands);
    }
}
