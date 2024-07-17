<?php

namespace TradusBundle\Service\Favorites;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\Translator;
use TradusBundle\Entity\Offer;
use TradusBundle\Entity\OfferInterface;
use TradusBundle\Entity\TradusUser;
use TradusBundle\Mailer\TradusMailer;
use TradusBundle\Repository\SimilarOfferAlertRepository;
use TradusBundle\Service\Alerts\Rules\ConfigRuleMatchingOffer;
use TradusBundle\Service\Search\SearchService;
use TradusBundle\Service\Sitecode\SitecodeService;
use TradusBundle\Service\Utils\CurrencyService;

/**
 * Class FavoritesService.
 */
class FavoritesService
{
    /*
     * @var EntityManager
     */
    protected $entityManager;

    /*
     * @var TradusUser
     */
    protected $user;

    /*
     * @var Offer
     */
    protected $offer;

    /*
     * @var TradusMailer
     */
    protected $mailer;

    /** @var ConfigRuleMatchingOffer */
    protected $config;

    /** @var Search */
    protected $search;

    /** @var Translator */
    protected $translator;

    /** @var string */
    protected $locale;

    /** @var array */
    protected $relatedResult;

    /** @var $container */
    protected $container;

    /**
     * FavouritesService constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        /* @var ConfigService $config */
        $this->config = new ConfigRuleMatchingOffer();

        global $kernel;
        $this->container = $kernel->getContainer();
        $this->mailer = $this->container->get('tradus.mailer');
        $this->search = $this->container->get('tradus.search');
        $this->translator = $this->container->get('translator');
    }

    /**
     * @param int $userId
     * @return $this
     */
    public function setUser(int $userId)
    {
        $this->user = $this->entityManager->getRepository('TradusBundle:TradusUser')->findOneBy(
            [
                'id' => $userId,
                'status' => TradusUser::STATUS_ALLOWED,
            ]
        );

        return $this;
    }

    /**
     * @return TradusUser $user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param int $offerId
     * @return $this
     */
    public function setOffer(int $offerId)
    {
        $this->offer = $this->entityManager->getRepository('TradusBundle:Offer')->findOneBy(
            [
                'id' => $offerId,
            ]
        );

        return $this;
    }

    /**
     * @return Offer $offer
     */
    public function getOffer()
    {
        return $this->offer;
    }

    /**
     * @send emails
     * @return void
     */
    public function sendSimilarOfferEmail()
    {
        $this->response = new Rules\FavoriteRuleResponse($this->entityManager);

        /* set general data */
        $this->setGeneralData();

        /* set related offers */
        $this->getRelatedOffers(
            $this->getOffer()->getMake()->getId(),
            $this->getOffer()->getCategory()->getId()
        );

        /* set user data */
        $this->setUserData();

        /* set offer data */
        $this->setOfferData();

        /* send email to buyer */
        $this->mailer->sendSimilarOffersFavoriteEmail($this->response);
    }

    /**
     * @set email general data
     * @return void
     */
    private function setGeneralData()
    {
        $this->locale = ($this->getUser()->getPreferredLocale() ?: CurrencyService::LANGUAGE_ENGLISH);
        $this->response->setLocale($this->locale);
        $this->translator->setLocale($this->locale);

        /* email subject */
        $this->response->setData(
            Rules\FavoriteRuleResponse::DATA_EMAIL_SUBJECT,
            $this->translator->trans('An offer you have marked as favorite has been removed')
        );
    }

    /**
     * @get related offers
     * @param int $make
     * @param int $catId
     * @return void
     */
    private function getRelatedOffers(int $make, int $catId)
    {
        // Related offers
        $request = new Request();
        $relatedParamsArr = [
            SearchService::REQUEST_FIELD_PAGE => 1,
            SearchService::REQUEST_FIELD_LIMIT => 50,
            SearchService::REQUEST_FIELD_HAS_IMAGE_COUNT => 1,
            SearchService::REQUEST_FIELD_SORT => SearchService::REQUEST_VALUE_SORT_RELEVANCY,
            SearchService::REQUEST_FIELD_MAKE => $make,
            SearchService::REQUEST_FIELD_CAT_L1 => $catId,
        ];

        $request->query = new ParameterBag($relatedParamsArr);
        $query = $this->search->getQuerySelect();
        $query = $this->search->createQueryFromRequest($query, $request);
        $relatedResult = $this->search->execute($query);
        $relatedResult->shuffleDocuments()
            ->boostSellerTypesDocuments($this->config->getFilterFreeSellers())
            ->limitDocuments($this->config->getFilterLimit());

        /* set search url and result */
        $this->response->setRelatedSearchResult($relatedResult);
        $this->response->setRelatedSearchUrl($this->search->getSearchUrlFull($this->locale));
    }

    /**
     * @set buyer's data
     * @return void
     */
    private function setUserData()
    {
        $this->response->setData(
            Rules\FavoriteRuleResponse::DATA_EMAIL_FROM,
            $this->container->getParameter('sitecode')['emails']['alert_error_email']
        );
        $this->response->setData(Rules\FavoriteRuleResponse::DATA_EMAIL_TO, $this->getUser()->getEmail());
        $userName = ! empty($this->getUser()->getFullName()) ?
            $this->getUser()->getFullName() :
            $this->translator->trans($this->container->getParameter('sitecode')['site_title'].' user');
        $this->response->setData(Rules\FavoriteRuleResponse::DATA_USER_FIRST_NAME, $userName);
        $this->response->setData(Rules\FavoriteRuleResponse::DATA_USER_ID, $this->getUser()->getId());
    }

    /**
     * @set removed/sold offer data
     * @return void
     */
    public function setOfferData()
    {
        // Create hash for similar alerts and add it to object sent to email template
        $offerData['offer_title'] = $this->getOfferName();
        $offerData['show_send_similar'] = ! empty($this->checkAlert()) ? false : true;
        $offerData['offer_url'] = '/en/offer/'.$this->getOffer()->getId();
        $offerData['offer_id'] = $this->getOffer()->getId();
        $offerData['similar_alerts_hash'] = base64_encode(
            json_encode(
                [
                    'offer_id' => $this->getOffer()->getId(),
                    'user_id' => $this->response->getData(Rules\FavoriteRuleResponse::DATA_USER_ID),
                ]
            )
        );

        /* offer image */
        $offerData['image'] = $this->entityManager->getRepository('TradusBundle:Offer')
            ->getDefaultImage($this->getOffer());

        /* set seller data */
        $offerData['seller'] = $this->getOffer()->getSeller();

        $this->response->setOfferData($offerData);
    }

    /**
     * @offer title from description
     * @return string
     */
    private function getOfferName()
    {
        $title = $this->offer->getTitleByLocale($this->locale);
        if (empty($title)) {
            $titleParts = [];
            // make name
            $make = $this->entityManager->getRepository('TradusBundle:Make')
                ->findOneBy(['id' => $this->offer->getMake()->getId()]);
            $titleParts[] = $make->getName();
            // category name
            $category = $this->entityManager->getRepository('TradusBundle:Category')
                ->findOneBy(['id' => $this->offer->getCategory()->getId()]);
            $titleParts[] = $category->getNameTranslation($this->locale);
            // construction year
            if ($this->offer->getAttributes()) {
                foreach ($this->offer->getAttributes() as $offerAtt) {
                    if ($offerAtt->getAttribute()->getName() == OfferInterface::FIELD_CONSTRUCTION_YEAR) {
                        $construction_year = $offerAtt->getContent();
                        if (! empty($construction_year)) {
                            $titleParts[] = $construction_year;
                        }
                        break;
                    }
                }
            }
            $title = ! empty($titleParts) ? implode('-', $titleParts) : '';
        }

        return $title;
    }

    /**
     * @to check alert exist with this offer
     * @return mixed
     * @throws NonUniqueResultException
     */
    private function checkAlert()
    {
        $sitecodeService = new SitecodeService();
        $sitecodeId = $sitecodeService->getSitecodeId();
        /** @var SimilarOfferAlertRepository $similarAlertRepo */
        $similarAlertRepo = $this->entityManager->getRepository('TradusBundle:SimilarOfferAlert');

        return $similarAlertRepo->checkAlertExist($this->getUser()->getId(), $this->getOffer()->getId(), $sitecodeId);
    }
}
