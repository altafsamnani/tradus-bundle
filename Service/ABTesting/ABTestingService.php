<?php

namespace TradusBundle\Service\ABTesting;

/**
 * Class ABTestingService.
 */
class ABTestingService
{
    private $aPercentage;
    private $userId;
    private $userSessionId;
    public const TRACKING_SESSION_ID_NAME = 'monthly_track_id';
    public const TRACKING_IMAGE_ORDERING = 'image_ordering_version';

    public function __construct(int $aPercentage, int $userId, string $userSessionId)
    {
        $this->aPercentage = $aPercentage;
        $this->userId = $userId;
        $this->userSessionId = $userSessionId;
    }

    /**
     * Generate the A or B option based on the percentages.
     * Note: the LOWER percentage the more "option B" will be,
     * For example 100% will always go to "option A".
     * @return string
     */
    public function generate()
    {
        if ($this->userId > 0) {
            $hash = md5($this->userId);
        } else {
            $hash = md5($this->userSessionId);
        }

        $number = hexdec(substr($hash, 0, 4));
        $version = $number % 100;

        if ($version < $this->aPercentage) {
            return 'a';
        }

        return 'b';
    }
}
