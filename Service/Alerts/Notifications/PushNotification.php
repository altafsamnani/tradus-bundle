<?php

namespace TradusBundle\Service\Alerts\Notifications;

class PushNotification
{
    /** @var array */
    protected $data = [];

    public function __construct(
        string $userId,
        string $title,
        string $body,
        string $buttonText,
        string $url,
        string $sound = 'tractor'
    ) {
        $this->data = [
            'userId' => $userId,
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'sound' => $sound,
            'button' => $buttonText,
        ];
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
