<?php

namespace Lstr\DnsmasqMgmt\Command;

use Exception;

use Lstr\Silex\App\AppAwareInterface;
use Lstr\Silex\App\AppAwareTrait;
use Silex\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WorkspaceListCommand extends Command implements AppAwareInterface
{
    use AppAwareTrait;

    protected function configure()
    {
        $this
            ->setName('workspace:list')
            ->setAliases(['list-workspaces'])
            ->setDescription('List workspaces')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app     = $this->getSilexApplication();
        $service = $app['lstr.dnsmasq'];

        $service->setLoggerIsVerbose(
            OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()
        );

        $active_workspace = $service->getActiveWorkspaceName();
        $workspaces = $service->getWorkspaces();
        ksort($workspaces);

        foreach ($workspaces as $workspace_name => $workspace) {
            $output->writeln(sprintf(
                "%1s %s",
                ($workspace_name == $active_workspace ? '*' : ''),
                $workspace_name
            ));
        }
    }
}
