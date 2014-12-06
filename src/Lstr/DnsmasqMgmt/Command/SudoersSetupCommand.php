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

class SudoersSetupCommand extends Command implements AppAwareInterface
{
    use AppAwareTrait;

    protected function configure()
    {
        $this
            ->setName('sudoers:install')
            ->setAliases(['install-sudoers'])
            ->setDescription('Setup sudoers file to allow for passwordless dnsmasq management')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (0 !== posix_geteuid()) {
            $output->writeln("Please re-run '{$this->getName()}' with sudo.");
            exit(1);
        }

        $app     = $this->getSilexApplication();
        $service = $app['lstr.dnsmasq'];

        $service->setupSudoers();
    }
}
