<?php

namespace Resque\Component\Statistic;

/**
 * Stat interface
 */
interface StatisticInterface
{
    /**
     * Get the value of the supplied statistic counter for the specified statistic.
     *
     * @param string $stat The name of the statistic to get the stats for.
     * @return mixed Value of the statistic.
     */
    public function get($stat);

    /**
     * Increment the value of the specified statistic by a certain amount (default is 1)
     *
     * @param string $stat The name of the statistic to increment.
     * @param int $by The amount to increment the statistic by.
     * @return boolean True if successful, false if not.
     */
    public function increment($stat, $by = 1);

    /**
     * Decrement the value of the specified statistic by a certain amount (default is 1)
     *
     * @param string $stat The name of the statistic to decrement.
     * @param int $by The amount to decrement the statistic by.
     * @return boolean True if successful, false if not.
     */
    public function decrement($stat, $by = 1);

    /**
     * Delete a statistic with the given name.
     *
     * @param string $stat The name of the statistic to delete.
     * @return boolean True if successful, false if not.
     */
    public function clear($stat);
}
