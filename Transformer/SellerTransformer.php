<?php

namespace TradusBundle\Transformer;

use TradusBundle\Entity\Seller;
use TradusBundle\Entity\SellerInterface;
use TradusBundle\Entity\SellerOptionInterface;
use TradusBundle\Entity\SellerPreferenceInterface;
use TradusBundle\Repository\SellerRepository;
use TradusBundle\Service\Sitecode\SitecodeService;

/**
 * Class SellerTransformer.
 */
class SellerTransformer
{
    /** @var Seller */
    private $seller;

    /** @var int */
    private $sitecodeId;

    /**
     * SellerTransformer constructor.
     *
     * @param Seller $seller
     */
    public function __construct(Seller $seller)
    {
        $this->seller = $seller;
        $ssc = new SitecodeService();
        $this->sitecodeId = $ssc->getSitecodeId();
    }

    /**
     * @return array
     */
    public function transform($locale = null): array
    {
        $preference = $this->seller->getPreference();
        $langOptions = $preference ? $preference->getLanguageOptions() : [];

        return [
            SellerInterface::FIELD_ID => $this->seller->getId(),
            SellerInterface::FIELD_USER_ID => $this->seller->getUserId(),
            SellerInterface::FIELD_EMAIL => $this->seller->getSellerContactEmail(),
            SellerInterface::FIELD_NAME => $this->seller->getName(),
            SellerInterface::FIELD_LOCALE => $this->seller->getLocale(),
            SellerInterface::FIELD_SLUG => $this->seller->getSlug(),
            SellerInterface::FIELD_URL => $this->seller->getSellerUrl($locale),
            SellerInterface::FIELD_ADDRESS => $this->seller->getAddress(),
            SellerInterface::FIELD_CITY => $this->seller->getCity(),
            SellerInterface::FIELD_COUNTRY => $this->seller->getCountry(),
            SellerInterface::FIELD_COMPANY_NAME => $this->seller->getCompanyName(),
            SellerInterface::FIELD_LOGO => $this->seller->getLogo(),
            SellerInterface::FIELD_PHONE => $this->seller->getSellerContactPhone(),
            SellerInterface::FIELD_MOBILE_PHONE => $this->seller->getMobilePhone(),
            SellerInterface::FIELD_STATUS => $this->seller->getStatus(),
            SellerInterface::FIELD_TYPE => $this->seller->getSellerType(),
            SellerInterface::FIELD_CREATED_AT => $this->seller->getCreatedAt()->format('Y-m-d'),
            SellerInterface::FIELD_UPDATED_AT => $this->seller->getUpdatedAt()->format('Y-m-d'),
            SellerInterface::FIELD_WHATSAPP_ENABLED => $this->seller->getWhatsappEnabled(),
            SellerInterface::FIELD_GEO_LOCATION => $this->seller->getGeoLocation(),
            SellerInterface::FIELD_GEO_LOCATION_OBJECT => $this->seller->getGeoLocationObject(),
            SellerInterface::FIELD_ANALYTICS_API_TOKEN => $this->seller->getAnalyticsApiToken(),
            SellerInterface::FIELD_ROLES => $this->seller->getRoles(),
            SellerInterface::FIELD_PASSWORD => $this->seller->getPassword(),
            SellerInterface::FIELD_SINCE => $this->seller->getSince(),
            SellerOptionInterface::FIELD_OPTIONS => $this->seller->getOptionValues(),
            SellerPreferenceInterface::FIELD_PREFERENCES => [SellerInterface::FIELD_PREFERENCE_LANGUAGE_OPTIONS => $langOptions],
            SellerInterface::FIELD_WEBSITE => $this->seller->getWebsite(),
            SellerInterface::FIELD_ADDITIONAL_SERVICES => $this->seller->getServicesIds(),
            SellerInterface::FIELD_BADGE_REPLY_FAST => $this->seller->getBadgeReplyFast(),
            SellerInterface::FIELD_BADGE_REPLY_FAST_LABEL => $this->seller->getLabelReplyFast(),
            SellerInterface::FIELD_SOURCE => $this->seller->getSource(),
            SellerInterface::FIELD_TESTUSER_FLAG => $this->seller->getTestuser(),
            SellerInterface::FIELD_ANONYMIZED_AT => $this->seller->getAnonymizedAt(),
            SellerInterface::FIELD_CHILD_SELLERS => $this->seller->getChildSellers(),
            SellerInterface::FIELD_PARENT_SELLER_ID => $this->seller->getParentSellerId(),
            SellerInterface::FIELD_POINT_OF_CONTACT => $this->seller->getPointOfContact(),
            SellerInterface::FIELD_SOLR_STATUS => $this->seller->getSolrStatus(),
            SellerInterface::FIELD_OFFERS_COUNT => SellerRepository::getTotalOffersBySellerInSitecode($this->seller->getId(), $this->sitecodeId),
        ];
    }
}
