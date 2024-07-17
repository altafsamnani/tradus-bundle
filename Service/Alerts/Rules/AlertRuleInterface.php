<?php

namespace TradusBundle\Service\Alerts\Rules;

use TradusBundle\Service\Alerts\Notifications\PushNotification;

/**
 * Interface AlertRuleInterface.
 */
interface AlertRuleInterface
{
    const RULE_TYPE_MATCHING_OFFER = 1;

    const ALERT_CRON_ERROR_TO = 'jair.foro@olx.com';
    const ALERT_CRON_ERROR_BCC = ['daniel.andrade@olx.com', 'markus.scherner@olx.com', 'sabari.jayakumar@olx.com', 'richard.reveron@olx.com'];

    public function getDataForUpdate();

    public function getEntity();

    public function getUser();

    public function getEmail(): ?AlertRuleResponse;

    public function getPushNotification(): ?PushNotification;
}
