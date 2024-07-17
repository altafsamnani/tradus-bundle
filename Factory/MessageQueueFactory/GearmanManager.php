<?php

namespace TradusBundle\Factory\MessageQueueFactory;

use GearmanClient;
use GearmanWorker;

class GearmanManager implements MessageQueueFactoryInterface
{
    public const NAME = 'gearman';
    public const TIMEOUT = 60000;

    /** @var array $consumerData */
    public $consumerData = [];

    /**
     * {@inheritdoc}
     */
    public function publish(string $queue, array $data): void
    {
        $client = new GearmanClient();
        $client->addServer(self::NAME);
        $client->doBackground($queue, json_encode($data));
    }

    /**
     * {@inheritdoc}
     */
    public function consume(string $queue, $callback): array
    {
        $callback->bindTo($this);
        $worker = new GearmanWorker();

        $worker->addServer(self::NAME);
        $worker->addFunction($queue, function ($job) use ($callback) {
            $content = $job->workload();
            $data = json_decode($content, true);
            $callback($data);
        });
        $worker->setTimeout(self::TIMEOUT);
        $count = 0;
        while (@$worker->work()) {
            $count++;
            if ($count > 1000) {
                break;
            }
        }
        $worker->unregisterAll();

        return $this->consumerData;
    }
}
