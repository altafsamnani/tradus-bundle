<?php

namespace TradusBundle\Entity;

/**
 * Interface OfferImageInterface.
 */
interface OfferImageInterface
{
    public const STATUS_PENDING = 0;
    public const STATUS_PENDING_APOLLO = 10;
    public const STATUS_ONLINE = 100;
    public const STATUS_OFFLINE = -10;
    public const STATUS_DELETED = -200;

    public const SIZE_STATUS_FOUND = 1;
    public const SIZE_STATUS_NOT_FOUND = 0;

    public const FIELD_OFFER_ID = 'offer';

    public const PARAMETER_ID = 'id';
    public const PARAMETER_IMAGE_REPORTED = 'reported';
    public const PARAMETER_URL = 'url';
    public const PARAMETER_IMAGE_TEXT = 'image_text_found';
    public const PARAMETER_SORT_ORDER = 'sort_order';

    public const PARAMETER_SORT_ORDER_POSE = 'sort_order_pose';
    public const PARAMETER_WIDTH = 'width';
    public const PARAMETER_HEIGHT = 'height';

    public const IMAGE_SIZE_SMALL = 'small';
    public const IMAGE_SIZE_MEDIUM = 'medium';
    public const IMAGE_SIZE_LARGE = 'large';
    public const IMAGE_SIZES = [
        self::IMAGE_SIZE_SMALL,
        self::IMAGE_SIZE_MEDIUM,
        self::IMAGE_SIZE_LARGE,
    ];

    public const IMAGE_SIZE_PRESETS = [
        self::IMAGE_SIZE_SMALL => ';p=SMALL',
        self::IMAGE_SIZE_MEDIUM => ';p=THUMB',
        self::IMAGE_SIZE_LARGE => null,
    ];
}
