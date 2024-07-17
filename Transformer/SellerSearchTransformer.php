<?php

namespace TradusBundle\Transformer;

use TradusBundle\Entity\Seller;

/**
 * Class SellerTransformer.
 */
class SellerSearchTransformer
{
    /**
     * Tranforms the Search Seller Response.
     *
     * @return array
     */
    public function transform($documents)
    {
        $results = [];
        foreach ($documents as $document) {
            $document[Seller::SOLR_FIELD_SELLER_GEO_LOCATION];
            $geoLocationObject = [];

            if (trim($document[Seller::SOLR_FIELD_SELLER_GEO_LOCATION]) != '') {
                $json = json_decode($document[Seller::SOLR_FIELD_SELLER_GEO_LOCATION], true);
                $geoLocationObject = [
                    'lat' => $json['lat'],
                    'lng' => $json['lng'],
                ];
            }

            $transformed = [
                Seller::FIELD_ID => $document[Seller::SOLR_FIELD_SELLER_ID],
                Seller::FIELD_EMAIL => $document[Seller::SOLR_FIELD_SELLER_EMAIL],
                Seller::FIELD_NAME => $document[Seller::SOLR_FIELD_SELLER_NAME],
                Seller::FIELD_LOCALE => $document[Seller::SOLR_FIELD_SELLER_LOCALE],
                Seller::FIELD_SLUG => $document[Seller::SOLR_FIELD_SELLER_SLUG],
                Seller::FIELD_ADDRESS => $document[Seller::SOLR_FIELD_SELLER_ADDRESS],
                Seller::FIELD_CITY => $document[Seller::SOLR_FIELD_SELLER_CITY],
                Seller::FIELD_COUNTRY => $document[Seller::SOLR_FIELD_SELLER_COUNTRY],
                Seller::FIELD_COMPANY_NAME => $document[Seller::SOLR_FIELD_SELLER_COMPANY_NAME],
                Seller::FIELD_LOGO=> $document[Seller::SOLR_FIELD_SELLER_LOGO],
                Seller::FIELD_PHONE => $document[Seller::SOLR_FIELD_SELLER_PHONE],
                Seller::FIELD_MOBILE_PHONE => $document[Seller::SOLR_FIELD_SELLER_MOBILE_PHONE],
                Seller::FIELD_STATUS => Seller::STATUS_ONLINE,
                Seller::FIELD_TYPE => $document[Seller::SOLR_FIELD_SELLER_TYPE],
                Seller::FIELD_CREATED_AT => $document[Seller::SOLR_FIELD_SELLER_CREATED_AT],
                Seller::FIELD_GEO_LOCATION => $document[Seller::SOLR_FIELD_SELLER_GEO_LOCATION],
                Seller::FIELD_GEO_LOCATION_OBJECT => $geoLocationObject,
                Seller::FIELD_SOURCE => $document[Seller::SOLR_FIELD_SELLER_SOURCE],
                Seller::FIELD_POINT_OF_CONTACT => $document[Seller::SOLR_FIELD_SELLER_POINT_OF_CONTACT],
                Seller::FIELD_ROLES => $document[Seller::SOLR_FIELD_SELLER_ROLES],
                Seller::FIELD_PASSWORD => $document[Seller::SOLR_FIELD_SELLER_PASSWORD],
                Seller::SOLR_FIELD_SELLER_PREFERENCES => [
                   Seller::FIELD_PREFERENCE_LANGUAGE_OPTIONS => $document[Seller::SOLR_FIELD_SELLER_PREFERENCES],
                ],
                Seller::SOLR_FIELD_SELLER_GEO_DISTANCE => $document[Seller::SOLR_FIELD_SELLER_GEO_DISTANCE],
                Seller::SOLR_FIELD_SELLER_HAS_LEAD_LAST_MONTH_FACET_INT => $document[Seller::SOLR_FIELD_SELLER_HAS_LEAD_LAST_MONTH_FACET_INT],
                Seller::SOLR_FIELD_SELLER_HAS_IMAGE_FACET_INT => $document[Seller::SOLR_FIELD_SELLER_HAS_IMAGE_FACET_INT],
                Seller::SOLR_FIELD_SELLER_OFFERS_COUNT => $document[Seller::SOLR_FIELD_SELLER_OFFERS_COUNT],
                Seller::SOLR_FIELD_SELLER_PARENT => $document[Seller::SOLR_FIELD_SELLER_PARENT],
                Seller::SOLR_FIELD_SELLER_CATEGORY => $document[Seller::SOLR_FIELD_SELLER_CATEGORY],
                Seller::SOLR_FIELD_SELLER_URL => $document[Seller::SOLR_FIELD_SELLER_URL],
            ];
            $results[] = $transformed;
        }

        return $results;
    }
}
