<?php

namespace TradusBundle\Service\Contract;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use TradusBundle\Entity\Anonymize;

/**
 * Interface AnonymizeServiceInterface.
 */
interface AnonymizeServiceInterface
{
    /**
     * @param int $userId
     *
     * @return Anonymize
     * @throws UnprocessableEntityHttpException
     */
    public function anonymizeUser(int $userId, string $country): Anonymize;
}
