<?php

namespace Lstr\DnsmasqMgmt\Service;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class LogService extends AbstractLogger
{
    /**
     * @var boolean
     */
    private $is_verbose = true;

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        $log_type = 'error';
        if (LogLevel::INFO === $level || LogLevel::DEBUG === $level) {
            $log_type = 'output';
        }

        if ('error' === $log_type) {
            fwrite(STDERR, $message);
        } elseif ($this->is_verbose) {
            fwrite(STDOUT, $message);
        }
    }

    /**
     * @param bool $is_verbose
     */
    public function setIsVerbose($is_verbose)
    {
        $this->is_verbose = (bool)$is_verbose;
    }
}
