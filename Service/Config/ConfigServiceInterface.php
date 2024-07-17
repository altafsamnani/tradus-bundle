<?php

namespace TradusBundle\Service\Config;

/**
 * Interface ConfigServiceInterface.
 */
interface ConfigServiceInterface
{
    public const GROUP_SEARCH = 'search';
    public const GROUP_UNIT_TEST = 'unittests';
    public const GROUP_ALERTS = 'alerts';
    public const GROUP_CRITEO = 'criteo';
    public const GROUP_EMAILS = 'emails';

    public const DEFAULT_LOCALE_CONFIG = 'kernel.default_locale';
    public const DEFAULT_CURRENCY_CONFIG = 'default_currency';
    public const UTM_SOURCE = 'sendgrid.com';
    public const UTM_MEDIUM = 'email';

    public const TRADUS_PRO_PROD = [
        'url' => 'https://pro.tradus.com',
        'basicAuth' => 'NzpVb1pJTmVWNXBtQzBQOGppNURYWmJrVmZuTGM3NEs1Znd1U1NMdE9i',
    ];
    public const TRADUS_PRO_DEV = [
        'url' => 'http://pro.tradus.dev',
        'basicAuth' => 'Mjo1SXltWGRaaUMyTXp4SElmOWZyVDhQTURocG9zQVBYemVOTnF5SFNo',
    ];
    public const TPRO_LOGIN_LINK = '/frontend/login';
    public const TPRO_REG_LINK = '/frontend/signup';
    public const TPRO_LOCALE = [
        'nl' => 'nl',
        'en' => 'en',
        'pl' => 'pl',
        'ro' => 'ro',
        'pt-pt' => 'pt',
        'ru' => 'ru',
        'es' => 'es',
        'it' => 'it',
        'fr' => 'fr',
        'de' => 'de',
        'bg' => 'en',
        'da' => 'en',
        'el' => 'en',
        'hr' => 'en',
        'hu' => 'en',
        'lt' => 'en',
        'sk' => 'en',
        'sr' => 'en',
        'tr' => 'en',
        'uk' => 'en',
    ];

    public const INTL_PHONE_ERRORS = [
        'Invalid phone number',
        'Invalid country code',
        'Phone number is too short',
        'Phone number is too long',
        'Invalid phone number',
    ];

    /* Localized content for WL -------------------------------------------------------------------- */
    public const LOCALE_YOUTUBE_LINK = [
        'de' => 'https://www.youtube.com/watch?v=UCXSKu-eOzg&list=PLXZ_iV-2LAkrgwwusJH5utpt12-BtKg86',
        'pl' => 'https://www.youtube.com/watch?v=JtkcqStMjn0&list=PLXZ_iV-2LAkpmYo-FWwyA0TMj4A4PUBbI',
        'ro' => 'https://www.youtube.com/watch?v=AdcbZJsvkNQ&list=PLXZ_iV-2LAkr2LytUyXl-kRM7w13DxxV3',
        'en' => 'https://www.youtube.com/watch?v=JwsMzQ5aB5E&list=PLXZ_iV-2LAkon_nlRhjlPLcZCcDhRRvRW',
    ];

    public const IMAGES_AVAILABLE_LOCALE = ['da', 'de', 'en', 'es', 'fr', 'nl', 'pl', 'ro'];

    public const NOT_REDIRECT_ACCOUNT = ['search', 'showOffer', 'sellerSearch2', 'sellerSearch', 'seller2', 'sellerPage', 'sellerPageSearch'];
}
