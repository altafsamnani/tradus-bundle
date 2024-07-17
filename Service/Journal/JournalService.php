<?php

namespace TradusBundle\Service\Journal;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use TradusBundle\Entity\Journal;
use TradusBundle\Entity\Sitecodes;
use TradusBundle\Service\Sitecode\SitecodeService;

/**
 * Class JournalService.
 */
class JournalService
{
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param string $action
     * @param string $agent
     * @param string $title
     * @param string $description
     * @param int $user_id
     * @param int $resource
     * @param int $client
     * @param int $execution_time
     * @param int $seller_id
     * @return bool
     * @throws UnprocessableEntityHttpException
     */
    public function setJournal(
        $action,
        $agent,
        $title = null,
        $description = null,
        $user_id = null,
        $resource = null,
        $client = null,
        $execution_time = null,
        $seller_id = null,
        $ip = null
    ) {
        if (empty($action)) {
            throw new UnprocessableEntityHttpException('Action field can not be empty');
        }

        if (empty($agent)) {
            return false;
        }

        if (empty($title)) {
            $title = $action;
        }

        if (empty($description)) {
            $description = $action;
        }

        $em = $this->em;
        $user = $em->getRepository('TradusBundle:TradusUser')->find($user_id);

        $seller = null;
        if ($seller_id && is_numeric($seller_id)) {
            $seller = $em->getRepository('TradusBundle:Seller')->find($seller_id);
        }

        if (! $user) {
            return false;
        }

        $scs = new SitecodeService();
        $sitecodeId = $scs->getSitecodeId();
        if (! $sitecodeId) {
            $sitecodeId = Sitecodes::SITECODE_TRADUS;
        }
        $sitecode = $em->getRepository('TradusBundle:Sitecodes')->find($sitecodeId);

        $journal = new Journal();
        $journal->setAction($action);
        $journal->setTitle($title);
        $journal->setDescription($description);
        $journal->setUser($user);
        $journal->setSeller($seller);
        $journal->setResource($resource);
        $journal->setAgent($agent);
        $journal->setClient($client);
        $journal->setExecutionTime($execution_time);
        $journal->setSitecode($sitecode);
        if ($ip) {
            $journal->setIp($ip);
        }
        $em->persist($journal);
        $em->flush();

        return true;
    }
}
