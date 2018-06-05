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

class AddressUpdateCommand extends Command implements AppAwareInterface
{
    use AppAwareTrait;

    protected function configure()
    {
        $this
            ->setName('address:update')
            ->setDescription('Update the IP that an address points to')
            ->addArgument(
                'hostname',
                InputArgument::REQUIRED,
                'What is the hostname you want to update?'
            )
            ->addOption(
                'ip-address',
                null,
                InputOption::VALUE_REQUIRED,
                'What is the IP address you want to point the hostname to?'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app     = $this->getSilexApplication();
        $service = $app['lstr.dnsmasq'];

        if (!$input->getOption('ip-address')) {
            throw new Exception("At least one option ('ip-address') should be provided.");
        }

        $service->setLoggerIsVerbose(
            OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()
        );

        return $service->updateAddress(
            $input->getArgument('hostname'),
            $input->getOption('ip-address')
        );
    }
}
