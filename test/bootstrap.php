<?php

/**
 * Resque test bootstrap file - sets up a test environment.
 *
 * @package        Resque/Tests
 * @author        Chris Boulton <chris@bigcommerce.com>
 * @license        http://www.opensource.org/licenses/mit-license.php
 */

if (function_exists('pcntl_signal')) {
    // Override INT and TERM signals, so they do a clean shutdown.
    function sigint()
    {
        exit;
    }

    pcntl_signal(SIGINT, 'sigint');
    pcntl_signal(SIGTERM, 'sigint');
}
