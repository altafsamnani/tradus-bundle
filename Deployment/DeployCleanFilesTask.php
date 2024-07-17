<?php

namespace TradusBundle\Deployment;

use Mage\Task\AbstractTask;
use Symfony\Component\Process\Process;

/**
 * Symfony Task - DeployCleanFilesTask.
 */
class DeployCleanFilesTask extends AbstractTask
{
    public function getName()
    {
        return 'deploy/clean-files';
    }

    public function getDescription()
    {
        return '[Tradus] Clean ._ files';
    }

    public function execute()
    {
        $options = $this->getOptions();
        $command = sprintf('%s deploy:clean_files', $options['console']);
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
