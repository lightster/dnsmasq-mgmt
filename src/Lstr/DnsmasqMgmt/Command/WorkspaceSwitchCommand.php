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

class WorkspaceSwitchCommand extends Command implements AppAwareInterface
{
    use AppAwareTrait;

    protected function configure()
    {
        $this
            ->setName('workspace:switch')
            ->setDescription('Switch workspaces')
            ->addArgument(
                'workspace',
                InputArgument::REQUIRED,
                'What is the name of the workspace you want to switch to?'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app     = $this->getSilexApplication();
        $service = $app['lstr.dnsmasq'];

        $service->setLoggerIsVerbose(
            OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()
        );

        return $service->setWorkspace($input->getArgument('workspace'));
    }
}
