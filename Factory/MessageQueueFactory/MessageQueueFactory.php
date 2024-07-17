<?php

namespace TradusBundle\Factory\MessageQueueFactory;

class MessageQueueFactory implements MessageQueueFactoryInterface
{
    private $manager;

    /**
     * MessageQueueFactory constructor.
     * @param string $queueingSystem
     */
    public function __construct(string $queueingSystem)
    {
        switch ($queueingSystem) {
            /*
             * For the moment we only have one queue system so this code is basically useless
             * Hopefully we can add another queue system or replace the existing one in the future
             */
            case GearmanManager::NAME:
            default:
                $this->manager = new GearmanManager();
                break;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param string $queue
     * @param array $data
     */
    public function publish(string $queue, array $data): void
    {
        $this->manager->publish($queue, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function consume(string $queue, $callback): array
    {
        return $this->manager->consume($queue, $callback);
    }
}
