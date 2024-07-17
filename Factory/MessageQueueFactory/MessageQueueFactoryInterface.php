<?php

namespace TradusBundle\Factory\MessageQueueFactory;

use Closure;

interface MessageQueueFactoryInterface
{
    /**
     * Publish a message to a given queue
     * The message should always be an array for uniformization.
     *
     * @param string $queue
     * @param array $data
     */
    public function publish(string $queue, array $data): void;

    /**
     * Runs the custom callback on the message array.
     *
     * @param string $queue
     * @param Closure $callback
     * @return array
     */
    public function consume(string $queue, $callback): array;
}
