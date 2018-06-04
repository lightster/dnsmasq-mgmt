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

class AddressListCommand extends Command implements AppAwareInterface
{
    use AppAwareTrait;

    protected function configure()
    {
        $this
            ->setName('address:list')
            ->setDescription('List addresses')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app     = $this->getSilexApplication();
        $service = $app['lstr.dnsmasq'];

        $service->setLoggerIsVerbose(
            OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()
        );

        $addresses = $service->getAddresses();
        $hostnames = array_keys($addresses);
        $max_hostname_length = array_reduce(
            $hostnames,
            function ($carry, $hostname) {
                return max($carry, strlen($hostname));
            },
            0
        );
        $hostname_length = $max_hostname_length + 2;

        foreach ($addresses as $hostname => $address) {
            $output->writeln(sprintf(
                "%-{$hostname_length}s %s",
                $hostname,
                $address['ip_address']
            ));
        }
    }
}
