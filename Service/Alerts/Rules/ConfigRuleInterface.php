<?php

namespace TradusBundle\Service\Alerts\Rules;

/**
 * Interface ConfigRuleInterface.
 */
interface ConfigRuleInterface
{
    /**
     * @return int
     */
    public function getRuleType();

    /**
     * Get the date for sending the first update.
     * @return \DateTime
     */
    public function getFirstUpdateDate();

    /**
     * get the interval date for sending updates.
     * @return \DateTime
     */
    public function getIntervalDate();
}
