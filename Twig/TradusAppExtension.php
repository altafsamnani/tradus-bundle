<?php

namespace TradusBundle\Twig;

use Cocur\Slugify\Slugify;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use TradusBundle\Service\Config\ConfigServiceInterface;
use TradusBundle\Service\Translation\TranslationService;
use TradusBundle\Utils\CurrencyExchange\CurrencyExchange;
use Traversable;
use Twig_Environment;
use Twig_Extension;
use Twig_SimpleFilter;

/**
 * Class TradusAppExtension.
 */
class TradusAppExtension extends Twig_Extension
{
    /** @var TranslatorService $translatorService ; */
    protected $translatorService;

    /*
     * @var bool
     */
    public $isTranslator = false;

    /*
     * @var array
     */
    public $loadedResource;

    /*
     * @var mixed
     */
    public $requestRoute = null;

    /*
     * @var mixed
     */
    public $requestStack;

    /*
     * @var array
     */
    private $testEmails = 'olx.com';

    /*
     * @var array
     */
    private $testEmailsList = ['spinn2046@gmail.com', 'kg@katgazing.com', 'isabelle.muresan@gmail.com'];

    /**
     * TradusAppExtension constructor.
     *
     * @param TranslationService $translator
     * @param ContainerInterface $container
     * @param RequestStack
     */
    public function __construct(TranslationService $translationService, ContainerInterface $container, RequestStack $requestStack)
    {
        $this->translatorService = $translationService;
        $this->container = $container;
        $this->requestStack = $requestStack;
        $this->user = $this->getUser();
        $this->loadTranslatorMessageResources();
    }

    /**
     * Get a user from the Security Token Storage.
     *
     * @return mixed
     */
    protected function getUser()
    {
        $tokenStorage = $this->container->get('security.token_storage');
        $token = $tokenStorage->getToken();

        return $token && is_object($token) ?
            $tokenStorage->getToken()->getUser() : null;
    }

    public function setXliffResource($loadedResource)
    {
        $this->loadedResource = $loadedResource;
    }

    /**
     * Get XLIFF Loaded resources for Phraseapp.
     *
     * @return void
     */
    private function loadTranslatorMessageResources()
    {
        if (is_object($this->user) && (strpos($this->user->getEmail(), $this->testEmails)
                || in_array($this->user->getEmail(), $this->testEmailsList))) {
            global $kernel;
            $loader = new XliffFileLoader();
            $resource = $kernel->getProjectDir().'/app/Resources/translations/messages.en'.'.xlf';
            $this->setXliffResource($loader->load(
                $resource,
                $kernel->getContainer()->getParameter(ConfigServiceInterface::DEFAULT_LOCALE_CONFIG)
            ));
            $this->isTranslator = true;

            $currentRequest = $this->requestStack->getCurrentRequest();
            $this->requestRoute = is_object($currentRequest) ? $currentRequest->attributes->get('_route') : '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('t', [$this, 't'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('slugify', [$this, 'slugify'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('safe_join', [$this, 'safeJoin'], ['needs_environment' => true, 'is_safe' => ['html']]),
            new Twig_SimpleFilter('traduscurrency', [$this, 'tradusLocalisedCurrency']),
            new Twig_SimpleFilter('render', [$this, 'identity']),
            new Twig_SimpleFilter('tNoFallback', [$this, 'tNoFallback'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('stringReplace', [$this, 'stringReplace']),
        ];
    }

    public function identity($string)
    {
        return $string;
    }

    public function slugify($string)
    {
        $slugify = new Slugify();

        return $slugify->slugify($string);
    }

    /**
     * Function to replicate the Drupal t behaviour.
     *
     * @param string $string The string that needs to be translated.
     * @param array $parameters The parameters that need to be passed on to the translator.
     *
     * @return string
     */
    public function t($string, array $parameters = [])
    {
        if (! $string) {
            return;
        }

        if ($this->isTranslator && isset($_COOKIE['phraseapp_live_enabled'])) {
            return $this->translatorService->transPhraseApp(
                $string,
                $this->loadedResource,
                $this->requestRoute
            );
        }

        return $this->translatorService->trans($string, $parameters);
    }

    public function tNoFallback($string, array $parameters = [])
    {
        if (! $string) {
            return;
        }

        return $this->translatorService->trans($string, $parameters, false);
    }

    /**
     * Joins several strings together safely.
     *
     * @param Twig_Environment $env A Twig_Environment instance.
     * @param mixed[]|Traversable|null $value The pieces to join.
     * @param string $glue
     *   The delimiter with which to join the string. Defaults to an empty string.
     *   This value is expected to be safe for output and user provided data
     *   should never be used as a glue.
     *
     * @return string
     *   The strings joined together.
     */
    public function safeJoin(Twig_Environment $env, $value, $glue = '')
    {
        if ($value instanceof Traversable) {
            $value = iterator_to_array($value, false);
        }

        return implode($glue, array_map(function ($item) use ($env) {
            // If $item is not marked safe then it will be escaped.
            return twig_escape_filter($env, $item, 'html', null, true);
        }, (array) $value));
    }

    /**
     * Function for formatting localised currency without fractions.
     *
     * @param float|int $number
     *   The number to be formatted.
     * @param null $currency
     *   The currency to be used.
     * @param null $locale
     *   The locale for which the currency and value are to be rendered.
     *
     * @return string|bool
     *   Returns the rendered number or FALSE when empty.
     */
    public function tradusLocalisedCurrency($number, $currency = null, $locale = null)
    {
        return CurrencyExchange::getLocalisedCurrency($number, $currency, $locale);
    }

    /**
     * Filter to be used in twig file for handling string and replace string parts based on regex passed
     * It accepts parameter $placeholder in form of array
     * This is an example for removing spaces before comma | stringReplace({'\\s+,': ','}).
     *
     * @param string $text
     * @param array $placeholders
     * @return string|null
     */
    public function stringReplace(string $text, array $placeholders)
    {
        if (! empty($text) && ! empty($placeholders)) {
            foreach ($placeholders as $key => $replacement) {
                $text = preg_replace("/$key/", $replacement, $text);
            }
        }

        return $text;
    }
}
