<?php

namespace TradusBundle\Transformer;

use TradusBundle\Entity\AppDataInterface;

/**
 * Class AppDataTransformer.
 */
class AppDataTransformer extends AbstractTransformer implements AppDataInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform(): array
    {
        return [];
    }

    public function transformConfig($userAgent): array
    {
        global $kernel;
        $container = $kernel->getContainer();
        $siteCodes = $container->getParameter('sitecode');
        $locale = $container->getParameter('locale');
        $currencies = $container->getParameter('currencies');
        $footer = $this->snakeCaseToCamelCase($siteCodes['footer_links']);
        $oauth = $this->snakeCaseToCamelCase($container->getParameter('oauth'));
        $forceUpgrade = $this->getForceUpgradeFlag($userAgent);

        return [
            self::LABEL_FORCEUPGRADE => $forceUpgrade,
            self::LABEL_LOCALE => $locale,
            self::LABEL_CURRENCIES => $currencies,
            self::LABEL_HELPCENTER => $siteCodes['help_center'],
            self::LABEL_SOCIALMEDIA => $siteCodes['social_media'],
            self::LABEL_TERMSLINK => $siteCodes['footer_links']['terms']['url'],
            self::LABEL_PRIVACYLINK => $siteCodes['footer_links']['privacy_policy']['url'],
            self::LABEL_COOKIEPOLICY => $siteCodes['footer_links']['cookies_policy']['url'],
            self::LABEL_HELPLINK => $siteCodes['footer_links']['help']['url'],
            self::LABEL_SELLINGLINK => $siteCodes['footer_links']['start_selling']['url'],
            self::LABEL_OAUTH => $oauth,
            self::LABEL_SHOWOFFERBANNER => $siteCodes['apps']['show_offer_banner'] ?? false,
        ];
    }
}
