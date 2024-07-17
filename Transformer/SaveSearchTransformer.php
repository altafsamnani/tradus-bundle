<?php

namespace TradusBundle\Transformer;

use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Intl\Intl;
use TradusBundle\Entity\SearchAlert;
use TradusBundle\Repository\SearchAlertRepository;
use TradusBundle\Service\Helper\OfferServiceHelper;
use TradusBundle\Service\Search\SearchService;
use TradusBundle\Service\Sitecode\SitecodeService;

/**
 * Class SaveSearchTransformer.
 */
class SaveSearchTransformer
{
    /** @var searchAlert */
    private $searchAlert;

    /** @var string */
    private $locale;

    /** @var EntityManager */
    private $entityManager;

    protected $sitecodeId;

    /**
     * SaveSearchTransformer constructor.
     *
     * @param int $userId
     * @param EntityManager $entityManager
     * @param int $sitecodeId
     * @param string $locale
     */
    public function __construct(
        int $userId,
        EntityManager $entityManager,
        ?int $sitecodeId = null,
        ?string $locale = null
    ) {
        /** @var SearchAlertRepository $searchAlertRepository */
        $searchAlertRepository = $entityManager->getRepository('TradusBundle:SearchAlert');
        $searchAlert = $searchAlertRepository->getSearchAlertsByUser($userId, $sitecodeId);
        $this->searchAlert = $searchAlert;
        $this->entityManager = $entityManager;
        $sitecodeService = new SitecodeService();
        $this->locale = $locale ?? $sitecodeService->getDefaultLocale();
        $this->sitecodeId = $sitecodeId ?? $sitecodeService->getSitecodeId();
    }

    /**
     * Please kill me know
     * If you care about your life, do not read the code below
     * If we add more filters...good luck with that :).
     *
     * @param $translator
     * @return array
     */
    public function transform($translator): array
    {
        $translator->setLocale($this->locale);
        $saved_search = $this->searchAlert;
        $response = [];
        $localizedMake = OfferServiceHelper::localizedMake($this->locale);
        $localizedLocation = OfferServiceHelper::localizedLocation($this->locale);
        $slugify = new Slugify();
        /** @var SearchAlert $result */
        foreach ($saved_search as $result) {
            $description = [];
            // Categories.
            $category = $result->getCategory();
            $category_id = $category->getId();
            $category_name = $category->getNameTranslation($this->locale);
            $categories = $category ? $category->getCatsArray($this->locale) : [];
            $key = array_search($category_id, array_column($categories, 'id'));
            $urllink = $categories[$key]['url'];

            //countries
            $countries = $result->getCountries();
            $countries_array = explode(',', $countries);
            $country_array_names = [];
            foreach ($countries_array as $country) {
                $countryName = Intl::getRegionBundle()->getCountryName($country, $this->locale);
                $slug_country_name = $slugify->slugify($countryName);
                array_push($country_array_names, $slug_country_name);
            }

            $countrynames = implode(',', $country_array_names);

            $checksubcategories = '';
            if ($result->getMakes()) {
                $urllink = $urllink.$localizedMake.str_replace(',', '+', $result->getMakes());
                $checksubcategories = $result->getMakes().'+';

                $description['Make'] = preg_replace_callback('/[^, ]*/', function ($m) {
                    return ucfirst($m[0]);
                }, str_replace(',', ', ', $result->getMakes()));
            }

            if ($result->getPriceType()) {
                if (! empty($checksubcategories)) {
                    $urllink .= '/';
                }
                $urllink = $urllink.OfferServiceHelper::localizedPriceType($this->locale)
                    .str_replace(',', '+', $result->getPriceType());
                $checksubcategories = $result->getPriceType().'+';

                $priceTypes = preg_replace_callback('/[^, ]*/', function ($m) use ($translator) {
                    $priceType = str_replace('-', ' ', $m[0]);

                    return $translator->trans(ucwords($priceType));
                }, $result->getPriceType());
                $description['Price type'] = str_replace(',', ', ', $priceTypes);
            }

            if ($result->getPriceRating()) {
                if (! empty($checksubcategories)) {
                    $urllink .= '/';
                }
                $priceRatingSlug = $translator->trans('pricerating').'-';
                $urllink = $urllink.$priceRatingSlug.str_replace(',', '+', $result->getPriceRating());
                $checksubcategories = $result->getPriceRating().'+';

                $priceRatings = preg_replace_callback('/[^, ]*/', function ($m) use ($translator) {
                    $priceRating = str_replace('-', ' ', $m[0]);

                    return $translator->trans(ucfirst($priceRating));
                }, $result->getPriceRating());
                $description['Price rating'] = str_replace(',', ', ', $priceRatings);
            }

            if ($result->getTransmission()) {
                if (! empty($checksubcategories)) {
                    $urllink .= '/';
                }
                $urllink = $urllink.OfferServiceHelper::localizedTransmission($this->locale)
                    .str_replace(',', '+', $result->getTransmission());
                $checksubcategories = $result->getTransmission().'+';

                $transmissions = preg_replace_callback('/[^, ]*/', function ($m) use ($translator) {
                    $transmission = str_replace('-', ' ', $m[0]);

                    return $translator->trans(ucwords($transmission));
                }, $result->getTransmission());
                $description['Transmission'] = str_replace(',', ', ', $transmissions);
            }

            if ($countrynames) {
                if (! empty($checksubcategories)) {
                    $urllink .= '/';
                }
                $urllink = $urllink.$localizedLocation.str_replace(',', '+', $countrynames);
                $checksubcategories = $checksubcategories.$countrynames.'+';
                $cnames = str_replace([',', '-'], [', ', ' '], $countrynames);
                $description['Country'] = preg_replace_callback('/[^, ]*/', function ($m) {
                    return ucfirst($m[0]);
                }, $cnames);
            }

            $urllink = $urllink.'/?';
            if ($result->getQueryString()) {
                $urllink = $urllink.'query='.str_replace(' ', '+', $result->getQueryString()).'&';
                $description['Query'] = $result->getQueryString();
            }
            $year = '';
            if ($result->getYearFrom()) {
                $urllink = $urllink.'year_from='.$result->getYearFrom().'&';
                $year = $result->getYearFrom().'-';
                $description['Year'] = ' > '.$result->getYearFrom();
            }

            if ($result->getYearTo()) {
                $urllink = $urllink.'year_to='.$result->getYearTo().'&';
                $year = $year.$result->getYearTo();
                $description['Year'] = ' < '.$result->getYearTo();
            }

            if ($result->getYearFrom() && $result->getYearTo()) {
                $description['Year'] = $result->getYearFrom().' - '.$result->getYearTo();
            }

            $price = '';
            if ($result->getPriceFrom()) {
                $urllink = $urllink.'price_from='.$result->getPriceFrom().'&';
                $price = $result->getPriceFrom().'-';
                $description['Price'] = ' > '.$result->getPriceFrom();
            }

            if ($result->getPriceTo()) {
                $urllink = $urllink.'price_to='.$result->getPriceTo().'&';
                $price = $price.$result->getPriceTo();
                $description['Price'] = ' < '.$result->getPriceTo();
            }

            if ($result->getPriceFrom() && $result->getPriceTo()) {
                $description['Price'] = $result->getPriceFrom().' - '.$result->getPriceTo();
            }

            /** Add weights */
            $weight = '';
            if ($result->getWeightFrom()) {
                $urllink = $urllink.'weight_from='.$result->getWeightFrom().'&';
                $weight = $result->getWeightFrom().'-';
                $description['Weight'] = ' > '.$result->getWeightFrom();
            }

            if ($result->getWeightTo()) {
                $urllink = $urllink.'weight_to='.$result->getWeightTo().'&';
                $weight = $weight.$result->getWeightTo();
                $description['Weight'] = ' < '.$result->getWeightTo();
            }

            if ($result->getWeightFrom() && $result->getWeightTo()) {
                $description['Weight'] = $result->getWeightFrom().' - '.$result->getWeightTo();
            }

            /** Add Mileage */
            $mileage = '';
            if ($result->getMileageFrom()) {
                $urllink = $urllink.'mileage_from='.$result->getMileageFrom().'&';
                $mileage = $result->getMileageFrom().'-';
                $description['Mileage'] = ' > '.$result->getMileageFrom();
            }

            if ($result->getMileageTo()) {
                $urllink = $urllink.'mileage_to='.$result->getMileageTo().'&';
                $mileage = $mileage.$result->getMileageTo();
                $description['Mileage'] = ' < '.$result->getMileageTo();
            }

            if ($result->getMileageFrom() && $result->getMileageTo()) {
                $description['Mileage'] = $result->getMileageFrom().' - '.$result->getMileageTo();
            }

            // Please forgive me for the code added on this story
            if ($result->getSortBy()) {
                $urllink = $urllink.'sort='.$result->getSortBy().'&';
                $checksubcategories = $checksubcategories.$result->getSortBy().'+';
                $sortReplace = str_replace(
                    SearchService::REQUEST_VALUE_SORT_RELEVANCY,
                    SearchService::REQUEST_VALUE_SORT_RELEVANCY_LABEL,
                    $result->getSortBy()
                );
                $sort = ucfirst(str_replace(['-', 'asc', 'desc'], [' ', '↑', '↓'], $sortReplace));
                $description['Sort by'] = $translator->trans($sort);
            }

            if ($year) {
                $checksubcategories = $checksubcategories.$year.'+';
            }

            if ($price) {
                $checksubcategories = $checksubcategories.$price.'+';
            }

            if ($weight) {
                $checksubcategories = $checksubcategories.$weight.'+';
            }

            if ($mileage) {
                $checksubcategories = $checksubcategories.$mileage.'+';
            }

            $urllink = rtrim($urllink, '/?');

            $response[] = [
                'id' => $result->getId(),
                'category' => $category_name,
                'category_id' => $category_id,
                'categories' => $categories,
                'status' => $result->getStatus(),
                'makes' => $result->getMakes(),
                'countries_id' => $countries,
                'countryname' => $countrynames,
                'query_string' => $result->getQueryString(),
                'sort_by' => $result->getSortBy(),
                'price_from' => $result->getPriceFrom(),
                'price_to' => $result->getPriceTo(),
                'weight_from' => $result->getWeightFrom(),
                'weight_to' => $result->getWeightTo(),
                'mileage_from' => $result->getMileageFrom(),
                'mileage_to' => $result->getMileageTo(),
                'year_from' => $result->getYearFrom(),
                'year_to' => $result->getYearTo(),
                'price_type' => $result->getPriceType(),
                'price_rating' => $result->getPriceRating(),
                'created_at' => $result->getCreatedAt(),
                'updated_at' => $result->getUpdatedAt(),
                'urllink' => rtrim($urllink, '&'),
                'subcategories' => rtrim($checksubcategories, '+'),
                'description' => $description,
            ];
        }

        return $response;
    }
}
