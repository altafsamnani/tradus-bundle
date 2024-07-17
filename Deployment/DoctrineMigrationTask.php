<?php

namespace TradusBundle\Deployment;

use Mage\Task\AbstractTask;
use Symfony\Component\Process\Process;

/**
 * Symfony Task - Doctrine Migrations.
 */
class DoctrineMigrationTask extends AbstractTask
{
    public function getName()
    {
        return 'symfony/doctrine-migrations';
    }

    public function getDescription()
    {
        return '[Symfony] Doctrine Migrations';
    }

    public function execute()
    {
        $options = $this->getOptions();
        $command = sprintf('%s doctrine:migrations:migrate --no-interaction --quiet', $options['console']);
        /** @var Process $process */
        $process = $this->runtime->runCommand(trim($command));

        return $process->isSuccessful();
    }

    protected function getOptions()
    {
        $options = array_merge(
            ['console' => 'bin/console'],
            $this->runtime->getMergedOption('symfony'),
            $this->options
        );

        return $options;
    }
}
