<?php

namespace TradusBundle\Service\Mail;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use http\Env\Request;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TradusBundle\Entity\Email;
use TradusBundle\Entity\ReportAbuse;
use TradusBundle\Entity\Seller;
use TradusBundle\Entity\Sitecodes;
use TradusBundle\Entity\TradusUser;
use TradusBundle\Repository\ConfigurationRepository;
use TradusBundle\Service\Alerts\Rules\AlertRuleInterface;
use TradusBundle\Service\Config\ConfigService;
use TradusBundle\Service\Config\ConfigServiceInterface;
use TradusBundle\Service\Journal\JournalService;
use TradusBundle\Service\ShippingQuote\ShippingQuoteService;
use TradusBundle\Service\Sitecode\SitecodeService;
use TradusBundle\Service\TradusUser\TradusUserService;
use Twig_Environment;
use Twig_Error_Loader;
use Twig_Error_Runtime;
use Twig_Error_Syntax;

/**
 * Class MailService.
 */
class MailService
{
    /** @var $sendGridHeader */
    private $sendGridHeader;

    /** @var resource */
    protected $handle;

    /** @var string */
    private $endpoint;

    /** @var Twig_Environment */
    protected $twig;

    /** @var ObjectManager|object */
    protected $entityManager;

    /** @var array */
    protected $emails = [];

    /** @var int */
    protected $countSendSucces = 0;

    /** @var int */
    protected $countSendFailed = 0;

    /** @var ConfigService $config */
    protected $config;

    /** @var string */
    protected $enviroment;

    /** @var string */
    protected $translator;

    protected $defaultLocale;

    protected $sitecodeService;

    /**
     * MailService constructor.
     * @param Twig_Environment $twig
     * @param Registry|null $em
     */
    public function __construct(Twig_Environment $twig, ?Registry $em = null)
    {
        global $kernel;
        $this->endpoint = $kernel->getContainer()->getParameter('sendgrid_url');
        $this->twig = $twig;

        // Only set when database is available
        if ($em) {
            $this->entityManager = $em->getManager();
        }

        if ($kernel->getContainer()->has('tradus.config')) {
            $this->config = $kernel->getContainer()->get('tradus.config');
        }

        $this->sitecodeService = new SitecodeService();
        $this->enviroment = $kernel->getEnvironment();
        $this->translator = $kernel->getContainer()->get('translator');
        $this->defaultLocale = $kernel->getContainer()->getParameter(ConfigServiceInterface::DEFAULT_LOCALE_CONFIG);

        $this->sendGridHeader = [
            'authorization: '.$kernel->getContainer()->getParameter('sendgrid_token'),
            'User-Agent: sendgrid/tradus',
            'content-type: application/json',
        ];

        $this->handle = $this->createHandle($this->endpoint);
    }

    /**
     * @param string $email
     * @param string $resetLink
     * @param string $name
     * @param string $preferredLocale
     *
     * @return array
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function sendPasswordForgetEmail(
        $email,
        $resetLink,
        $name = '',
        $preferredLocale = null
    ) {
        global $kernel;

        //To set the language of the email in set Preferred Locale
        $locale = $this->translator->getLocale() ?? $this->defaultLocale;
        $this->translator->setLocale($preferredLocale ?? $locale);
        $body = $this->twig->render(
            '@templates/email/account/reset-password.html.twig',
            [
                'resetLink' => $resetLink,
                'name' => ! empty($name) ? $name :
                    $this->translator->trans($kernel->getContainer()->getParameter('sitecode')['site_title'].' user'),
                'utmSource' => ConfigService::UTM_SOURCE,
                'utmMedium' => ConfigService::UTM_MEDIUM,
                'urlDomain' => $kernel->getContainer()->getParameter('sitecode')['domain'],
                'utmLogoCampaign' => 'password-reset',
            ]
        );

        $mail = $this->getMailRequest();
        $mail->setFrom(
            $kernel->getContainer()->getParameter('sitecode')['emails']['support_email'],
            $kernel->getContainer()->getParameter('sitecode')['site_title']
        );

        $subject = $this->translator->trans(
            'Please reset your '.$kernel->getContainer()->getParameter('sitecode')['site_title'].' password'
        );
        $mail->setSubject($subject);
        $mail->setContent($body);
        $mail->setTo($email);
        $mail->addCategory('PASSWORD_FORGET');

        //To set the language back to the current user locale to display message
        if ($preferredLocale && $preferredLocale != $locale) {
            $this->translator->setLocale($locale);
        }

        return $this->sendMail($mail);
    }

    public function getMailRequest()
    {
        return new RequestSendGrid();
    }

    /**
     * @param $email
     * @return object
     * @throws NonUniqueResultException
     */
    public function getTradusUser($email)
    {
        $userService = new TradusUserService($this->entityManager, new JournalService($this->entityManager));

        return $userService->findOneByEmail($email);
    }

    /**
     * @param $email
     * @return object
     */
    public function getTradusSeller($email)
    {
        return $this->entityManager->getRepository('TradusBundle:Seller')->findOneBy(['email' => $email]);
    }

    /**
     * @param $email
     * @deprecated
     * @return bool
     */
    public function checkRecipient($email)
    {
        $buyer = $this->getTradusUser($email);
        $seller = $this->getTradusSeller($email);

        if (! empty($buyer) || ! empty($seller)) {
            return true;
        }

        return false;
    }

    /**
     * @param $email
     * @param $locale
     * @return array
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function sendIntroductionEmail($email, $userLocale = null)
    {
        if ($this->sitecodeService->getSitecodeKey() == Sitecodes::SITECODE_KEY_TRADUS) {
            $userLocale = $userLocale ?? $this->defaultLocale;
            $locale = in_array($userLocale, ConfigServiceInterface::IMAGES_AVAILABLE_LOCALE) ? $userLocale : $this->defaultLocale;
            $this->translator->setLocale($locale);
            $body = $this->twig->render(
                '@templates/email/account/buyer-welcome-email.html.twig',
                [
                    'start_selling_link' => $this->sitecodeService->getStartSellingLink($locale),
                    'locale' => $locale,
                    'utmSource' => ConfigService::UTM_SOURCE,
                    'utmMedium' => ConfigService::UTM_MEDIUM,
                    'urlDomain' => $this->sitecodeService->getSitecodeDomain(),
                    'utmLogoCampaign' => 'introduction-email',
                ]
            );

            $mail = $this->getMailRequest();

            $subject = $this->translator->trans(
                'Welcome to %site% - the smarter global marketplace',
                ['%site%' => strtoupper($this->sitecodeService->getSitecodeTitle())]
            );

            $mail->setSubject($subject);
            $mail->setFrom(
                $this->sitecodeService->getSitecodeParameter('emails.support_email'),
                $this->sitecodeService->getSitecodeTitle()
            );
            $mail->setContent($body);
            $mail->setTo($email);
            $mail->addCategory('BUYER_WELCOME_EMAIL');

            return $this->sendMail($mail);
        }

        return [];
    }

    /**
     * @param $email
     * @param $confirmUrl
     * @return array
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function sendConfirmRegistrationEmail($email, $confirmUrl, $locale = null)
    {
        $locale = $locale ?? $this->defaultLocale;
        $this->translator->setLocale($locale);
        $body = $this->twig->render(
            '@templates/email/account/confirm-email.html.twig',
            [
                'confirmUrl' => $confirmUrl,
                'utmSource' => ConfigService::UTM_SOURCE,
                'utmMedium' => ConfigService::UTM_MEDIUM,
                'urlDomain' => $this->sitecodeService->getSitecodeDomain(),
                'utmLogoCampaign' => 'confirmation-registration',
            ]
        );
        $mail = $this->getMailRequest();

        $subject = $this->translator->trans(
            'Confirm your %site% account',
            ['%site%' => $this->sitecodeService->getSitecodeTitle()]
        );

        $mail->setSubject($subject);
        $mail->setFrom(
            $this->sitecodeService->getSitecodeParameter('emails.support_email'),
            $this->sitecodeService->getSitecodeTitle()
        );
        $mail->setContent($body);
        $mail->setTo($email);
        $mail->addCategory('CONFIRM_REGISTRATION');

        return $this->sendMail($mail);
    }

    /**
     * Used for sending emails in command.
     * @return int
     */
    public function findNewEmailsToSend()
    {
        $this->emails = $this->entityManager->getRepository('TradusBundle:Email')->findBy(
            ['status' => Email::STATUS_PENDING]
        );
        $this->verifyEmails();

        return count($this->emails);
    }

    /**
     * Checks if emails contains valid email address
     * We could add other stuff to this later like bounces or spam stuff.
     */
    private function verifyEmails()
    {
        if (count($this->emails)) {
            /** @var Email $emailEntity */
            foreach ($this->emails as $key => $emailEntity) {
                if (! filter_var($emailEntity->getEmailTo(), FILTER_VALIDATE_EMAIL)) {
                    if ($emailEntity) {
                        $emailEntity->setStatus(Email::STATUS_ERROR);
                        $this->entityManager->persist($emailEntity);
                        $this->entityManager->flush();
                    }
                    unset($this->emails[$key]);
                }
                /* check receipient exist in tradus before sending emails */
                /* We removed this check because sometimes the seller responds via a localized email or alias */
                /* if (!$this->checkRecipient($emailEntity->getEmailTo())) {
                    if ($emailEntity->getEmailType() != Email::EMAIL_TYPE_CONTACT_TRANSPORT_WHEELS
                        && $emailEntity->getEmailType() != Email::EMAIL_TYPE_WEEKLY_SEARCH_ANALYTICS
                    ) {
                        unset($this->emails[$key]);
                    }
                } */
            }
        }
    }

    /**
     * Used for sending emails in command.
     */
    public function sendNewEmails()
    {
        global $kernel;
        $configSitecodeName = $kernel->getContainer()->getParameter('sitecode')['site_title'];
        $configSitecodeKey = $kernel->getContainer()->getParameter('sitecode')['site_key'];
        $configSitecodeId = $kernel->getContainer()->getParameter('sitecode')['site_id'];
        $noEmailDelievery = $kernel->getContainer()->getParameter('no_email_delievery');
        $sitecodeDomains = $this->sitecodeService->getSitecodeDomains();

        if (count($this->emails)) {
            /** @var Email $emailEntity */
            foreach ($this->emails as $emailEntity) {
                $request = $this->getMailRequest();

                // SET TO
                $toSeller = $emailEntity->getToSeller();
                $toName = $toSeller ? $toSeller->getCompanyName() : null;
                $toEmail = $emailEntity->getEmailTo();

                $sitecodeId = $configSitecodeId;
                $emailSitecodeName = $configSitecodeName;
                $sitecode = $emailEntity->getSitecode();

                if (is_object($sitecode)) {
                    $sitecodeId = $sitecode->getId();
                    $emailSitecodeName = ($sitecode->getSitecode() == Sitecodes::SITECODE_KEY_AUTOTRADER) ? Sitecodes::SITECODE_TITLE_AUTOTRADER : ucfirst($sitecode->getSitecode());
                }

                $request->setTo($toEmail, $toName);
                $emailFromName = $emailSitecodeName;

                // SET THE FROM ADDRESS
                if ($emailEntity->getEmailType() == Email::EMAIL_TYPE_SIMILAR_OFFERS_ALERT) {
                    $emailFrom = $emailEntity->getEmailFrom();
                } else {
                    $domain = $sitecodeDomains[$sitecodeId];
                    $emailFrom = $emailEntity->getNotificationEmailAddress($domain);
                }

                if ($emailEntity->getEmailType() == Email::EMAIL_TYPE_FORM_EMAIL_TO_SELLER ||
                    $emailEntity->getEmailType() == Email::EMAIL_TYPE_CALLBACK_TO_SELLER) {
                    if ($toSeller->getParentSellerId()) {
                        if ($toSeller->getParentSellerId()->getSellerContactEmail()) {
                            $request->setBcc($toSeller->getParentSellerId()->getSellerContactEmail());
                        }
                    }

                    if (! empty($emailEntity->getReplyTo())) {
                        $tradusUser = $this->getTradusUser($emailEntity->getReplyTo());
                        if (! empty($tradusUser)) {
                            if (! empty($tradusUser->getFullName())) {
                                $emailFromName = $tradusUser->getFullName().' on '.$emailSitecodeName;
                            } else {
                                if ($sitecodeId == 3) {
                                    $pos = strpos($emailEntity->getReplyTo(), '@');
                                    if ($pos) {
                                        $emailFromName = substr($emailEntity->getReplyTo(), 0, $pos).' on '.$emailSitecodeName;
                                    }
                                }
                            }
                        }
                    }
                }

                if ($sitecodeId == 3) {
                    if ($emailEntity->getEmailType() == Email::EMAIL_TYPE_FORM_EMAIL_TO_BUYER ||
                        $emailEntity->getEmailType() == Email::EMAIL_TYPE_FORM_EMAIL_RESPONSE_BUYER ||
                        $emailEntity->getEmailType() == Email::EMAIL_TYPE_FORM_EMAIL_RESPONSE) {
                        $tradusSeller = $this->getTradusSeller($emailEntity->getReplyTo());
                        if (! empty($tradusSeller)) {
                            if (! empty($tradusSeller->getCompanyName())) {
                                $emailFromName = $tradusSeller->getCompanyName().'  on '.$emailSitecodeName;
                            }
                        } elseif (! empty($emailEntity->getReplyTo())) {
                            $tradusUser = $this->getTradusUser($emailEntity->getReplyTo());
                            if (! empty($tradusUser)
                                        && ! empty($tradusUser->getFullName())) {
                                $emailFromName = $tradusUser->getFullName().' on '.$emailSitecodeName;
                            } else {
                                $pos = strpos($emailEntity->getReplyTo(), '@');
                                if ($pos) {
                                    $emailFromName = substr($emailEntity->getReplyTo(), 0, $pos).' on '.$emailSitecodeName;
                                }
                            }
                        }
                    }
                }

                if ($emailEntity->getEmailType() == Email::EMAIL_TYPE_PERFORMANCE_TO_SELLER) {
                    $emailFromName = Sitecodes::SITECODE_TRADUS_PRO;
                }

                $request->setFrom($emailFrom, $emailFromName);
                $request->setReplyTo($emailFrom);

                // SET BCC
                if ($this->enviroment != 'dev' && $this->config) {
                    $bccList = $this->config->getSettingValue('emails.bcc.list');
                    if ($bccList && count($bccList)) {
                        foreach ($bccList as $bcc) {
                            $request->setBcc(key($bcc), $bcc[key($bcc)]);
                        }
                    }
                }

                if ($emailEntity->getEmailType() == Email::EMAIL_TYPE_WEEKLY_SEARCH_ANALYTICS) {
                    $bccList = $this->config->getSettingValue('weekly.analytics.email.bcc');
                    if ($bccList && count($bccList)) {
                        foreach ($bccList as $bccKey => $bccValue) {
                            $request->setBcc($bccKey, $bccValue);
                        }
                    }
                }

                // SUBJECT
                if (! $subject = $emailEntity->getSubject()) {
                    $subject = 'RE: ';
                }
                $request->setSubject($subject);

                // BODY
                if (! $body = $emailEntity->getBody()) {
                    $body = $emailEntity->getMessage();
                }
                if ($body) {
                    $request->setContent($body);
                }

                $attachments = $emailEntity->getAttachments();
                foreach ($attachments as $attachment) {
                    $request->setAttachmentsFromString(
                        $attachment->getFileContent(),
                        $attachment->getFileName(),
                        $attachment->getFileType()
                    );
                }

                // SET CATEGORY IF DEFINED
                if ($emailEntity->getCategoryName() !== false) {
                    $request->addCategory($emailEntity->getCategoryName());
                }

                $transportWheelsError = false;
                if ($emailEntity->getEmailType() == Email::EMAIL_TYPE_CONTACT_TRANSPORT_WHEELS) {
                    $quoteService = new ShippingQuoteService($this->entityManager);
                    $result = $quoteService->getShippingQuote($emailEntity);

                    if ($result) {
                        $request->setFrom($result['from'], $result['name']);
                        $request->setReplyTo($result['from']);
                        $request->setContent($emailEntity->getBody());
                        $request->setAttachmentsFromString(
                            base64_encode($result['xml_data']),
                            $configSitecodeKey.'.com_'.date('Ymd_His').'.xml',
                            'application/xml'
                        );
                    } else {
                        $transportWheelsError = true;
                        $this->countSendFailed++;
                    }
                }

                try {
                    if (! $transportWheelsError && ! in_array($sitecodeId, $noEmailDelievery)) {
                        $this->sendMail($request);
                    } else {
                        $status = $transportWheelsError ? Email::STATUS_ERROR : '10'.$sitecodeId;
                        if ($emailEntity) {
                            $emailEntity->setStatus($status);
                            $this->entityManager->persist($emailEntity);
                            $this->entityManager->flush();
                        }
                    }
                } catch (Exception $e) {
                    // When 5xx code then sendgrid is offline
                    if (substr($e->getCode(), 0, 1) != 5) {
                        // Some content wrong
                        if ($emailEntity) {
                            $emailEntity->setStatus(Email::STATUS_ERROR);
                            $this->entityManager->persist($emailEntity);
                            $this->entityManager->flush();
                        }
                    }
                    continue;
                }

                if (! $transportWheelsError) {
                    if ($request->getMessageId()) {
                        $emailEntity->setMessageId($request->getMessageId());
                        $emailEntity->setStatus(Email::STATUS_SENT);
                        $emailEntity->setSentAt(new DateTime());
                        $this->entityManager->persist($emailEntity);
                        $this->entityManager->flush();
                    }
                }
            }
        }
    }

    /**
     * Create curl handle for a request.
     *
     * @param $endpoint
     * @return resource
     */
    protected function createHandle($endpoint)
    {
        $url = $endpoint;
        $method = 'POST';
        $timeout = 20;

        $handler = curl_init();
        curl_setopt($handler, CURLOPT_URL, $url);
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handler, CURLOPT_ENCODING, '');
        curl_setopt($handler, CURLOPT_MAXREDIRS, 5);
        curl_setopt($handler, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($handler, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($handler, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($handler, CURLOPT_HEADER, 1); // We want to retrieve header, contains X-Message-Id
        curl_setopt($handler, CURLOPT_HTTPHEADER, $this->sendGridHeader);

        if ($method == 'POST') {
            curl_setopt($handler, CURLOPT_POST, true);
        } elseif ($method == 'GET') {
            curl_setopt($handler, CURLOPT_HTTPGET, true);
        } elseif ($method == 'HEAD') {
            curl_setopt($handler, CURLOPT_CUSTOMREQUEST, 'HEAD');
        } else {
            throw new InvalidArgumentException("unsupported method: $method");
        }

        return $handler;
    }

    /**
     * @param $data
     * @return array
     */
    protected function send($data)
    {
        curl_setopt($this->handle, CURLOPT_POSTFIELDS, $data);
        $httpResponse = curl_exec($this->handle);

        return $this->getResponse($this->handle, $httpResponse);
    }

    /**
     * @param RequestSendGrid $request
     * @return array|bool
     * @throws Exception
     */
    public function sendMail(RequestSendGrid $request)
    {
        if (getenv('MAIL_RESTRICTED')) {
            $to = $request->getTo();

            if (! isset($to[0]['email'])) {
                throw new Exception('To email is missing');
            }

            if (! strpos($to[0]['email'], '@olx.com') && ! strpos($to[0]['email'], '@sunfra.in')) {
                // throw new \Exception('Email: '.$to[0]['email'].' is not authorized to send to');
                return false;
            }
        }

        $data = $request->createPayload();
        if ($data !== false) {
            $response = $this->send($data);
            $request->setResponse($response);

            return $response;
        }

        return false;
    }

    /**
     * Get the response for a curl handle.
     *
     * @param resource $handle
     * @param string   $httpResponse
     * @return array
     */
    protected function getResponse($handle, $httpResponse)
    {
        if ($httpResponse !== false && $httpResponse !== null) {
            $data = $httpResponse;
            $headers = curl_getinfo($handle);
            $header = substr($data, 0, $headers['header_size']);
            $body = substr($data, -$headers['download_content_length']);
            $headers['status'] = 'HTTP/1.1 '.$headers['http_code'].' OK';

            $headerArray = explode("\r\n", $header);
            if ($headerArray) {
                foreach ($headerArray as $headerValue) {
                    $value = explode(': ', $headerValue);
                    if (isset($value[1])) {
                        $headers[$value[0]] = $value[1];
                    }
                }
            }
        } else {
            $headers = [];
            $data = '';
            $body = '';
        }
        $this->check($data, $headers, $handle);

        return ['headers' => $headers, 'data' => $data, 'body' => $body];
    }

    /**
     * Check result of a request.
     *
     * @param string   $data
     * @param array    $headers
     * @param resource $handle
     * @throws HttpException
     */
    protected function check($data, $headers, $handle)
    {
        // if there is no data and there are no headers it's a total failure,
        // a connection to the host was impossible.
        if (empty($data) && count($headers) == 0) {
            $this->countSendFailed++;
            throw new HttpException(500, 'HTTP request failed, '.curl_error($handle));
        }
        if (substr($headers['http_code'], 0, 1) != 2) {
            $this->countSendFailed++;
            throw new HttpException($headers['http_code'], 'HTTP request failed, '.curl_error($handle));
        }

        $this->countSendSucces++;
    }

    /**
     * @return int
     */
    public function getCountSuccesSend()
    {
        return $this->countSendSucces;
    }

    /**
     * @return int
     */
    public function getCountFailedSend()
    {
        return $this->countSendFailed;
    }

    /**
     * @param array $alertError
     * @return array|bool
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function sendAlertCronErrorEmail(array $alertError)
    {
        global $kernel;
        $body = $this->twig->render(
            '@templates/email/similar-listings/alert-cron-error-notification.html.twig',
            [
                'errorList' => $alertError,
                'urlDomain' => $kernel->getContainer()->getParameter('sitecode')['domain'],
                'utmSource' => ConfigService::UTM_SOURCE,
                'utmMedium' => ConfigService::UTM_MEDIUM,
                'utmLogoCampaign' => 'alert-cron-error-notification',
            ]
        );

        $subject = 'tradus-jobs - Alert send all cron error list';
        $bccList = AlertRuleInterface::ALERT_CRON_ERROR_BCC;

        $mail = $this->getMailRequest();
        $mail->setSubject($subject);
        $mail->setFrom(
            $kernel->getContainer()->getParameter('sitecode')['emails']['support_email'],
            $kernel->getContainer()->getParameter('sitecode')['site_title']
        );
        $mail->setContent($body);
        $mail->setTo(AlertRuleInterface::ALERT_CRON_ERROR_TO);

        foreach ($bccList as $bcc) {
            $mail->setBcc($bcc);
        }

        return $this->sendMail($mail);
    }

    /**
     * Send report abuse emails.
     *
     * @param $emails
     * @throws Exception
     */
    public function sendReportAbuseEmails($emails)
    {
        global $kernel;
        $emailFrom = $kernel->getContainer()->getParameter('sitecode')['emails']['alerts_email'];
        $emailAbuse = $kernel->getContainer()->getParameter('sitecode')['emails']['abuse_email'];
        $emailSitecodeName = $kernel->getContainer()->getParameter('sitecode')['site_title'];

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->entityManager->getRepository('TradusBundle:Configuration');
        $reportAbuse = $configuration->findOneBy(['name' => 'report.abuseReasons']);
        $reportAbuseArray = $reportAbuse->getValue();
        $reportAbuseReasons = reset($reportAbuseArray);
        /** @var ReportAbuse $email */
        foreach ($emails as $email) {
            $data = [];
            $mail = $this->getMailRequest();
            $mail->setTo($emailAbuse, $emailSitecodeName.' Zendesk');
            $mail->setFrom($emailFrom, $emailSitecodeName);

            // Create and set subject
            $subject = 'Ad ID: '.$email->getOffer().' '.$reportAbuseReasons[$email->getReason()];
            $mail->setSubject($subject);

            // Create body array for email
            $data[$emailSitecodeName.' AD ID: '] = $email->getOffer();
            $data[$emailSitecodeName.' AD URL: '] =
                '<a href="'.$email->getOfferUrl().'">'.$email->getOfferUrl().'</a>';
            $data['Abuse reason: '] = $reportAbuseReasons[$email->getReason()];
            $data['Reporter message: '] = $email->getMessage();
            $createdAt = $email->getCreatedAt()->format('Y-m-d h:i:s');
            $data['Timestamp: '] = $createdAt;
            $data['User id: '] = $email->getUserId();
            $data['Reporter email: '] = $email->getEmail() ?: '';
            $data['Registered user: '] = $email->getUserId() ? 'Y' : 'N';
            $confirmedUser = 'N';
            if ($email->getUserId()) {
                /** @var TradusUser $user */
                $user = $this->entityManager->getRepository('TradusBundle:TradusUser')
                    ->findOneBy(['id' => $email->getUserId()]);
                $confirmedUser = ($user->getStatus() == TradusUser::STATUS_ACTIVE) ? 'Y' : 'N';
            }
            $data['Confirmed user email: '] = $confirmedUser;
            $data['User IP: '] = $email->getIp();
            $data['User agent: '] = $email->getUserAgent();
            $data['Language: '] = $email->getLocale();

            $body = $this->twig->render(
                '@templates/email/report-abuse/report-abuse.html.twig',
                ['data' => $data, 'hideBadges' => true]
            );

            $mail->setContent($body);
            $this->sendMail($mail);

            // It's either a doctrine problem or i am a bit stupid
            // This was the only way to update the record.
            // If i just persisted the existing email it would create a new record
            /** @var ReportAbuse $newEmail */
            $newEmail = $this->entityManager->getRepository('TradusBundle:ReportAbuse')
                ->findOneBy(['id' => $email->getId()]);
            $newEmail->setSentAt(new DateTime());
            $this->entityManager->persist($newEmail);
            $this->entityManager->flush();
        }
    }
}
