<?php

namespace TradusBundle\Entity;

/**
 * Interface AppDataInterface.
 */
interface AppDataInterface
{
    public const LABEL_FORCEUPGRADE = 'forceUpgrade';
    public const LABEL_LOCALE = 'locale';
    public const LABEL_CURRENCIES = 'currencies';
    public const LABEL_HELPCENTER = 'helpCenter';
    public const LABEL_SOCIALMEDIA = 'social';
    public const LABEL_TERMSLINK = 'terms';
    public const LABEL_PRIVACYLINK = 'privacyPolicy';
    public const LABEL_COOKIEPOLICY = 'cookiePolicy';
    public const LABEL_HELPLINK = 'helpLink';
    public const LABEL_SELLINGLINK = 'startSellingLink';
    public const LABEL_OAUTH = 'oauth';
    public const LABEL_SHOWOFFERBANNER = 'show_offer_banner';

    /**
     * @return array
     */
    public function transform(): array;
}
