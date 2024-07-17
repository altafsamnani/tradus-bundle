<?php

namespace TradusBundle\Utils\CurrencyExchange;

use NumberFormatter;
use PDO;

/**
 * Class CurrencyExchange.
 */
class CurrencyExchange implements CurrencyExchangeInterface
{
    /** @var array */
    protected $exchangeRates = [];

    /**
     * CurrencyExchange constructor.
     *
     * @param PDO $connection
     */
    public function __construct(PDO $connection)
    {
        $singletonCurrencyExchange = new SingletonCurrencyExchange();
        $this->exchangeRates = $singletonCurrencyExchange->getExchangeRates();
    }

    /**
     * Function for exchanging currencies into an euro value.
     *
     * @param float $price
     * @param string $currency
     * @param bool $roundUp
     *
     * @return float
     *   Returns the Euro value.
     * @throws CurrencyExchangeException
     *   Throws an exception when the currency does not exist.
     */
    public function getEuroValue($price, $currency, $roundUp = true)
    {
        if (! isset($this->exchangeRates[$currency])) {
            throw new CurrencyExchangeException(
                "Currency: $currency is not set in the config file"
            );
        }
        $euroPrice = $price;
        if ($currency !== self::DEFAULT_CURRENCY) {
            $euroPrice = $price / $this->exchangeRates[$currency];
        }

        return $roundUp ? ceil($euroPrice) : $euroPrice;
    }

    /**
     * Function for exchanging currencies into an Polish Zloty value.
     *
     * @param float $price
     * @param string $currency
     *
     * @return float
     *   Returns the Zloty value.
     * @throws CurrencyExchangeException
     *   Throws an exception when the currency does not exist.
     */
    public function getPolishZlotyValue(float $price, string $currency)
    {
        $exchangeRates = $this->getExchangeRates($price, $currency);

        return $exchangeRates[self::POLISH_ZLOTY_CURRENCY];
    }

    /**
     * Function for getting the exchange rates for a given price.
     *
     * @param float $price
     * @param string $currency
     *
     * @param bool $roundUp
     * @return array
     *   Returns an array containing the exchange rates.
     * @throws CurrencyExchangeException Throws an exception when the currency does not exist.
     */
    public function getExchangeRates($price, $currency, $roundUp = true)
    {
        if (! empty($this->exchangeRates)) {
            foreach ($this->exchangeRates as $exchangeCurrency => $rate) {
                if ($exchangeCurrency == $currency) {
                    $result[$exchangeCurrency] = $price;
                } else {
                    $tempPrice = ceil(($price / $this->exchangeRates[$currency]) * $rate);
                    $tempPrice = ceil($tempPrice / 100) * 100;
                    $result[$exchangeCurrency] = $tempPrice;
                }
            }
        }

        return $result;
    }

    /**
     * @param array $originalPriceArr
     * @param array $marketValueArr
     * @return array
     */
    public function getEstimatedPrice(?array $originalPriceArr = null, ?array $marketValueArr = null)
    {
        if (! $originalPriceArr || ! $marketValueArr) {
            return [];
        }
        $priceValue = [];
        foreach ($marketValueArr as $currency => $value) {
            $priceValue[$currency] = abs($value - $originalPriceArr[$currency]);
        }

        return $priceValue;
    }

    public function getSingletonExchangeRate()
    {
        return $this->exchangeRates;
    }

    /**
     * Function for formatting localised currency without fractions.
     *
     * @param float|int $number
     *   The number to be formatted.
     * @param null $currency
     *   The currency to be used.
     * @param null $locale
     *   The locale for which the currency and value are to be rendered.
     *
     * @return string|bool
     *   Returns the rendered number or FALSE when empty.
     */
    public static function getLocalisedCurrency($number, $currency = null, $locale = null)
    {
        if (! empty($number)) {
            $currencyLocale = $locale;
            if ($locale != 'pl' && $currency == 'PLN') {
                // setting to italian because it provides us the format we want (XXX PLN instead of PLN XXX)
                $currencyLocale = 'it_IT';
            }

            $fmt = new NumberFormatter($currencyLocale, NumberFormatter::CURRENCY);
            $fmt->setTextAttribute(NumberFormatter::CURRENCY_CODE, $currency);
            $fmt->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
            $newCurrency = $fmt->formatCurrency($number, $currency);

            return str_replace([',', '.'], ' ', $newCurrency);
        }

        return false;
    }
}
