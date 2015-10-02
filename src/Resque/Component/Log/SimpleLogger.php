<?php

namespace Resque\Component\Log;

use Psr\Log\AbstractLogger;

/**
 * Throw away CLI logger
 */
class SimpleLogger extends AbstractLogger
{
    /**
     * {@inheritDoc}
     */
    public function log($level, $message, array $context = array())
    {
        fwrite(
            STDOUT,
            sprintf(
                '[%s][%s] %s' . PHP_EOL,
                $level,
                strftime('%Y-%m-%d %T'),
                $this->interpolate($message, $context)
            )
        );
    }

    /**
     * Interpolate
     *
     * From PSR-3 doc
     *
     * @param $message
     * @param array $context
     * @return string
     */
    protected function interpolate($message, array $context = array())
    {
        $replace = array();

        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }

        return strtr($message, $replace);
    }
}
