<?php

namespace TradusBundle\Service\Search;

use TradusBundle\Entity\Category;
use TradusBundle\Entity\PriceType;
use TradusBundle\Entity\Sitecodes;
use TradusBundle\Service\Sitecode\SitecodeService;

/**
 * Class SearchSeoService.
 */
class SearchSeoService
{
    public const TOP_MAKES_LENGTH = 4;
    public const TOP_MODELS_LENGTH = 4;
    public const TOP_VERSIONS_LENGTH = 4;
    public const TOP_COUNTRIES_LENGTH = 3;
    public const TOP_REGIONS_LENGTH = 3;
    public const TOP_CATEGORIES_LENGTH = 4;
    public const TOP_SELLERS_LENGTH = 3;
    public const ELIGIBLE_PRICE_TYPES = [PriceType::UPON_REQUEST, PriceType::AUCTION, PriceType::RENT];
    public const ELIGIBLE_PRICE_TYPE_CONTENTS = [
        PriceType::UPON_REQUEST => 'priced on request',
        PriceType::AUCTION => 'for auction',
        PriceType::RENT => 'for rent',
    ];

    /** @var array $searchResult */
    private $searchResult = [];

    private $resultCount = 0;

    private $sellersCount = 0;

    private $countriesCount = 0;

    private $regionsCount = 0;

    private $locationsCount = 0;

    private $selectedMake = [];

    private $selectedModel = '';

    private $selectedVersion = '';

    private $selectedCategory = '';

    private $selectedCatL1 = '';

    private $selectedCatL2 = '';

    private $selectedCatL3 = '';

    private $selectedCountry = [];

    private $selectedRegion = [];

    private $selectedLocation = [];

    private $selectedPriceType = '';

    private $topMakes = [];

    private $topModels = [];

    private $topVersions = [];

    private $topCategories = [];

    private $topCountries = [];

    private $topRegions = [];

    private $topLocations = [];

    private $topSellers = [];

    private $topPriceTypes = [];

    private $priceRange = [];

    private $translator;

    private $sitecodeService;

    private $sitecodeKey;

    private $currency = 'EUR';

    private $locale = 'EN';

    private $categoryAliases = [];

    /**
     * SearchSeoService constructor.
     * @param array $searchResult
     * @param string $locale
     * @param string $currency
     */
    public function __construct(array $searchResult, string $locale, string $currency, $categoryAliases = '')
    {
        global $kernel;
        $this->translator = $kernel->getContainer()->get('translator');
        $this->translator->setLocale($locale);
        $this->searchResult = $searchResult;
        $this->currency = $currency;
        $this->locale = $locale;
        $this->sitecodeService = new SitecodeService();
        $this->sitecodeKey = $this->sitecodeService->getSitecodeKey();
        $this->categoryAliases = $categoryAliases;
        $this->setSearchFiltersData();
    }

    public function getSeoContents()
    {
        if (empty($this->selectedCatL3) || $this->sitecodeKey == Sitecodes::SITECODE_KEY_AUTOTRADER) {
            return [];
        }

        return [
            'headerText' => ! empty($this->getSelectedMake()) ? $this->getForSaleTodayText() : [],
            'footerText' => ! empty($this->getSelectedMake()) ? array_filter([
                $this->getExpectAPriceWithFilterText(),
                $this->getFilterWithPriceTypeText(),
                $this->getSelectModelText(),
                $this->getSelectVersionText(),
                $this->getContactSellerInLocationText(),
            ]) : [],
            'metatitle' => $this->sitecodeKey == Sitecodes::SITECODE_KEY_TRADUS ? $this->getSeoTitle() : '',
            'metadescription' => $this->sitecodeKey == Sitecodes::SITECODE_KEY_TRADUS ? $this->getSeoDescription() : '',
        ];
    }

    public function getResultCount()
    {
        return $this->resultCount = $this->searchResult['resultCount'];
    }

    public function getSellersCount()
    {
        if ($this->sellersCount) {
            return $this->sellersCount;
        }

        return $this->sellersCount = ! empty($this->searchResult['dynamicSeoContent']['seller']['sellersCount']) ? $this->searchResult['dynamicSeoContent']['seller']['sellersCount'] : 0;
    }

    public function getCountriesCount()
    {
        if ($this->countriesCount) {
            return $this->countriesCount;
        }

        return $this->countriesCount = ! empty($this->searchResult['facets']['country']['items']) ? count($this->searchResult['facets']['country']['items']) : 0;
    }

    public function getRegionsCount()
    {
        if ($this->regionsCount) {
            return $this->regionsCount;
        }

        return $this->regionsCount = ! empty($this->searchResult['facets']['region']['items']) ? count($this->searchResult['facets']['region']['items']) : 0;
    }

    public function getLocationsCount()
    {
        if (empty($this->locationsCount)) {
            $this->setLocationData();
        }

        return $this->locationsCount;
    }

    public function getMakeModelVersionCategory()
    {
        $makeText = $this->getSelectedMakeText();
        $model = $this->getSelectedModel();
        $version = $this->getSelectedVersion();
        $category = $this->getSelectedCategory();

        if (strtoupper($this->locale) == 'DE') {
            $category = ucfirst($category);
        }

        return [
            'text' => $makeText['text'].' @model @version @category',
            'placeholder' => [
                '@model' => $model,
                '@version' => $version,
                '@category' => $category,
            ] + $makeText['placeholder'],
        ];
    }

    public function getPriceRangeData()
    {
        if (! empty($this->searchResult['dynamicSeoContent']['price']['max']) && empty($this->priceRange)) {
            $this->priceRange = [
                '@price1' => [
                    'price' => $this->searchResult['dynamicSeoContent']['price']['min'],
                    'data_price' => $this->searchResult['dynamicSeoContent']['price']['min_data_price'],
                ],
                '@price2' => [
                    'price' => $this->searchResult['dynamicSeoContent']['price']['max'],
                    'data_price' => $this->searchResult['dynamicSeoContent']['price']['max_data_price'],
                ],
            ];
        }

        return $this->priceRange;
    }

    public function getTopVersions()
    {
        if (empty($this->topVersions)) {
            $this->setVersionData();
        }

        return $this->topVersions;
    }

    public function getTopModels()
    {
        if (empty($this->topModels)) {
            $this->setModelData();
        }

        return $this->topModels;
    }

    public function getTopMakes()
    {
        if (empty($this->topMakes)) {
            $this->setMakeData();
        }

        return $this->topMakes;
    }

    public function getTopCategories()
    {
        if (empty($this->topCategories)) {
            $this->setCategoryData();
        }

        return $this->topCategories;
    }

    public function getTopCountries()
    {
        if (empty($this->topCountries)) {
            $this->setCountryData();
        }

        return $this->topCountries;
    }

    public function getTopRegions()
    {
        if (empty($this->topRegions)) {
            $this->setRegionData();
        }

        return $this->topRegions;
    }

    public function getTopLocations()
    {
        if ($this->topLocations) {
            return $this->topLocations;
        }

        $this->setLocationData();

        return $this->topLocations;
    }

    public function getTopSellers()
    {
        if ($this->topSellers) {
            return $this->topSellers;
        }
        if (! empty($this->searchResult['dynamicSeoContent']['seller']['topSellers'])) {
            $this->topSellers = [];
            foreach ($this->searchResult['dynamicSeoContent']['seller']['topSellers'] as $seller) {
                $this->topSellers[] = $this->getItemWithLink($seller);
            }

            if (count($this->topSellers) > self::TOP_SELLERS_LENGTH) {
                $this->topSellers = array_slice($this->topSellers, 0, self::TOP_SELLERS_LENGTH);
            }
        }

        return $this->topSellers;
    }

    public function getForSaleTodayText()
    {
        $datePlaceholder['@month'] = $this->translator->trans(date('F'));
        $datePlaceholder['@date'] = date('d');
        $datePlaceholder['@year'] = date('Y');
        $today = $this->translator->trans('@date @month @year', $datePlaceholder);
        $makeModelCategory = $this->getMakeModelVersionCategory();
        $location = $this->getSelectedLocation();
        $text = 'Find @count used '.$makeModelCategory['text'].' for sale today (@date)';
        $placeholder = ['@count' => $this->getResultCount(), '@date' => $today] + $makeModelCategory['placeholder'];
        if (! empty($location)) {
            $selectedLocation = $this->getSelectedLocationText();
            $text = 'Find @count used '.$makeModelCategory['text'].' for sale in '.$selectedLocation['text'].' today (@date)';
            $placeholder += $selectedLocation['placeholder'];
        }
        if (empty($this->getSelectedMake()) && ! empty($this->getTopMakes())) {
            $topMakesData = $this->getReadableItems($this->getTopMakes(), '@topmakes');
            if (! empty($topMakesData['text'])) {
                $text .= ', featuring manufacturers like '.$topMakesData['text'];
                $placeholder += $topMakesData['placeholder'];
            }
        }

        return [
            'text' => $text.'.',
            'placeholder' => $placeholder,
        ];
    }

    public function getSeoDescription()
    {
        $sitename = $this->sitecodeService->getSitecodeTitle();
        $makeModelCategory = $this->getMakeModelVersionCategory();
        $altCategory = '';
        $text = 'Find @count used '.$makeModelCategory['text'].' for sale';
        $placeholder = ['@count' => $this->getResultCount()] + $makeModelCategory['placeholder'];

        if (! empty($this->getSelectedLocation())) {
            $selectedLocation = $this->getSelectedLocationText();
            $text .= ' in '.$selectedLocation['text'];
            $placeholder += $selectedLocation['placeholder'];
        } else {
            $text .= ' on @site';
            $placeholder += ['@site' => $sitename];
        }

        if (empty($this->getSelectedMake()) && ! empty($this->getTopMakes())) {
            $topContents = count($this->getTopMakes()) > 3 ? array_slice($this->getTopMakes(), 0, 3) : $this->getTopMakes();
            $topContentsData = $this->getReadableItems($topContents, '@topcontents');
        } elseif (! empty($this->getSelectedMake()) && empty($this->getSelectedModel()) && ! empty($this->getTopModels())) {
            $topContents = count($this->getTopModels()) > 3 ? array_slice($this->getTopModels(), 0, 3) : $this->getTopModels();
            $topContentsData = $this->getReadableItems($topContents, '@topcontents');
        }

        if (! empty($topContentsData['text']) && ! empty($topContentsData['placeholder'])) {
            $text .= ', featuring '.$topContentsData['text'];
            $placeholder += $topContentsData['placeholder'];
        }

        $text .= ' - offered by commercial dealers and private sellers';

        if (! empty($altCategory) && ! empty($placeholder['@category'])) {
            $altPlaceholder = $placeholder;
            $altPlaceholder['@category'] = sprintf('%s / %s', $placeholder['@category'], $altCategory);
            $transDesc = ucfirst($this->pregReplaceText(['\s+' => ' ', '\s+,' => ',', '\s+\.' => '.'], strip_tags($this->translator->trans($text, $altPlaceholder))));
            if (strlen($transDesc) <= 160) {
                return $transDesc;
            }
        }

        return ucfirst($this->pregReplaceText(['\s+' => ' ', '\s+,' => ',', '\s+\.' => '.'], strip_tags($this->translator->trans($text, $placeholder))));
    }

    public function getExpectAPriceText()
    {
        return empty($this->getPriceRangeData()) ? '' : $this->translator->trans('Expect a price between @minprice - @maxprice.', $this->priceRange);
    }

    public function getExpectAPriceWithFilterText()
    {
        $expectAPriceWithFilterText = [];
        $makeModelCategory = $this->getMakeModelVersionCategory();
        if (! empty($this->getPriceRangeData()) && ! empty($makeModelCategory)) {
            $expectAPriceWithFilterText = [
                'text' => 'For '.$makeModelCategory['text'].', expect a price between @price1 - @price2.',
                'placeholder' => ['currency' => $this->currency] + $makeModelCategory['placeholder'] + $this->getPriceRangeData(),
            ];
        }

        return $expectAPriceWithFilterText;
    }

    public function getFilterWithPriceTypeText()
    {
        $filterWithPriceTypeText = [];
        $this->setPriceTypeData();
        $makeModelCategory = $this->getMakeModelVersionCategory();
        $priceTypeData = $this->getReadableItems($this->topPriceTypes, '@pricetypes');
        if (! empty($priceTypeData['text'])) {
            $filterWithPriceTypeText = [
                'text' => 'Also find '.$makeModelCategory['text'].' '.$priceTypeData['text'].'.',
                'placeholder' => $makeModelCategory['placeholder'] + $priceTypeData['placeholder'],
            ];
        }

        return $filterWithPriceTypeText;
    }

    public function getSelectModelText()
    {
        $selectModelText = [];
        if (empty($this->getSelectedModel()) && ! empty($this->getSelectedMake()) && ! empty($this->getTopModels()) && count($this->getTopModels()) > 1) {
            $topModelData = $this->getReadableItems($this->getTopModels(), '@topmodels');
            if (! empty($topModelData['text'])) {
                $selectModelText = [
                    'text' => 'Select the model that interests you most, featuring '.$topModelData['text'].'.',
                    'placeholder' => $topModelData['placeholder'],
                ];
            }
        }

        return $selectModelText;
    }

    public function getSelectVersionText()
    {
        $selectVersionText = [];
        if (empty($this->getSelectedVersion()) && ! empty($this->getSelectedModel()) && ! empty($this->getTopVersions()) && count($this->getTopVersions()) > 1) {
            $topVersionData = $this->getReadableItems($this->getTopVersions(), '@topversions');
            if (! empty($topVersionData['text'])) {
                $selectVersionText = [
                    'text' => 'Select the version that interests you most, featuring '.$topVersionData['text'].'.',
                    'placeholder' => $topVersionData['placeholder'],
                ];
            }
        }

        return $selectVersionText;
    }

    public function getContactSellerInLocationText()
    {
        $contactSellerInLocationText = [];
        if (! empty($this->getLocationsCount()) && ! empty($this->getSellersCount())) {
            $text = 'Contact ';
            $placeholder = ['@sellercount' => $this->getSellersCount()];
            $sellerLabel = $this->getSellersCount() > 1 ? 'sellers' : 'seller';
            if (! empty($this->getSelectedLocation())) {
                $selectedLocation = $this->getSelectedLocationText();
                $topSellerData = $this->getReadableItems($this->getTopSellers(), '@topsellers');
                if ($this->getSellersCount() > 3) {
                    $text .= sprintf('@sellercount %s from '.$selectedLocation['text'].' including: '.$topSellerData['text'], $sellerLabel);
                } else {
                    $text .= sprintf('the following %s from '.$selectedLocation['text'].': '.$topSellerData['text'], $sellerLabel);
                }
                $placeholder += $selectedLocation['placeholder'] + $topSellerData['placeholder'];
            } else {
                $text .= '@sellercount '.$sellerLabel;
                $topLocationData = $this->getReadableItems($this->getTopLocations(), '@locations');
                if ($this->getLocationsCount() > 3) {
                    $text .= ' from @locationcount ';
                    $text .= ($this->sitecodeKey == Sitecodes::SITECODE_KEY_TRADUS) ? 'countries' : 'regions';
                    $placeholder += ['@locationcount' => $this->getLocationsCount()];
                    $text .= ' including '.$topLocationData['text'];
                } else {
                    $text .= ' from '.$topLocationData['text'];
                }
                $placeholder += $topLocationData['placeholder'];
            }

            $contactSellerInLocationText = [
                'text' => $text.'.',
                'placeholder' => $placeholder,
            ];
        }

        return $contactSellerInLocationText;
    }

    public function getShipmentHelpText()
    {
        $shipmentHelpText = [];
        if (! empty($this->getSelectedCountry())) {
            $selectedLocation = $this->getSelectedLocationText();
            $shipmentHelpText1 = 'Need help with equipment transportation to or within '.$selectedLocation['text'].'?';
            $placeholder = [] + $selectedLocation['placeholder'];

            $shipmentHelpText2 = $this->translator->trans(
                'Request a free quote from any offer page.'
            );

            $shipmentHelpText = [
                'text' => sprintf('%s %s', $shipmentHelpText1, $shipmentHelpText2),
                'placeholder' => $placeholder,
            ];
        }

        return $shipmentHelpText;
    }

    public function getSelectedMake()
    {
        if (empty($this->selectedMake)) {
            $this->setMakeData();
        }

        return $this->selectedMake;
    }

    public function getSelectedModel()
    {
        if (empty($this->selectedModel)) {
            $this->setModelData();
        }

        return $this->selectedModel;
    }

    public function getSelectedVersion()
    {
        if (empty($this->selectedVersion)) {
            $this->setVersionData();
        }

        return $this->selectedVersion;
    }

    public function getSelectedCategory()
    {
        if (empty($this->selectedCategory)) {
            $this->setCategoryData();
        }

        return $this->selectedCategory;
    }

    public function getSelectedCountry()
    {
        if (empty($this->selectedCountry)) {
            $this->setCountryData();
        }

        return $this->selectedCountry;
    }

    public function getSelectedRegion()
    {
        if (empty($this->selectedRegion)) {
            $this->setRegionData();
        }

        return $this->selectedRegion;
    }

    public function getSelectedLocation()
    {
        if (empty($this->selectedLocation)) {
            $this->setLocationData();
        }

        return $this->selectedLocation;
    }

    private function setVersionData()
    {
        if (! empty($this->selectedCatL1['id']) && $this->selectedCatL1['id'] == Category::CATEGORY_TRANSPORT_ID && ! empty($this->searchResult['facets']['version']['items'])) {
            $this->topVersions = [];
            foreach ($this->searchResult['facets']['version']['items'] as $version) {
                if ($version['checked'] == true) {
                    $this->selectedVersion = $this->getItemWithLink($version);
                    continue;
                }
                $this->topVersions[] = $this->getItemWithLink($version);
            }

            if (count($this->topVersions) > self::TOP_VERSIONS_LENGTH) {
                $this->topVersions = array_slice($this->topVersions, 0, self::TOP_VERSIONS_LENGTH);
            }
        }
    }

    private function setModelData()
    {
        if (! empty($this->selectedCatL1['id']) && $this->selectedCatL1['id'] == Category::CATEGORY_TRANSPORT_ID && ! empty($this->searchResult['facets']['model']['items'])) {
            $this->topModels = [];
            foreach ($this->searchResult['facets']['model']['items'] as $model) {
                if ($model['checked'] == true) {
                    $this->selectedModel = $this->getItemWithLink($model);
                    continue;
                }
                $this->topModels[] = $this->getItemWithLink($model);
            }

            if (count($this->topModels) > self::TOP_MODELS_LENGTH) {
                $this->topModels = array_slice($this->topModels, 0, self::TOP_MODELS_LENGTH);
            }
        }
    }

    private function setMakeData()
    {
        if (! empty($this->searchResult['facets']['popularMake']['items'])) {
            $this->topMakes = [];
            $this->selectedMake = [];
            $allMakes = $this->searchResult['facets']['popularMake']['items'] + (! empty($this->searchResult['facets']['make']['items']) ? $this->searchResult['facets']['make']['items'] : []);
            usort($allMakes, function ($a, $b) {
                return $b['resultCount'] - $a['resultCount'];
            });
            foreach ($allMakes as $make) {
                if ($make['checked']) {
                    $this->selectedMake[] = $this->getItemWithLink($make);
                    continue;
                }
                if ($make['value'] != 'other') {
                    $this->topMakes[] = $this->getItemWithLink($make);
                }
            }

            if (count($this->topMakes) > self::TOP_MAKES_LENGTH) {
                $this->topMakes = array_slice($this->topMakes, 0, self::TOP_MAKES_LENGTH);
            }
        }
    }

    private function setCountryData()
    {
        if (! empty($this->searchResult['facets']['country']['items'])) {
            $this->topCountries = [];
            $this->selectedCountry = [];
            foreach ($this->searchResult['facets']['country']['items'] as $country) {
                if ($country['checked'] == true) {
                    $this->selectedCountry[] = $this->getItemWithLink($country);
                    continue;
                }
                $this->topCountries[] = $this->getItemWithLink($country);
            }

            if (count($this->topCountries) > self::TOP_COUNTRIES_LENGTH) {
                $this->topCountries = array_slice($this->topCountries, 0, self::TOP_COUNTRIES_LENGTH);
            }
        }
    }

    private function setRegionData()
    {
        if (! empty($this->searchResult['facets']['region']['items'])) {
            $this->topRegions = [];
            $this->selectedRegion = [];
            foreach ($this->searchResult['facets']['region']['items'] as $region) {
                if ($region['checked'] == true) {
                    $this->selectedRegion[] = $this->getItemWithLink($region);
                    continue;
                }
                $this->topRegions[] = $this->getItemWithLink($region);
            }

            if (count($this->topRegions) > self::TOP_REGIONS_LENGTH) {
                $this->topRegions = array_slice($this->topRegions, 0, self::TOP_REGIONS_LENGTH);
            }
        }
    }

    private function setLocationData()
    {
        if ($this->sitecodeKey == Sitecodes::SITECODE_KEY_TRADUS) {
            $this->selectedLocation = $this->getSelectedCountry();
            $this->topLocations = $this->getTopCountries();
            $this->locationsCount = $this->getCountriesCount();
        } else {
            $this->selectedLocation = $this->getSelectedRegion();
            $this->topLocations = $this->getTopRegions();
            $this->locationsCount = $this->getRegionsCount();
        }
    }

    private function getSelectedLocationText()
    {
        $selectedLocations = $this->getSelectedLocation();
        if (! empty($selectedLocations)) {
            return $this->getReadableItems($selectedLocations, '@location');
        }

        return ['text' => '@location', 'placeholder' => ['@location' => '']];
    }

    private function getSelectedMakeText()
    {
        $selectedMakes = $this->getSelectedMake();
        if (! empty($selectedMakes)) {
            return $this->getReadableItems($selectedMakes, '@make');
        }

        return ['text' => '@make', 'placeholder' => ['@make' => '']];
    }

    private function setPriceTypeData()
    {
        if (! empty($this->searchResult['facets']['priceType']['items'])) {
            $this->topPriceTypes = array_fill_keys(self::ELIGIBLE_PRICE_TYPES, '');
            foreach ($this->searchResult['facets']['priceType']['items'] as $priceType) {
                if (empty($priceType['checked']) && in_array($priceType['value'], self::ELIGIBLE_PRICE_TYPES)) {
                    $priceType['label'] = $this->translator->trans(self::ELIGIBLE_PRICE_TYPE_CONTENTS[$priceType['value']]);
                    $this->topPriceTypes[$priceType['value']] = $this->getItemWithLink($priceType);
                }
                if ($priceType['checked'] == true) {
                    $this->selectedPriceType = $this->getItemWithLink($priceType);
                }
            }
            $this->topPriceTypes = array_filter($this->topPriceTypes);
        }
    }

    private function setCategoryData()
    {
        if (! empty($this->searchResult['facets']['subtype']['items'])) {
            $this->topCategories = [];
            foreach ($this->searchResult['facets']['subtype']['items'] as $subtype) {
                if ($subtype['checked'] == true) {
                    $this->selectedCategory = strtolower($this->getItemWithLink($subtype));
                    $this->selectedCatL3 = $subtype;
                    continue;
                }
                $this->topCategories[] = strtolower($this->getItemWithLink($subtype));
            }
        }
        if (! empty($this->searchResult['facets']['type']['items'])) {
            foreach ($this->searchResult['facets']['type']['items'] as $type) {
                if ($type['checked'] == true) {
                    if (empty($this->selectedCategory)) {
                        $this->selectedCategory = $this->getItemWithLink($type);
                    }
                    $this->selectedCatL2 = $type;
                    continue;
                }
            }
        }
        if (! empty($this->searchResult['facets']['category']['items'])) {
            foreach ($this->searchResult['facets']['category']['items'] as $category) {
                if ($category['checked'] == true) {
                    if (empty($this->selectedCategory)) {
                        $this->selectedCategory = $this->getItemWithLink($category);
                    }
                    $this->selectedCatL1 = $category;
                    continue;
                }
            }
        }

        if (count($this->topCategories) > self::TOP_CATEGORIES_LENGTH) {
            $this->topCategories = array_slice($this->topCategories, 0, self::TOP_CATEGORIES_LENGTH);
        }
    }

    private function getItemWithLink($item = [])
    {
        $itemLink = '';
        if (! empty($item['label'])) {
            $itemLink = $item['label'];
            /*if (! empty($item['url'])) {
                $itemLink = sprintf('<a href="%s">%s</a>', $item['url'], $itemLink);
            }*/
        }

        return $itemLink;
    }

    private function getReadableItems($items = [], $placeholder = '@placeholder')
    {
        $formattedContents = ['text' => '', 'placeholder' => []];
        if (! empty($items)) {
            $placeholder1 = '@placeholder1';
            $placeholder2 = '@placeholder2';
            if (count($items) > 1) {
                $lastContent = array_pop($items);
                $firstContents = $items;

                $text = $this->translator->trans("$placeholder1 and $placeholder2", [$placeholder1 => implode(', ', $firstContents).'</b>', $placeholder2 => '<b>'.$lastContent]);

                return $formattedContents = [
                    'text' => "$placeholder",
                    'placeholder' => [$placeholder => $text],
                ];
            }

            $formattedContents = ['text' => "$placeholder", 'placeholder' => [$placeholder => current($items)]];
        }

        return $formattedContents;
    }

    private function setSearchFiltersData()
    {
        $this->setCategoryData();
        $this->setMakeData();
        $this->setModelData();
        $this->setVersionData();
        $this->setLocationData();
        $this->setPriceTypeData();
    }

    private function getSeoTitle()
    {
        if (empty($this->selectedCatL3) || $this->sitecodeKey == Sitecodes::SITECODE_KEY_AUTOTRADER) {
            return [];
        }

        $makeModelVersionCategory = $this->getMakeModelVersionCategory();

        $metaTitle = [
            'text' => sprintf('Used %s for sale', $makeModelVersionCategory['text']),
            'placeholder' => ['@altcategory' => ''] + $makeModelVersionCategory['placeholder'],
        ];

        if (! empty($this->selectedLocation)) {
            $selectedLocationText = $this->getSelectedLocationText();
            $metaTitle['text'] .= ' in '.$selectedLocationText['text'];
            $metaTitle['placeholder'] += $selectedLocationText['placeholder'];
        }

        if (! empty($this->categoryAliases) && ! empty($metaTitle['placeholder']['@category'])) {
            $altPlaceholder = $metaTitle['placeholder'];
            $altPlaceholder['@category'] = sprintf('%s %s', $altPlaceholder['@category'], $this->categoryAliases);
            $transTitle = ucfirst($this->pregReplaceText(['\s+' => ' ', '\s+,' => ',', '\s+\.' => '.'], strip_tags($this->translator->trans($metaTitle['text'], $altPlaceholder))));
            if (strlen($transTitle) <= 60) {
                return $transTitle;
            }
        }

        return ucfirst($this->pregReplaceText(['\s+' => ' ', '\s+,' => ',', '\s+\.' => '.'], strip_tags($this->translator->trans($metaTitle['text'], $metaTitle['placeholder']))));
    }

    public function pregReplaceText(array $placeholders, string $text)
    {
        if (! empty($text) && ! empty($placeholders)) {
            foreach ($placeholders as $key => $replacement) {
                $text = preg_replace("/$key/", $replacement, $text);
            }
        }

        return $text;
    }
}
