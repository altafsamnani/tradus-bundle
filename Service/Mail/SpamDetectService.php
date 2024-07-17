<?php

namespace TradusBundle\Service\Mail;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use EmailChecker\EmailChecker;
use Exception;
use TradusBundle\Entity\Email;
use TradusBundle\Entity\SpamEmail;
use TradusBundle\Entity\SpamUser;
use TradusBundle\Repository\EmailRepository;
use TradusBundle\Repository\SpamUserRepository;
use TradusBundle\Service\Sitecode\SitecodeService;

/**
 * Class SpamDetectService.
 *
 * @category Email_Helper
 * @author   Tradus <dev@tradus.com>
 * @license  www.tradus.com Tradus
 * @link     www.tradus.com
 */
class SpamDetectService
{
    public const MAX_DAILY_EMAILS = 10;

    public const DIFFERENCE_BETWEEN = 5;

    public const MIN_CHARACTERS = 5;

    /**
     * Entity manager.
     *
     * @var ObjectManager|object
     */
    protected $entityManager;

    protected $userMessage;

    protected $userEmail;

    protected $content;

    protected $questions = [];

    protected $saveSpammer = true;

    /**
     * The user was manually checked and determined that it's not a spam user
     * All the emails from this user should be sent.
     *
     * @var bool
     */
    protected $checkedSpamUser = false;

    /**
     * SpamDetectService constructor.
     *
     * @param Registry $em          entity manager
     * @param string   $userEmail   the user email
     * @param string   $userMessage the user message
     * @param mixed    $questions   Lead form questions
     */
    public function __construct(Registry $em, string $userEmail, ?string $userMessage = null, $questions = null, $content = null)
    {
        $this->entityManager = $em->getManager();
        $this->userMessage = $userMessage;
        $this->userEmail = $userEmail;
        $this->content = $content;
        if ($questions) {
            $this->questions = $questions;
        }
    }

    /**
     * Based on some predefined rules determine if the email is spam.
     *
     * @return bool
     */
    public function isSpam()
    {
        if ($this->validateSpamUserName()) {
            return true;
        }

        if ($this->isKnownSpamUser()) {
            $this->saveSpammer = false;

            return ! $this->checkedSpamUser;
        }

        if ($this->isDisposableEmail()) {
            return true;
        }

        if ($this->isGmailTrick()) {
            return true;
        }

        if ($this->userMessage) {
            if (! $this->hasRequiredLength()) {
                return true;
            }

            if ($this->containLinks()) {
                return true;
            }
        }

        /* if ($this->isImmediatelySent()) {
            return true;
        } */

        if ($this->sentManyMessages()) {
            return true;
        }

        return false;
    }

    /**
     * Check Upper and Lower letters more in full_name.
     *
     * @return bool
     */
    protected function validateSpamUserName()
    {
        $fullName = $this->content['full_name'];

        preg_match_all('/([[:lower:]]+[[:upper:]]+[[:lower:]]+)+|([[:upper:]]+[[:lower:]]+[[:upper:]]+)+/u', $fullName, $matchesFirst);
        $countFirst = count($matchesFirst[0]);

        if ($countFirst) {
            return true;
        }

        return false;
    }

    /**
     * Check if message has the minimum required length.
     *
     * @return bool
     */
    protected function hasRequiredLength()
    {
        if (strlen($this->userMessage) <= self::MIN_CHARACTERS && empty($this->questions)) {
            return false;
        }

        return true;
    }

    /**
     * Search for links inside the text.
     *
     * @return bool
     */
    protected function containLinks()
    {
        $reg_exUrl
            = '/(((http|https|ftp|ftps)\:\/\/)|(www\.))[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\:[0-9]+)?(\/\S*)?/';

        if (preg_match($reg_exUrl, $this->userMessage)) {
            return true;
        }

        return false;
    }

    /**
     * Checks user email against known disposable email domains.
     *
     * @return bool
     */
    protected function isDisposableEmail()
    {
        $checker = new EmailChecker();

        return ! $checker->isValid($this->userEmail);
    }

    /**
     * Checks for gmail address trick (add '+' inside email).
     *
     * @return bool
     */
    protected function isGmailTrick()
    {
        if (strpos($this->userEmail, '+')) {
            return true;
        }

        return false;
    }

    /**
     * Checks to see if another message was sent less than x seconds ago.
     *
     * @return bool
     * @throws Exception
     */
    protected function isImmediatelySent()
    {
        $entityManager = $this->entityManager;
        /** @var EmailRepository $emailRepository */
        $emailRepository = $entityManager->getRepository('TradusBundle:Email');
        $email = $emailRepository->findOneBy(
            ['reply_To' => $this->userEmail],
            ['created_at' => 'desc']
        );

        if (! $email) {
            return false;
        }

        $emailDate = $email->getCreatedAt();
        $emailDateFormatted = $emailDate->format('Y-m-d H:i:s');
        $now = new DateTime();
        $nowFormatted = $now->format('Y-m-d H:i:s');
        $difference = strtotime($nowFormatted)
            - strtotime($emailDateFormatted);

        if ($difference < self::DIFFERENCE_BETWEEN) {
            return true;
        }

        return false;
    }

    /**
     * Check if user send more emails than the daily limit.
     *
     * @return bool
     */
    protected function sentManyMessages()
    {
        $entityManager = $this->entityManager;
        $emailRepository = $entityManager->getRepository('TradusBundle:Email');
        $emailsSentToday = $emailRepository->countUserDailyEmails($this->userEmail);

        if ($emailsSentToday >= self::MAX_DAILY_EMAILS) {
            return true;
        }

        return false;
    }

    /**
     * Check spam user table for a known spammer.
     *
     * @return bool
     */
    protected function isKnownSpamUser()
    {
        $entityManager = $this->entityManager;
        /**
         * Spam User Repository.
         *
         * @var SpamUserRepository
         */
        $spamUserRepository = $entityManager->getRepository('TradusBundle:SpamUser');
        $spamUser = $spamUserRepository->findOneByEmail($this->userEmail);
        if (! $spamUser) {
            return false;
        }

        $this->saveSpammer = false;
        if ($spamUser->getStatus() == SpamUser::STATUS_INACTIVE) {
            $this->checkedSpamUser = true;

            return false;
        }

        return true;
    }

    /**
     * Save email information in the spam email table.
     *
     * @param array $data email information
     *
     * @return void
     */
    public function saveSpamEmail(array $data)
    {
        $scs = new SitecodeService();
        $sitecodeId = $scs->getSitecodeId();

        $entityManager = $this->entityManager;
        $sitecode = $entityManager->getRepository('TradusBundle:Sitecodes')->find($sitecodeId);

        $spamEmail = new SpamEmail();
        $spamEmail->setEmailFrom($data['from']);
        $spamEmail->setEmailTo($data['to']);
        $spamEmail->setSubject($data['subject']);
        $spamEmail->setMessage($data['message']);
        $spamEmail->setBody($data['body']);
        $spamEmail->setStatus(Email::STATUS_PENDING);
        $spamEmail->setToSeller($data['seller']);
        $spamEmail->setOffer($data['offer']);
        $spamEmail->setEmailTemplate($data['email_template']);
        $spamEmail->setReplyTo($data['reply_To']);
        $spamEmail->setEmailType($data['email_type']);
        $spamEmail->setPredefinedQuestion($data['predefinedQuestion']);
        $spamEmail->setSitecode($sitecode);

        if (isset($data['user_id'])) {
            $spamEmail->setUserId($data['user_id']);
        }

        if (isset($data['ip'])) {
            $spamEmail->setIp($data['ip']);
        }

        $em = $this->entityManager;
        $em->persist($spamEmail);
        $em->flush();
    }

    /**
     * Save spammer email address in separate table.
     *
     * @param string $email spammer email address
     * @param null $ip ip address
     *
     * @return void
     * @throws Exception
     */
    public function saveSpamUser(string $email, $ip = null)
    {
        if (! $this->saveSpammer) {
            return;
        }
        $spamUser = new SpamUser();
        $spamUser->setEmail($email);
        $spamUser->setStatus(SpamUser::STATUS_ACTIVE);
        $spamUser->setDateAdded(new DateTime());
        if ($ip) {
            $spamUser->setIp($ip);
        }

        $em = $this->entityManager;
        $em->persist($spamUser);
        $em->flush();
    }
}
