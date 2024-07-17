<?php

namespace TradusBundle\Entity;

/**
 * Interface SellerInterface.
 */
interface SellerInterface
{
    public const FIELD_ID = 'id';
    public const FIELD_SELLER_ID = 'seller_id';
    public const FIELD_V1_SELLER_ID = 'seller';
    public const FIELD_USER_ID = 'user_id';
    public const FIELD_TPRO_ID = 'tpro_id';
    public const FIELD_SLUG = 'slug';
    public const FIELD_URL = 'url';
    public const FIELD_EMAIL = 'email';
    public const FIELD_NAME = 'name';
    public const FIELD_LOCALE = 'locale';
    public const FIELD_CITY = 'city';
    public const FIELD_COUNTRY = 'country';
    public const FIELD_PHONE = 'phone';
    public const FIELD_MOBILE_PHONE = 'mobile_phone';
    public const FIELD_ADDRESS = 'address';
    public const FIELD_GEO_LOCATION = 'geo_location';
    public const FIELD_GEO_LOCATION_OBJECT = 'geo_location_object';
    public const FIELD_STATUS = 'status';
    public const FIELD_TESTUSER_FLAG = 'testuser';
    public const FIELD_ADDITIONAL_SERVICES = 'services';
    public const FIELD_V1_ID = 'v1_id';
    public const FIELD_V1_STATUS = 'sellerStatus';
    public const FIELD_COMPANY_NAME = 'company_name';
    public const FIELD_V1_COMPANY_NAME = 'companyName';
    public const FIELD_LOGO = 'logo';
    public const FIELD_TYPE = 'seller_type';
    public const FIELD_V1_TYPE = 'sellerType';
    public const FIELD_CREATED_AT = 'created_at';
    public const FIELD_UPDATED_AT = 'updated_at';
    public const FIELD_SOURCE = 'source';
    public const FIELD_PARENT_SELLER_ID = 'parent_seller';
    public const FIELD_POINT_OF_CONTACT = 'point_of_contact';
    public const FIELD_WHATSAPP_ENABLED = 'whatsapp_enabled';
    public const FIELD_PREFERENCE_LANGUAGE_OPTIONS = 'language_options';
    public const FIELD_SITECODES = 'sitecodes';
    public const FIELD_ANALYTICS_API_TOKEN = 'analytics_api_token';
    public const FIELD_ROLES = 'roles';
    public const FIELD_PASSWORD = 'password';
    public const FIELD_SINCE = 'since';
    public const FIELD_WEBSITE = 'website';
    public const FIELD_BADGE_REPLY_FAST = 'badge_reply_fast';
    public const FIELD_BADGE_REPLY_FAST_LABEL = 'badge_reply_fast_label';
    public const FIELD_BADGE_REPLY_FAST_CALCULATION = 'badge_reply_fast_calc';
    public const FIELD_ANONYMIZED_AT = 'anonymized_at';
    public const FIELD_CHILD_SELLERS = 'child_sellers';
    public const FIELD_OFFERS_COUNT = 'offers_count';
    public const FIELD_OFFER_CATEGORIES = 'offer_categories';
    public const FIELD_LAST_LEAD_AT = 'last_lead_at';
    public const FIELD_SOLR_STATUS = 'solr_status';

    public const SOLR_FIELD_SELLER_ID = 'seller_id';
    public const SOLR_FIELD_INT_ID = 'id';
    public const SOLR_FIELD_USER_ID = 'user_id_facet_int';
    public const SOLR_FIELD_V1_ID = 'v1_id';
    public const SOLR_FIELD_SELLER_PARENT = 'parent_seller';
    public const SOLR_FIELD_SELLER_CATEGORY = 'category';
    public const SOLR_FIELD_SELLER_TYPE = 'sellerType';
    public const SOLR_FIELD_SELLER_NAME = 'name';
    public const SOLR_FIELD_SELLER_EMAIL = 'email';
    public const SOLR_FIELD_SELLER_URL = 'url';
    public const SOLR_FIELD_SELLER_LOCALE = 'locale';
    public const SOLR_FIELD_SELLER_SOURCE = 'source';
    public const SOLR_FIELD_SELLER_ADDRESS = 'address';
    public const SOLR_FIELD_SELLER_CITY = 'city';
    public const SOLR_FIELD_SELLER_COUNTRY = 'country';
    public const SOLR_FIELD_SELLER_COMPANY_NAME = 'company_name';
    public const SOLR_FIELD_SELLER_SLUG = 'slug';
    public const SOLR_FIELD_SELLER_LOGO = 'logo';
    public const SOLR_FIELD_SELLER_PHONE = 'phone';
    public const SOLR_FIELD_SELLER_MOBILE_PHONE = 'mobile_phone_facet_string';
    public const SOLR_FIELD_SELLER_OFFERS_COUNT = 'offers_count';
    public const SOLR_FIELD_SELLER_POINT_OF_CONTACT = 'point_of_contact';
    public const SOLR_FIELD_SELLER_GEO_LOCATION = 'geo_location';
    public const SOLR_FIELD_SELLER_GEO_LOCATION_OBJECT = 'geo_location_object';
    public const SOLR_FIELD_SELLER_ANALYTICS_API_TOKEN = 'analytics_api_token';
    public const SOLR_FIELD_SELLER_ROLES = 'roles';
    public const SOLR_FIELD_SELLER_PASSWORD = 'password';
    public const SOLR_FIELD_SELLER_PREFERENCES = 'preferences';
    public const SOLR_FIELD_SELLER_GEO_DISTANCE = '_dist_';
    public const SOLR_FIELD_SELLER_GEO_LOCATION_LATLON = 'latlon';
    public const SOLR_FIELD_WEBSITE = 'website_facet_string';

    public const SOLR_FIELD_SELLER_CREATED_AT = 'created_at';
    public const SOLR_FIELD_SELLER_HAS_LEAD_LAST_MONTH_FACET_INT = 'seller_lead_last_month_facet_int';
    public const SOLR_FIELD_SELLER_HAS_IMAGE_FACET_INT = 'seller_has_image_facet_int';
    public const SOLR_FIELD_SELLER_WHATSAPP_FACET_INT = 'whatsapp_enabled_facet_int';

    public const STATUS_ONLINE = 100;
    public const STATUS_OFFLINE = -10;
    public const STATUS_DELETED = -100;

    public const TESTUSER_IS_NOT_FLAG = 0;
    public const TESTUSER_IS_FLAG = 1;

    // Seller type constants.
    public const SELLER_TYPE_FREE = 0;
    public const SELLER_TYPE_PREMIUM = 1;
    public const SELLER_TYPE_PACKAGE_FREE = 2;
    public const SELLER_TYPE_PACKAGE_BRONZE = 3;
    public const SELLER_TYPE_PACKAGE_SILVER = 4;
    public const SELLER_TYPE_PACKAGE_GOLD = 5;

    public const SELLER_TYPE_SPARE_PARTS = 6;
    public const SELLER_TYPE_SELF_SERVE = 7;
    public const SELLER_TYPE_PACKAGE_PREMIUM = 8;
    public const SELLER_TYPE_PACKAGE_PREMIUM_PLUS = 9;
    public const SELLER_TYPE_PACKAGE_THREE_MONTHS_TRIAL = 10;
    public const SELLER_TYPE_CSV_UPLOAD = 11;

    public const SELLER_TYPES = [
        self::SELLER_TYPE_FREE,
        self::SELLER_TYPE_PREMIUM,
        self::SELLER_TYPE_PACKAGE_FREE,
        self::SELLER_TYPE_PACKAGE_BRONZE,
        self::SELLER_TYPE_PACKAGE_SILVER,
        self::SELLER_TYPE_PACKAGE_GOLD,
        self::SELLER_TYPE_SPARE_PARTS,
        self::SELLER_TYPE_SELF_SERVE,
        self::SELLER_TYPE_PACKAGE_PREMIUM,
        self::SELLER_TYPE_PACKAGE_PREMIUM_PLUS,
        self::SELLER_TYPE_PACKAGE_THREE_MONTHS_TRIAL,
        self::SELLER_TYPE_CSV_UPLOAD,
    ];

    public const PREMIUM_BADGE_SELLER_TYPES = [
        self::SELLER_TYPE_PREMIUM,
        self::SELLER_TYPE_PACKAGE_BRONZE,
        self::SELLER_TYPE_PACKAGE_SILVER,
        self::SELLER_TYPE_PACKAGE_GOLD,
        self::SELLER_TYPE_SPARE_PARTS,
        self::SELLER_TYPE_SELF_SERVE,
        self::SELLER_TYPE_PACKAGE_PREMIUM,
        self::SELLER_TYPE_PACKAGE_PREMIUM_PLUS,
    ];

    public const SELLER_PREFERENCES = [
        self::FIELD_PREFERENCE_LANGUAGE_OPTIONS,
    ];

    public const SELLER_FIELDS = [
        self::FIELD_V1_SELLER_ID,
        self::FIELD_SLUG,
        self::FIELD_EMAIL,
        self::FIELD_CITY,
        self::FIELD_COUNTRY,
        self::FIELD_NAME,
        self::FIELD_LOCALE,
        self::FIELD_PHONE,
        self::FIELD_ADDRESS,
        self::FIELD_STATUS,
        self::FIELD_COMPANY_NAME,
        self::FIELD_LOGO,
        self::FIELD_TYPE,
        self::FIELD_GEO_LOCATION,
    ];

    public const V1_STATUSES = [
        self::STATUS_ONLINE => 1,
        self::STATUS_OFFLINE => 0,
        self::STATUS_DELETED => 0,
    ];

    // The error messages for
    public const FIELD_TYPE_BLANK_ERROR = 'The Seller type must be set.';
    public const FIELD_EMAIL_BLANK_ERROR = 'The email must be set.';
    public const FIELD_SLUG_BLANK_ERROR = 'The slug must be set.';
    public const FIELD_COUNTRY_BLANK_ERROR = 'The country must be set.';
    public const FIELD_COMPANY_NAME_BLANK_ERROR = 'The company_name must be set.';
    public const FIELD_STATUS_BLANK_ERROR = 'The status must be set.';

    public const FIELD_ERRORS = [
        self::FIELD_EMAIL => self::FIELD_EMAIL_BLANK_ERROR,
        self::FIELD_COMPANY_NAME => self::FIELD_COMPANY_NAME_BLANK_ERROR,
        self::FIELD_COUNTRY => self::FIELD_COUNTRY_BLANK_ERROR,
        self::FIELD_STATUS => self::FIELD_STATUS_BLANK_ERROR,
        self::FIELD_TYPE => self::FIELD_TYPE_BLANK_ERROR,
        self::FIELD_SLUG => self::FIELD_SLUG_BLANK_ERROR,
    ];

    public const IMAGE_SIZE_SMALL = ';s=150x80';

    public const SOLR_STATUS_TO_UPDATE = 'to_update';
    public const SOLR_STATUS_IN_QUEUE = 'in_queue';
    public const SOLR_STATUS_IN_INDEX = 'in_index';
    public const SOLR_STATUS_NOT_IN_INDEX = 'not_in_index';
}
