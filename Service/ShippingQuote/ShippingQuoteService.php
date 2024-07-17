<?php

namespace TradusBundle\Service\ShippingQuote;

use Doctrine\ORM\EntityManagerInterface;
use DomDocument;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Translation\Translator;
use TradusBundle\Entity\Email;
use TradusBundle\Service\Config\ConfigServiceInterface;

/**
 * Class ShippingQuoteService.
 */
class ShippingQuoteService
{
    /** @var Translator */
    protected $translator;

    protected $defaultLocale;

    /**
     * @var resource
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        global $kernel;
        $this->translator = $kernel->getContainer()->get('translator');
        $this->defaultLocale = $kernel->getContainer()->getParameter(ConfigServiceInterface::DEFAULT_LOCALE_CONFIG);
    }

    /**
     * @param Email $emailEntity
     */
    public function getShippingQuote(Email $emailEntity)
    {
        $arr = $xmlArr = [];
        /* offer information */
        $offer = $emailEntity->getOffer();
        $arr['offer_id'] = $offer->getId();
        /* Get user information */
        $tradusUser = $this->entityManager
            ->getRepository('TradusBundle:TradusUser')->findOneBy(['email' => $emailEntity->getEmailFrom()]);

        if (! $tradusUser) {
            return [];
        }

        $this->translator->setLocale($tradusUser->getPreferredLocale() ?? $this->defaultLocale);
        $arr['from'] = $tradusUser->getEmail();
        $arr['to'] = $emailEntity->getEmailTo();
        $arr['user_id'] = $tradusUser->getId();

        $xmlArr['title'] = $offer->getTitleByLocale();
        $xmlArr['url'] = 'https://www.tradus.com'.$offer->getUrlByLocale();

        $userName = (
            $tradusUser->getFullName() != '' ? $tradusUser->getFullName() :
                $this->translator->trans($this->container->getParameter('sitecode')['site_title'].' user')
        );
        $xmlArr['name'] = $userName;

        $xmlArr['email'] = $tradusUser->getEmail();
        $xmlArr['phone_number'] = $tradusUser->getPhone();

        /* Get shipping quote information */
        if (! $emailEntity->getReferenceId()) {
            return [];
        }

        $offer_shipping = $this->entityManager
            ->getRepository('TradusBundle:OfferShipping')->find($emailEntity->getReferenceId());

        if (! $offer_shipping) {
            return [];
        }

        $xmlArr['from_location'] = [
            'country' => Intl::getRegionBundle()->getCountryName($offer_shipping->getFromCountryIso()),
            'city' => $offer_shipping->getFromCity(),
        ];
        $xmlArr['to_location'] = [
            'country' => Intl::getRegionBundle()->getCountryName($offer_shipping->getDestinationCountryIso()),
            'city'    => $offer_shipping->getDestinationCity(),
        ];
        $xmlArr['vehicle_info'] = ['vehicle_type' => $offer_shipping->getTwVehicleType()];
        /* Get offer specification information */
        $spec_array = ['width', 'height', 'length', 'weight'];
        if ($offer->getAttributes()) {
            foreach ($offer->getAttributes() as $offer_attribute) {
                $attribute_name = $offer_attribute->getAttribute()->getName();
                if (in_array($attribute_name, $spec_array)) {
                    if ($attribute_name != 'weight') {
                        $xmlArr['vehicle_info'][$attribute_name] =
                            number_format($offer_attribute->getContent(), 2, '', '');
                    } else {
                        $xmlArr['vehicle_info'][$attribute_name] = $offer_attribute->getContent();
                    }
                }
            }
        }

        $xmlArr['cost'] = '€'.number_format($offer_shipping->getTotal(), 2);
        $arr['name'] = $xmlArr['name'];
        $arr['xml_data'] = $this->createXml($xmlArr);

        return $arr;
    }

    /**
     * @param array $data
     */
    public function createXml($data)
    {
        $xml = new DomDocument('1.0', 'utf-8');
        $contactElem = $xml->createElement('contact');
        $companyElem = $xml->createElement('company_source', 'TRADUS.COM');
        $contactElem->appendChild($companyElem);
        $calculationElem = $xml->createElement('calculation');
        $contactElem->appendChild($calculationElem);
        foreach ($data as $key => $value) {
            /* buyer information */
            if (! is_array($value)) {
                $userElem = $xml->createElement($key, $value);
                $calculationElem->appendChild($userElem);
            } else {
                /* vehicle information */
                $dataElem = $xml->createElement($key);
                foreach ($value as $key2 => $value2) {
                    $value2 = (! empty($value2) ? $value2 : ' ');
                    $inforElem = $xml->createElement($key2, $value2);
                    $dataElem->appendChild($inforElem);
                }
                $calculationElem->appendChild($dataElem);
            }
        }
        $xml->appendChild($contactElem);
        $xml->formatOutput = true;

        return $xml->saveXML();
    }
}
