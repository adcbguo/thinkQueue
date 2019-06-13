<?php

namespace queue\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use queue\lib\Worker;
use Throwable;

class Work extends Command {

	/**
	 * 工作实例
	 * @var Worker
	 */
	protected $worker;

	protected function initialize(Input $input, Output $output) {
		$this->worker = new Worker();
	}

	protected function configure() {
		$this->setName('queue:work')
			->addOption('memory', null, Option::VALUE_OPTIONAL, 'The memory limit in megabytes', 128)
			->addOption('tries', null, Option::VALUE_OPTIONAL, 'Number of times to attempt a job before logging it failed', 0)
			->setDescription('Process the next job on a queue');
	}

	/**
	 * 执行命令
	 * @param Input $input
	 * @param Output $output
	 * @return int|null|void
	 * @throws Throwable
	 */
	public function execute(Input $input, Output $output) {
		$this->worker->pop($input->getOption('tries'),$input->getOption('memory'));
	}
}
