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

class AddressAddCommand extends Command implements AppAwareInterface
{
    use AppAwareTrait;

    protected function configure()
    {
        $this
            ->setName('address:add')
            ->setDescription('Add a new address')
            ->addArgument(
                'hostname',
                InputArgument::REQUIRED,
                'What is the hostname you want to setup?'
            )
            ->addArgument(
                'ip-address',
                InputArgument::REQUIRED,
                'What is the IP address you want to point the hostname to?'
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

        return $service->addAddress(
            $input->getArgument('hostname'),
            $input->getArgument('ip-address')
        );
    }
}
