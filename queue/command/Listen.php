<?php

namespace queue\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use queue\lib\Listener;

class Listen extends Command
{
	/**
	 * @var Listener
	 */
    protected $listener;

    public function configure()
    {
        $this->setName('queue:listen')
            ->addOption('memory', null, Option::VALUE_OPTIONAL, 'The memory limit in megabytes', 128)
            ->addOption('tries', null, Option::VALUE_OPTIONAL, 'Number of times to attempt a job before logging it failed', 0)
            ->setDescription('Listen to a given queue');
    }

    public function initialize(Input $input, Output $output)
    {
        $this->listener = new Listener($this->findCommandPath());
        $this->listener->setMaxTries($input->getOption('tries'));
        $this->listener->setOutputHandler(function ($type, $line) use ($output) {
            $output->write($line);
        });
    }

    public function execute(Input $input, Output $output)
    {
        $this->listener->listen($input->getOption('memory'));
    }
    
    protected function findCommandPath()
    {
        return defined('ROOT_PATH') ? ROOT_PATH : dirname($_SERVER['argv'][0]);
    }
}
