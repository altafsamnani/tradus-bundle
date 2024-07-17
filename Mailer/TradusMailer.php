<?php

namespace TradusBundle\Mailer;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Swift_Mailer;
use Symfony\Component\Translation\Translator;
use TradusBundle\Entity\Autologin;
use TradusBundle\Entity\Email;
use TradusBundle\Entity\EmailConversation;
use TradusBundle\Entity\Offer;
use TradusBundle\Entity\ReportAbuse;
use TradusBundle\Entity\Seller;
use TradusBundle\Entity\TradusUser;
use TradusBundle\Service\Alerts\Rules\AlertRuleResponse;
use TradusBundle\Service\Config\ConfigService;
use TradusBundle\Service\Favorites\Rules\FavoriteRuleResponse;
use TradusBundle\Service\Mail\SpamDetectService;
use TradusBundle\Service\Offer\OfferService;
use TradusBundle\Service\Sitecode\SitecodeService;
use TradusBundle\Service\Switchboard\SwitchboardService;
use TradusBundle\Service\Utils\CurrencyService;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig_Environment;
use Twig_Error_Loader;
use Twig_Error_Runtime;
use Twig_Error_Syntax;

class TradusMailer
{
    private $api_key;

    private $environment;

    /** @var Translator */
    private $translator;

    /** @var object ConfigService */
    private $config;

    private $container;

    /** @var EntityManager $entityManager */
    private $entityManager;

    /** @var string $sendgridBearer */
    private $sendgridBearer;

    /** @var string $sendgridURL */
    private $sendgridURL;

    public function __construct(
        $api_key = null,
        ?Twig_Environment $twig = null,
        ?Swift_Mailer $mailer = null,
        ?Registry $em = null
    ) {
        if (! $api_key) {
            throw new Exception('No api_key');
        }

        $this->mailer = $mailer;
        $this->twig = $twig;

        $this->api_key = $api_key;
        $this->em = $em;

        global $kernel;
        $this->environment = $kernel->getEnvironment();
        $this->translator = $kernel->getContainer()->get('translator');
        $this->config = $kernel->getContainer()->get('tradus.config');
        $this->container = $kernel->getContainer();

        $this->sendgridBearer = $kernel->getContainer()->getParameter('sendgrid_token');
        $this->sendgridURL = $kernel->getContainer()->getParameter('sendgrid_url');
    }

    public function send($data)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->sendgridURL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'authorization: '.$this->sendgridBearer,
                'content-type: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new Exception('cURL Error #:'.$err, 1);
        }
        $response = json_decode($response, true);

        if (! empty($response['errors'])) {
            throw new Exception($response['errors'][0]['message'], 1);
        }

        return $response;
    }

    /**
     * Send Callback email to seller.
     *
     * @param Offer $offer Offer entity
     * @param array $content content array
     *
     * @return mixed
     * @throws Exception
     */
    public function sendCallbackEmail(Offer $offer, $content)
    {
        $seller = $offer->getSeller();
        foreach ($offer->getDescriptions() as $desc) {
            $content['offer_title'] = $desc->getTitle();
            break;
        }
        $content['offer_url'] = '/en/offer/'.$offer->getId();
        $content['offer_id'] = $offer->getId();

        $em = $this->em->getManager();
        $twig = $this->twig;
        $seller_email = $seller->getSellerContactEmail();

        if ($sellerLocale = $seller->getSellerLocale()) {
            $this->translator->setLocale($sellerLocale);
        }
        $content['username'] = ! empty($content['full_name']) ? $content['full_name'] :
            $this->translator->trans($this->container->getParameter('sitecode')['site_title'].' user');
        /* utm link data */
        $utm = [
            'utmSource' => ConfigService::UTM_SOURCE,
            'utmMedium' => ConfigService::UTM_MEDIUM,
            'urlDomain' => ($this->environment == 'dev') ?
                $this->container->getParameter('sitecode')['domain_dev'] :
                $this->container->getParameter('sitecode')['domain'],
        ];
        $utm['utmLogoCampaign'] = 'seller-callback-lead';

        $template = $twig
            ->render(
                '@templates/email/contact-seller/callback.html.twig',
                array_merge([
                    'seller' => $seller,
                    'data' => $content,
                ], $utm)
            );
        $seller_subject = $this->translator
            ->trans(
                '%name% asked for a call back for ad %title%',
                [
                    '%name%' => $content['username'],
                    '%title%' => $content['offer_title'],
                ]
            );

        // SEND TO SELLER
        $data['ip'] = null;
        $data['subject'] = $seller_subject;
        $data['body'] = $template;

        $data['from'] = $this->container->getParameter('sitecode')['emails']['support_email'];
        $data['to'] = $seller_email;

        $data['offer'] = $offer;
        $data['seller'] = $seller;
        $data['reply_To'] = $content['from_email'];
        $data['email_type'] = Email::EMAIL_TYPE_CALLBACK_TO_SELLER;
        $data['email_template'] = null;
        $data['message'] = null;
        $data['predefinedQuestion'] = null;

        if ($content['ip']) {
            $data['ip'] = $content['ip'];
        }

        if (! ($content['user_id'])) {
            $content['user_id'] = null;
        }
        $data['user_id'] = $content['user_id'];

        $spamDetectService = new SpamDetectService(
            $this->em,
            $content['from_email'],
            null,
            null,
            $content
        );

        if ($spamDetectService->isSpam()) {
            $spamDetectService->saveSpamEmail($data);
            $spamDetectService->saveSpamUser($content['from_email'], $data['ip']);

            return;
        }

        $this->saveEmail($data);
    }

    /**
     * @param Offer $offer
     * @param $content
     * @param $seller
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     * @throws Exception
     */
    public function sendFormEmail(Offer $offer, $content, $seller)
    {
        $scs = new SitecodeService();
        $requiresLogin = $scs->getSitecodeParameter('leads.requires_login');
        $locale = $scs->getDefaultLocale();
        $styles = $this->container->getParameter('sitecode')['emails']['styles'];

        foreach ($offer->getDescriptions() as $desc) {
            $content['offer_title'] = $desc->getTitle();
            break;
        }
        $offerURL = $locale.'/offer/'.$offer->getId();
        $offerURL = str_replace('//', '/', $offerURL);
        $content['offer_url'] = $offerURL;
        $content['offer_id'] = $offer->getId();

        $em = isset($content['reminder']) ? $this->entityManager : $this->em->getManager();
        $twig = $this->twig;

        $emailTemplateRepository = $em->getRepository('TradusBundle:EmailTemplate');
        $email_template = $emailTemplateRepository->findOneBy(
            ['email_type' => 'send_form']
        );

        if (! $email_template) {
            throw new Exception('No email template found');
        }

        if ($sellerLocale = $seller->getSellerLocale()) {
            $content['seller_locale'] = $sellerLocale;
            $this->translator->setLocale($sellerLocale);
        }

        $content['username'] = ! empty($content['full_name']) ? $content['full_name'] :
            $this->translator->trans($this->container->getParameter('sitecode')['site_title'].' user');
        /* utm link data */
        $utm = [
            'utmSource' => ConfigService::UTM_SOURCE,
            'utmMedium' => ConfigService::UTM_MEDIUM,
            'urlDomain' => ($this->environment == 'dev') ?
                $this->container->getParameter('sitecode')['domain_dev'] :
                $this->container->getParameter('sitecode')['domain'],
        ];
        $utm['utmLogoCampaign'] = 'seller-lead';
        $content['defaultLocale'] = $locale;

        // We are getting the keys from db to translate to the correct seller locale
        $configValue = $this->config->getSettingValue('lead.questions');
        $dbQuestions = reset($configValue);
        $originalQuestions = isset($content['predefinedQuestion']) ?: false;
        $content['backgroundButtonColor'] = $styles['background_button_color'];
        $content['backgroundHeaderColor'] = $styles['background_header_color'];
        $content['backgroundFooterColor'] = $styles['background_footer_color'];
        // we need to translate the questions to the seller language
        $predefinedQuestion = '';
        if (isset($content['predefinedQuestion']) && ! empty($content['predefinedQuestion'])) {
            /*
                We are saving the original values to send the buyer email
                and we use the translated ones to send to the seller
            */
            $predefinedQuestion = $content['originalQuestions'] = $content['predefinedQuestion'];
            foreach ($content['predefinedQuestion'] as $question => $value) {
                $newQuestion = $this->translator->trans($dbQuestions[$value]);
                unset($content['predefinedQuestion'][$question]);
                $content['predefinedQuestion'][$newQuestion] = $value;
            }
        }

        $content['placeholder'] = Email::EMAIL_PLACEHOLDER_FOR_NOTIFICATION;

        if (isset($content['reminder'])) {
            $template = $twig
                ->render(
                    '@templates/email/contact-seller/to-seller-reminder.html.twig',
                    array_merge([
                        'seller' => $seller,
                        'data' => $content,
                    ], $utm)
                );
        } else {
            $template = $twig
                ->render(
                    '@templates/email/contact-seller/to-seller-premium.html.twig',
                    array_merge([
                        'seller' => $seller,
                        'data' => $content,
                    ], $utm)
                );
        }

        if (! $requiresLogin) {
            $seller_subject = $this->translator
                ->trans(
                    'You have received this message for your listing @title',
                    ['@title' => $content['offer_title']]
                );
        } else {
            if (isset($content['reminder'])) {
                $seller_subject = $this->translator
                    ->trans(
                        'Reminder: message from @name re: @title',
                        [
                            '@name' => $content['username'],
                            '@title' => $content['offer_title'],
                        ]
                    );
            } else {
                $seller_subject = $this->translator
                    ->trans(
                        'New message from @name re: @title',
                        [
                            '@name' => $content['username'],
                            '@title' => $content['offer_title'],
                        ]
                    );
            }
        }

        // SEND TO SELLER
        $data = [];
        $data['ip'] = null;
        $data['subject'] = $seller_subject;
        $data['body'] = $template;

        $data['from'] = $this->container->getParameter('sitecode')['emails']['support_email'];
        $seller_email = $seller->getSellerContactEmail();
        $data['to'] = $seller_email;

        $data['offer'] = $offer;
        $data['seller'] = $seller;
        $data['email_template'] = $email_template;
        $data['message'] = $content['message'];
        $data['reply_To'] = $content['from_email'];
        $data['email_type'] = isset($content['reminder']) ? Email::EMAIL_TYPE_FORM_REMINDER_EMAIL_TO_SELLER : Email::EMAIL_TYPE_FORM_EMAIL_TO_SELLER;
        $data['predefinedQuestion'] = $originalQuestions;
        $data['predefinedQuestionId'] = $predefinedQuestion;

        // reset the question to the original state so we send the email to the buyer in the correct locale
        $content['backgroundButtonColor'] = $styles['background_button_color'];
        $content['backgroundHeaderColor'] = $styles['background_header_color'];
        $content['backgroundFooterColor'] = $styles['background_footer_color'];

        if (isset($content['ip'])) {
            $data['ip'] = $content['ip'];
        }

        if (isset($content['user_id'])) {
            $data['user_id'] = $content['user_id'];
        }

        $spamDetectService = new SpamDetectService(
            $this->em,
            $content['from_email'],
            $content['message'],
            $predefinedQuestion,
            $content
        );

        if ($spamDetectService->isSpam()) {
            $spamDetectService->saveSpamEmail($data);
            $spamDetectService->saveSpamUser($content['from_email'], $data['ip']);

            return;
        }

        $emailId = isset($content['reminder']) ? $this->saveEmailReminder($data) : $this->saveEmail($data);

        if (strpos($data['to'], '@olx.com') || strpos($data['reply_To'], '@olx.com')) {
            try {
                // Save the lead to the switchboard system
                /** @var SwitchboardService $switchboardService */
                $switchboardService = $this->container->get('switchboard.service');
                $switchboardService->createConversation($content['user_id'], $seller, $offer, $content['message'], $template, $emailId);
            } catch (Exception $e) {
                $logger = $this->container->get('logger');
                $logger->error($e->getMessage(), ['createConversation']);
            }
        }

        if (! isset($content['reminder'])) {
            // Set the Buyer Locale
            $this->translator->setLocale($content['locale']);

            // Create hash for similar alerts and add it to object sent to email template
            $content['similar_alerts_hash'] = base64_encode(
                json_encode(
                    [
                        'offer_id' => $offer->getId(),
                        'user_id' => $content['user_id'],
                    ]
                )
            );

            // SEND TO BUYER
            $content['bottomPart'] = 'inbox';
            if (isset($content['userStatus']) && $content['userStatus'] == TradusUser::STATUS_NO_ACCOUNT) {
                $content['bottomPart'] = 'activate';
                $content['resetLink'] = $scs->getSitecodeDomain().'account/activate?activatecode='.$content['resetCode'];
            }
            $utm['utmLogoCampaign'] = 'buyer-lead';
            $content['predefinedQuestion'] = $predefinedQuestion;

            $template_buyer = $twig
                ->render(
                    '@templates/email/contact-seller/to-buyer.html.twig',
                    array_merge([
                        'seller' => $seller,
                        'data' => $content,
                    ], $utm)
                );

            $data['subject'] = $this
                ->translator
                ->trans(
                    'Your inquiry about @title has been shared with the seller',
                    ['@title' => $content['offer_title']]
                );
            $data['body'] = $template_buyer;
            $data['from'] = $this->container->getParameter('sitecode')['emails']['support_email'];
            $data['to'] = $content['from_email'];
            $data['offer'] = $offer;
            $data['seller'] = $seller;
            $data['email_template'] = $email_template;
            $data['message'] = $content['message'];
            $data['reply_To'] = $seller_email;
            $data['email_type'] = Email::EMAIL_TYPE_FORM_EMAIL_TO_BUYER;

            if (isset($content['user_id'])) {
                $data['user_id'] = $content['user_id'];
            }

            $this->saveEmail($data);
        }
    }

    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function saveEmailReminder($data)
    {
        $scs = new SitecodeService();
        $sitecodeId = $scs->getSitecodeId();
        $entityManager = $this->entityManager;
        $sitecode = $entityManager->getRepository('TradusBundle:Sitecodes')->find($sitecodeId);

        $email = new Email();
        $email->setEmailFrom($data['from']);
        $email->setEmailTo($data['to']);
        $email->setSubject($data['subject']);
        $email->setMessage($data['message']);
        $email->setBody($data['body']);
        $email->setStatus(Email::STATUS_PENDING);
        $email->setToSeller($data['seller']);
        $email->setOffer($data['offer']);
        $email->setEmailTemplate($data['email_template']);
        $email->setReplyTo($data['reply_To']);
        $email->setEmailType($data['email_type']);
        $email->setPredefinedQuestion($data['predefinedQuestion']);
        $email->setPredefinedQuestionId($data['predefinedQuestionId'] ?? '');
        $email->setSitecode($sitecode);
        $email->setCreatedAt(new DateTime('now'));
        $email->setUpdatedAt(new DateTime('now'));

        if (isset($data['user_id']) && ! empty($data['user_id'])) {
            $email->setUserId($data['user_id']);
        } elseif ($data['email_type'] == Email::EMAIL_TYPE_FORM_REMINDER_EMAIL_TO_SELLER) {
            $em = $this->em->getManager();
            $userRepository = $em->getRepository('TradusBundle:TradusUser');
            $user = $userRepository->findOneBy(
                ['email' => $data['reply_To']]
            );
            if ($user) {
                $data['user_id'] = $user->getId();
                $email->setUserId($data['user_id']);
            }
        }

        if (isset($data['ip'])) {
            $email->setIp($data['ip']);
        }

        $em = $this->entityManager;
        $em->persist($email);
        $em->flush();

        if ($data['email_type'] == Email::EMAIL_TYPE_FORM_REMINDER_EMAIL_TO_SELLER) {
            //To get and update the email, by adding the hash after created the email template
            global $kernel;
            $domain = $kernel->getContainer()->getParameter('sitecode')['short_domain'];
            $template = $email->getBody();
            $emailNotification = $email->getNotificationEmailAddress('mail.'.$domain);
            $template = str_replace(Email::EMAIL_PLACEHOLDER_FOR_NOTIFICATION, $emailNotification, $template);
            $email->setBody($template);
            $em->persist($email);
            $em->flush();
        }

        return $email->getId();
    }

    public function saveEmail($data)
    {
        $scs = new SitecodeService();
        $sitecodeId = $scs->getSitecodeId();
        $entityManager = $this->em->getManager();
        $sitecode = $entityManager->getRepository('TradusBundle:Sitecodes')->find($sitecodeId);

        $email = new Email();
        $email->setEmailFrom($data['from']);
        $email->setEmailTo($data['to']);
        $email->setSubject($data['subject']);
        $email->setMessage($data['message']);
        $email->setBody($data['body']);
        $email->setStatus(Email::STATUS_PENDING);
        $email->setToSeller($data['seller']);
        $email->setOffer($data['offer']);
        $email->setEmailTemplate($data['email_template']);
        $email->setReplyTo($data['reply_To']);
        $email->setEmailType($data['email_type']);
        $email->setPredefinedQuestion($data['predefinedQuestion']);
        $email->setPredefinedQuestionId($data['predefinedQuestionId'] ?? '');
        $email->setSitecode($sitecode);

        if (isset($data['user_id']) && ! empty($data['user_id'])) {
            $email->setUserId($data['user_id']);
        } elseif (in_array($data['email_type'], [Email::EMAIL_TYPE_FORM_EMAIL_TO_SELLER, Email::EMAIL_TYPE_FORM_EMAIL_TO_BUYER])) {
            $em = $this->em->getManager();
            $userRepository = $em->getRepository('TradusBundle:TradusUser');

            if ($data['email_type'] == Email::EMAIL_TYPE_FORM_EMAIL_TO_SELLER) {
                $user = $userRepository->findOneBy(
                    ['email' => $data['reply_To']]
                );
            } elseif ($data['email_type'] == Email::EMAIL_TYPE_FORM_EMAIL_TO_BUYER) {
                $user = $userRepository->findOneBy(
                    ['email' => $data['to']]
                );
            }

            if ($user) {
                $data['user_id'] = $user->getId();
                $email->setUserId($data['user_id']);
            }
        }

        if (isset($data['ip'])) {
            $email->setIp($data['ip']);
        }

        $em = $this->em->getManager();
        $em->persist($email);
        $em->flush();

        if ($data['email_type'] == Email::EMAIL_TYPE_FORM_EMAIL_TO_SELLER) {
            //To get and update the email, by adding the hash after created the email template
            global $kernel;
            $domain = $kernel->getContainer()->getParameter('sitecode')['short_domain'];
            $template = $email->getBody();
            $emailNotification = $email->getNotificationEmailAddress('mail.'.$domain);
            $template = str_replace(Email::EMAIL_PLACEHOLDER_FOR_NOTIFICATION, $emailNotification, $template);
            $email->setBody($template);
            $em->persist($email);
            $em->flush();
        }

        if (isset($data['user_id']) &&
            ($data['email_type'] == Email::EMAIL_TYPE_FORM_EMAIL_RESPONSE_BUYER
                || $data['email_type'] == Email::EMAIL_TYPE_FORM_EMAIL_RESPONSE)) {
            $email_conversation = new EmailConversation();
            $email_conversation->setEmailId($email);
            $email_conversation->setIsRead('0');

            if (isset($data['first_email_id'])) {
                $email_conversation->setFirstParentId($data['first_email_id']);
            }

            if (isset($data['from_inbox'])) {
                $email_conversation->setFromInbox($data['from_inbox']);
            }

            $email_conversation->setCreatedAt(new DateTime('now'));
            $em = $this->em->getManager();
            $em->persist($email_conversation);
            $em->flush();
        }

        return $email->getId();
    }

    public function sendV1FormEmail($content)
    {
        $data['subject'] = $content['subject'];
        $data['body'] = $content['message'];
        $data['to'] = $content['to'];
        $data['offer_id'] = $content['offer_id'];
        $data['seller_id'] = $content['seller_id'];
        $data['message'] = $content['message'];
        $data['user_agent'] = $content['user_agent'];
        $data['ip'] = $content['ip'];

        $this->saveV1Email($data);
    }

    protected function saveV1Email($data)
    {
        $email = new Email();
        $email->setEmailFrom('no-reply@mail.tradus.com');
        $email->setEmailTo($data['to']);
        $email->setSubject($data['subject']);
        $email->setMessage($data['message']);
        $email->setBody($data['body']);
        $email->setStatus(Email::STATUS_PENDING);
        $email->setToSellerId($data['seller_id']);
        $email->setToOfferId($data['offer_id']);
        $email->setUserAgent($data['user_agent']);
        $email->setIp($data['ip']);

        $em = $this->em->getManager();
        $em->persist($email);
        $em->flush();
    }

    /**
     * @param AlertRuleResponse $response
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function sendSimilarOffersAlertEmail(AlertRuleResponse $response)
    {
        $urlDomain = ($this->environment == 'dev') ?
            $this->container->getParameter('sitecode')['domain_dev'] :
            $this->container->getParameter('sitecode')['domain'];
        $locale = ! empty($response->getLocale()) ? $response->getLocale() : CurrencyService::LANGUAGE_ENGLISH;
        $this->translator->setLocale($locale);

        $currencyService = new CurrencyService();
        $currency = $currencyService->getCurrency($locale);

        $templateData = [];
        $templateData['utmCampaign'] = $response->getData(AlertRuleResponse::DATA_CAMPAIGN_ID);
        $templateData['utmSource'] = ConfigService::UTM_SOURCE;
        $templateData['utmMedium'] = ConfigService::UTM_MEDIUM;
        $templateData['utmLogoCampaign'] = 'similar-offers-alert';
        $templateData['locale'] = $locale;
        $templateData['showEquipmentForSale'] = true;
        $templateData['matchedOffers'] = $response->getOffers();
        $userId = $response->getData(AlertRuleResponse::DATA_USER_ID);
        foreach ($templateData['matchedOffers'] as $key => $offer) {
            $hash = sha1(uniqid('', true));
            $templateData['matchedOffers'][$key]['autologinLink'] = $hash;
            $templateData['matchedOffers'][$key]['currency'] = $currency;
            $templateData['matchedOffers'][$key]['price']
                = $templateData['matchedOffers'][$key]['data_price'][$currency];
            $this->saveAutologinHash($userId, $hash);
            $templateData['matchedOffers'][$key]['url'] = ltrim($templateData['matchedOffers'][$key]['url'], '/');
        }
        $templateData['matchedOffersCount'] = count($response->getOffers());
        $templateData['matchedString'] = $response->getAlertString();
        $templateData['urlDomain'] = $urlDomain;
        $templateData['unsubscribeAlertUrl'] = $urlDomain.$locale.$response
                ->getData(AlertRuleResponse::DATA_ALERT_UNSUBSCRIBE);

        $templateData['userName'] = ! empty($response->getData(AlertRuleResponse::DATA_USER_FULL_NAME)) ?
            $response->getData(AlertRuleResponse::DATA_USER_FULL_NAME) :
            $this->translator->trans($this->container->getParameter('sitecode')['site_title'].' user');
        $templateData['relatedOffers'] = $response->getRelatedOffers();
        foreach ($templateData['relatedOffers'] as $key => $offer) {
            $hash = sha1(uniqid('', true));
            $templateData['relatedOffers'][$key]['autologinLink'] = $hash;
            $templateData['relatedOffers'][$key]['currency'] = $currency;
            $templateData['relatedOffers'][$key]['price']
                = $templateData['relatedOffers'][$key]['data_price'][$currency];
            $this->saveAutologinHash($userId, $hash);
            $templateData['relatedOffers'][$key]['url'] = ltrim($templateData['relatedOffers'][$key]['url'], '/');
        }
        $templateData['relatedMoreLink'] = $response->getRelatedSearchUrl();
        $templateData['sparePartsUrl'] = ! empty($response->getSparePartsUrl()) ? trim($response->getSparePartsUrl(), '/') : '';
        $templateData['sparePartsCount'] = $response->getSparePartsCount();
        $templateData['relatedNumberFound'] = $response->getRelatedNumberFound();
        $templateData['filterNames'] = $response->getCategoryTitle();
        $templateData['originalOfferId'] = $response->getData(AlertRuleResponse::DATA_ORIGINAL_OFFER_ID);
        $templateData['catL3Link'] = $urlDomain.$response->getData(AlertRuleResponse::DATA_CATEGORY_PATH);
        $templateData['priceAnalysisTypeConst'] = OfferService::getPriceTypeConsts();

        $template = $this->twig
            ->render(
                '@templates/email/similar-listings/similar-listings--gallery.html.twig',
                $templateData
            );

        // SEND TO BUYER
        $data['body'] = $template;
        $data['subject'] = $response->getData(AlertRuleResponse::DATA_EMAIL_SUBJECT);
        $data['from'] = $response->getData(AlertRuleResponse::DATA_EMAIL_FROM);
        $data['to'] = $response->getData(AlertRuleResponse::DATA_EMAIL_TO);
        $data['reply_To'] = $response->getData(AlertRuleResponse::DATA_EMAIL_FROM);
        $data['offer'] = null;
        $data['seller'] = null;
        $data['email_template'] = null;
        $data['message'] = null;
        $data['predefinedQuestion'] = null;

        $data['email_type'] = Email::EMAIL_TYPE_SIMILAR_OFFERS_ALERT;

        $this->saveEmail($data);
    }

    /* save data in emails */

    /**
     * @param object $data
     */
    protected function saveTransportEmail($data)
    {
        $email = new Email();
        $email->setToSeller($data['seller']);
        $email->setEmailFrom($data['from']);
        $email->setEmailTo($data['to']);
        $email->setReplyTo($data['from']);
        $email->setSubject($data['subject']);
        $email->setBody($data['body']);
        $email->setStatus(Email::STATUS_PENDING);
        $email->setEmailType($data['email_type']);
        $email->setOffer($data['offer']);
        $email->setReferenceId($data['reference_id']);
        $em = $this->em->getManager();
        $em->persist($email);
        $em->flush();
    }

    /* get the body content */

    /**
     * @param object $content
     */
    public function sendTransportEmail($content)
    {
        $offer = $content['offer'];
        $content['offer_url'] = 'en/offer/'.$offer->getId();
        $content['full_name'] = ! empty($content['full_name']) ?
            $content['full_name'] :
            $this->translator->trans($this->container->getParameter('sitecode')['site_title'].' user');

        $template = $this->twig->render(
            '@templates/email/contact-transport/to-transport-wheels.html.twig',
            [
                'data' => $content,
                'utmSource' => ConfigService::UTM_SOURCE,
                'utmMedium' => ConfigService::UTM_MEDIUM,
                'utmCampaign' => 'shipping-quote',
                'utmContent' => 'offer',
                'utmLogoCampaign' => 'transporting-wheels',
                'urlDomain' => ($this->environment == 'dev') ? $this->container->getParameter('sitecode')['domain_dev'] :
                    $this->container->getParameter('sitecode')['domain'],
            ]
        );
        // SEND TO TRANSPORT
        $data['body'] = $template;
        $data['subject'] = $this->translator->trans('Vehicle delivery request '.
            $this->container->getParameter('sitecode')['site_title']);
        $data['from'] = $content['from_email'];
        $data['to'] = 'sales@transportingwheels.com';
        $data['reply_To'] = $content['from_email'];
        $data['offer'] = $offer;
        $data['seller'] = $content['seller'];
        $data['reference_id'] = $content['shipping_id'];
        $data['email_type'] = Email::EMAIL_TYPE_CONTACT_TRANSPORT_WHEELS;
        $this->saveTransportEmail($data);
    }

    /**
     * to send offer status mail to seller using cron job.
     *
     * @param $data
     */
    public function sendSellerOfferStatusMail($data)
    {
        if (! empty($data['locale'])) {
            $this->translator->setLocale($data['locale']);
        }

        /* utm link data */
        $data['utmSource'] = ConfigService::UTM_SOURCE;
        $data['utmMedium'] = ConfigService::UTM_MEDIUM;
        $data['utmLogoCampaign'] = 'seller-performance';
        $data['urlDomain'] = $this->container->getParameter('sitecode')['pro_domain'];

        $data['body'] = preg_replace('!\s+!', ' ', $this->twig->render(
            '@templates/email/to-seller-offer-status.html.twig',
            $data
        ));

        $fromEmail = $this->container->getParameter('sitecode')['emails']['noreply_email'];

        $email = new Email();
        $email->setEmailFrom($fromEmail);
        $email->setEmailTo($data['email']);
        $email->setReplyTo($fromEmail);
        $email->setSubject(
            $this->translator->trans(
                'Congratulations! Your ads are attracting potential buyers'
            )
        );
        $email->setBody($data['body']);
        $email->setStatus(Email::STATUS_PENDING);
        $email->setEmailType($data['emailType']);
        $em = $this->em->getManager();
        $em->persist($email);
        $em->flush();
    }

    /**
     * Saves the hash for the autologin function.
     *
     * @param int $userId user_id
     * @param string $hash md5 hash
     *
     * @return void
     */
    public function saveAutologinHash(int $userId, string $hash)
    {
        $autologin = new Autologin();
        $autologin->setUserId($userId);
        $autologin->setType(Autologin::OFFER);
        $autologin->setToken($hash);
        $autologin->setAddedDate(new DateTime());
        $em = $this->em->getManager();
        $em->persist($autologin);
        $em->flush();
    }

    /**
     * Save search analytics weekly basis email.
     *
     * @param $emailFrom
     * @param $emailTo
     * @param object|null $searchAnalytics
     * @return bool
     */
    public function saveAnalyticsEmail($emailFrom, $emailTo, $searchAnalytics = null)
    {
        $entityManager = $this->em->getManager();

        $totalKeywords = count($searchAnalytics);
        $countryAnalytics = [];
        foreach ($searchAnalytics as $analytics) {
            $countryAnalytics[$analytics['country']]['data'][] = $analytics;
            $countryAnalytics[$analytics['country']]['name'] = $analytics['name'];
        }

        $template = $this->twig->render(
            '@templates/email/search-analytics/search-analytics-listings.html.twig',
            [
                'countryAnalytics' => $countryAnalytics,
                'total_keywords' => $totalKeywords,
            ]
        );

        $mailer = new Email();
        $mailer->setStatus(Email::STATUS_PENDING);
        $mailer->setEmailFrom($emailFrom);
        $mailer->setReplyTo($emailFrom);
        $mailer->setEmailTo(trim($emailTo));
        $mailer->setSubject('Weekly Search Analytics');
        $mailer->setBody($template);
        $mailer->setMessage($template);
        $mailer->setEmailType(Email::EMAIL_TYPE_WEEKLY_SEARCH_ANALYTICS);

        $entityManager->persist($mailer);
        $entityManager->flush();

        return true;
    }

    /**
     * @param FavoriteRuleResponse $response
     * @return void
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     * @throws Twig_Error_Loader
     */
    public function sendSimilarOffersFavoriteEmail(FavoriteRuleResponse $response)
    {
        $urlDomain = ($this->environment == 'dev') ?
            $this->container->getParameter('sitecode')['domain_dev'] :
            $this->container->getParameter('sitecode')['domain'];
        $locale = ! empty($response->getLocale()) ? $response->getLocale() : CurrencyService::LANGUAGE_ENGLISH;
        $this->translator->setLocale($locale);

        $templateData = [];

        $templateData['locale'] = $locale;
        $templateData['utmSource'] = ConfigService::UTM_SOURCE;
        $templateData['utmMedium'] = ConfigService::UTM_MEDIUM;
        $templateData['utmCampaign'] = 'removed-favorites';
        $templateData['utmContent'] = 'button-view-details';
        $templateData['utmLogoCampaign'] = 'removed-favorite';
        $templateData['urlDomain'] = $urlDomain;

        $userEmail = $response->getData(FavoriteRuleResponse::DATA_EMAIL_TO);
        $userId = $response->getData(FavoriteRuleResponse::DATA_USER_ID);

        $templateData['offerData'] = $response->getOfferData();
        $templateData['originalOfferId'] = 'original-offer-id-'.$templateData['offerData']['offer_id'];
        $templateData['userName'] = $response->getData(FavoriteRuleResponse::DATA_USER_FIRST_NAME);
        $templateData['relatedOffers'] = $response->getRelatedOffers();

        if (! empty($templateData['relatedOffers'])) {
            foreach ($templateData['relatedOffers'] as $key => $offer) {
                $hash = sha1(uniqid('', true));
                $templateData['relatedOffers'][$key]['autologinLink'] = $hash;
                $templateData['relatedOffers'][$key]['url'] = ltrim($templateData['relatedOffers'][$key]['url'], '/');
                $this->saveAutologinHash($userId, $hash);
            }
            $templateData['relatedMoreLink'] = ltrim($response->getRelatedSearchUrl(), '/');
        }

        $template = $this->twig
            ->render(
                '@templates/email/offer-favorites/similar-favorites--gallery.html.twig',
                $templateData
            );

        // SAVE TO EMAILS
        $data['body'] = $template;
        $data['subject'] = $response->getData(FavoriteRuleResponse::DATA_EMAIL_SUBJECT);
        $data['from'] = $response->getData(FavoriteRuleResponse::DATA_EMAIL_FROM);
        $data['to'] = $userEmail;
        $data['reply_To'] = $response->getData(FavoriteRuleResponse::DATA_EMAIL_FROM);
        $data['user_id'] = $userId;
        $data['offer'] = null;
        $data['seller'] = null;
        $data['email_template'] = null;
        $data['message'] = null;
        $data['email_type'] = Email::EMAIL_TYPE_FAVORITE_OFFERS_REMOVED;
        $data['predefinedQuestion'] = null;

        $this->saveEmail($data);
    }

    /* Save backend monitoring email
     *
     * @param string $emailFrom
     * @param array $emailTo
     * @param array $content
     *
     * @return void
     */
    public function saveBackendMonitoringEmail(string $emailFrom, array $emailTo, $content = [])
    {
        $entityManager = $this->em->getManager();

        $template = $this->twig->render(
            '@templates/email/backend-monitoring/backend-monitoring-listings.html.twig',
            [
                'flows' => $content,
            ]
        );

        foreach ($emailTo as $recipient) {
            $mailer = new Email();
            $mailer->setStatus(Email::STATUS_PENDING);
            $mailer->setEmailFrom($emailFrom);
            $mailer->setReplyTo($emailFrom);
            $mailer->setEmailTo(trim($recipient));
            $mailer->setSubject('Backend Monitoring');
            $mailer->setBody($template);
            $mailer->setMessage($template);
            $mailer->setEmailType(Email::EMAIL_TYPE_BACKEND_MONITORING);

            $entityManager->persist($mailer);
            $entityManager->flush();
        }
    }

    /**
     * @param $userId
     * @param $content
     * @return ReportAbuse
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveAbuseReportEmail($userId, array $content): ReportAbuse
    {
        $scs = new SitecodeService();
        $sitecodeId = $scs->getSitecodeId();
        /** @var ReportAbuse $reportAbuse */
        $reportAbuse = new ReportAbuse();
        $reportAbuse->setOffer($content['offerId']);
        $domain = ($this->environment == 'dev') ? $scs->getSitecodeDomainDev() : $scs->getSitecodeDomain();
        $offerUrl = strpos($content['offerUrl'], $domain) == -1 ? $domain.$content['offerUrl'] : $content['offerUrl'];
        $reportAbuse->setOfferUrl(preg_replace('/com\/\//i', 'com/', $offerUrl));
        $reportAbuse->setReason($content['reason']);
        $reportAbuse->setMessage($content['message']);
        $reportAbuse->setEmail($content['email']);
        $reportAbuse->setUserId($userId);
        $reportAbuse->setIp($content['ip']);
        $reportAbuse->setUserAgent($content['userAgent']);
        $reportAbuse->setLocale($content['locale']);
        $reportAbuse->setSitecodeId($sitecodeId);

        /** @var EntityManager $entityManager */
        $entityManager = $this->em->getManager();
        $entityManager->persist($reportAbuse);
        $entityManager->flush();

        return $reportAbuse;
    }

    /**
     * @param array $content
     * @return void
     * @throws LoaderError
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function saveAbuseReportReplyEmail(array $content)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->em->getManager();
        $scs = new SitecodeService();
        $sitecodeId = $scs->getSitecodeId();
        $sitecode = $entityManager->getRepository('TradusBundle:Sitecodes')->find($sitecodeId);
        $urlDomain = ($this->environment == 'dev') ?
            $this->container->getParameter('sitecode')['domain_dev'] :
            $this->container->getParameter('sitecode')['domain'];

        $template = $this->twig->render(
            '@templates/email/report-abuse/report-abuse-reply.html.twig',
            [
                'urlDomain' => $urlDomain,
                'locale' => $content['locale'],
                'utmSource' => ConfigService::UTM_SOURCE,
                'utmMedium' => ConfigService::UTM_MEDIUM,
                'utmLogoCampaign' => 'reply-report-abuse',
            ]
        );
        $mailer = new Email();
        $mailer->setStatus(Email::STATUS_PENDING);
        $mailer->setEmailFrom($this->container->getParameter('sitecode')['emails']['noreply_email']);
        $mailer->setReplyTo($this->container->getParameter('sitecode')['emails']['noreply_email']);
        $mailer->setEmailTo($content['email']);
        $mailer->setSubject($this->translator
            ->trans(
                'About your @site request',
                [
                    '@site' => $this->container->getParameter('sitecode')['site_title'],
                ]
            ));
        $mailer->setBody($template);
        $mailer->setMessage($template);
        $mailer->setEmailType(Email::EMAIL_TYPE_REPORT_ABUSE_REPLY);
        $mailer->setSitecode($sitecode);

        $entityManager->persist($mailer);
        $entityManager->flush();
    }

    /**
     * Sending feedback survey to buyers.
     *
     * @param int $userId
     * @param int $sellerId
     * @param int $offerId
     * @param string $locale
     * @return bool | array
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendBuyerSurvey(int $userId, int $sellerId, int $offerId, string $locale)
    {
        $this->translator->setLocale($locale);
        $entityManager = $this->em->getManager();
        $userRepository = $entityManager->getRepository('TradusBundle:TradusUser');
        $sellerRepository = $entityManager->getRepository('TradusBundle:Seller');
        $offerRepository = $entityManager->getRepository('TradusBundle:Offer');

        /** @var TradusUser $user */
        $user = $userRepository->findOneBy(['id' => $userId]);
        /** @var Seller $seller */
        $seller = $sellerRepository->findOneBy(['id' => $sellerId]);
        /** @var Offer $offer */
        $offer = $offerRepository->findOneBy(['id' => $offerId]);
        if (! $user) {
            return [
                'entity' => 'user',
                'id' => $userId,
            ];
        }
        if (! $seller) {
            return [
                'entity' => 'seller',
                'id' => $sellerId,
            ];
        }
        if (! $offer) {
            return [
                'entity' => 'offer',
                'id' => $offerId,
            ];
        }

        $scs = new SitecodeService();
        $sitecodeId = $scs->getSitecodeId();
        $sitecode = $entityManager->getRepository('TradusBundle:Sitecodes')->find($sitecodeId);

        /* utm link data */
        $data['utmSource'] = ConfigService::UTM_SOURCE;
        $data['utmMedium'] = ConfigService::UTM_MEDIUM;
        $data['utmCampaign'] = 'buyer-survey';
        $data['utmLogoCampaign'] = 'buyer-survey';
        $data['urlDomain'] = ($this->environment == 'dev') ?
            $this->container->getParameter('sitecode')['domain_dev'] :
            $this->container->getParameter('sitecode')['domain'];
        $data['user']['name'] = ! empty($user->getFullName()) ? $user->getFullName() :
            $this->translator->trans($this->container->getParameter('sitecode')['site_title'].' user');
        $data['image'] = $offerRepository->getDefaultImage($offer);
        $offerURL = $locale.'/offer/'.$offer->getId();
        $offerURL = $data['urlDomain'].str_replace('//', '/', $offerURL);
        $encodeData = base64_encode(json_encode([
            'seller_id' => $sellerId,
            'offer_id' => $offerId,
            'user_id' => $userId,
        ]));
        $userSurveyUrl = $data['urlDomain'].$locale.'/user-survey';
        $data['offer'] = [
            'name' => $offer->getTitleByLocale($locale),
            'price' => $offer->getPrice(),
            'currency' => $offer->getCurrency(),
            'url' => $offerURL,
            'yesLink' => $userSurveyUrl.'?data='.$encodeData,
            'noLink' => $userSurveyUrl.'/sorry?data='.$encodeData,
        ];
        $data['site'] = $this->container->getParameter('sitecode')['site_title'];
        $data['seller'] = [
            'name' => $seller->getCompanyName(),
            'city' => $seller->getCity(),
            'country' => $seller->getCountry(),
        ];
        $data['locale'] = $locale;

        $data['body'] = preg_replace('!\s+!', ' ', $this->twig->render(
            '@templates/email/buyer-feedback/buyer-feedback.html.twig',
            $data
        ));

        $email = new Email();
        $email->setEmailFrom($this->container->getParameter('sitecode')['emails']['noreply_email']);
        $email->setEmailTo($user->getEmail());
        $email->setReplyTo($this->container->getParameter('sitecode')['emails']['noreply_email']);
        $email->setSubject(
            $this->translator->trans(
                'How was your experience with %seller%?',
                [
                    '%seller%' => $seller->getCompanyName(),
                ]
            )
        );
        $email->setBody($data['body']);
        $email->setStatus(Email::STATUS_PENDING);
        $email->setEmailType(Email::EMAIL_TYPE_BUYER_SURVEY);
        $email->setToSeller($seller);
        $email->setUserId($user->getId());
        $email->setOffer($offer);
        $email->setSitecode($sitecode);
        $em = $this->em->getManager();
        $em->persist($email);
        $em->flush();

        return true;
    }

    /**
     * Sending feedback survey to buyers.
     *
     * @param TradusUser $user
     * @param string $locale
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendBuyerNpsSurvey(TradusUser $user, string $locale)
    {
        $this->translator->setLocale($locale);
        $entityManager = $this->em->getManager();

        $scs = new SitecodeService();
        $sitecodeId = $scs->getSitecodeId();
        $sitecode = $entityManager->getRepository('TradusBundle:Sitecodes')->find($sitecodeId);

        /* utm link data */
        $data['utmSource'] = ConfigService::UTM_SOURCE;
        $data['utmMedium'] = ConfigService::UTM_MEDIUM;
        $data['utmCampaign'] = 'buyer-nps-survey-survey';
        $data['utmLogoCampaign'] = 'buyer-nps-survey-survey';
        $data['urlDomain'] = (strpos($this->environment, 'dev')) ?
            $this->container->getParameter('sitecode')['domain_dev'] :
            $this->container->getParameter('sitecode')['domain'];
        $userSurveyUrl = $data['urlDomain'].$locale.'/user-nps-feedback';
        $encodeData = base64_encode(json_encode([
            'user_id' => $user->getId(),
        ]));
        $data['surveyLink'] = $userSurveyUrl.'?data='.$encodeData;
        $data['site'] = $this->container->getParameter('sitecode')['site_title'];
        $data['locale'] = $locale;

        $data['body'] = preg_replace('!\s+!', ' ', $this->twig->render(
            '@templates/email/buyer-feedback/buyer-nps-survey.html.twig',
            $data
        ));

        $email = new Email();
        $email->setEmailFrom($this->container->getParameter('sitecode')['emails']['noreply_email']);
        $email->setEmailTo($user->getEmail());
        $email->setReplyTo($this->container->getParameter('sitecode')['emails']['noreply_email']);
        $email->setSubject(
            $this->translator->trans(
                'Will you share your opinion?'
            )
        );
        $email->setBody($data['body']);
        $email->setStatus(Email::STATUS_PENDING);
        $email->setEmailType(Email::EMAIL_TYPE_BUYER_NPS_SURVEY);
        $email->setUserId($user->getId());
        $email->setSitecode($sitecode);
        $em = $this->em->getManager();
        $em->persist($email);
        $em->flush();
    }
}
