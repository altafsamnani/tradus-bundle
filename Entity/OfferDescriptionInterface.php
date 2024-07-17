<?php
/**
 * Created by PhpStorm.
 * User: pflikweert
 * Date: 14/02/2018
 * Time: 10:46.
 */

namespace TradusBundle\Entity;

/**
 * Interface OfferDescriptionInterface.
 */
interface OfferDescriptionInterface
{
    // The offer description fields.
    const FIELD_TITLE = 'title';
    const FIELD_TITLE_SLUG = 'title_slug';
    const FIELD_SLUG = 'slug';
    const FIELD_DESCRIPTION = 'description';

    // The error messages for the offer description.
    const FIELD_LOCALE_BLANK_ERROR = 'The locale must be set.';
    const FIELD_TITLE_SLUG_BLANK_ERROR = 'The title_slug must be set.';
    const FIELD_OFFER_BLANK_ERROR = 'The offer must be set.';
    const FIELD_TITLE_BLANK_ERROR = 'The title must be set.';
}
