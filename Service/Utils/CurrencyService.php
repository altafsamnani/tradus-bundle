<?php

namespace TradusBundle\Service\Utils;

class CurrencyService
{
    const LANGUAGE_POLAND = 'pl';
    const LANGUAGE_ROMANIA = 'ro';
    const LANGUAGE_ENGLISH = 'en';

    const CURRENCY_POLAND = 'PLN';
    const CURRENCY_ROMANIA = 'RON';
    const CURRENCY_DEFAULT = 'EUR';

    /**
     * To be updated with values from a db table
     * And / Or to be completed with needed values.
     *
     * @param $locale
     * @return string
     */
    public function getCurrency(string $locale = self::CURRENCY_DEFAULT): string
    {
        switch ($locale) {
            case self::LANGUAGE_POLAND:
                return self::CURRENCY_POLAND;
            break;
            case self::LANGUAGE_ROMANIA:
                return self::CURRENCY_ROMANIA;
            break;
            default:
                return self::CURRENCY_DEFAULT;
                break;
        }
    }
}
