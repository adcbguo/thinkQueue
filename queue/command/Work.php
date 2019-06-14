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
			->addOption('exchange', 'e', Option::VALUE_REQUIRED, 'exchange', 128)
			->addOption('queue', 'q', Option::VALUE_REQUIRED, 'queue', 128)
			->addOption('memory', 'm', Option::VALUE_OPTIONAL, 'memory', 128)
			->addOption('tries', 't', Option::VALUE_OPTIONAL, 'tries', 1)
			->setDescription('Process queue');
	}

	/**
	 * 执行命令
	 * @param Input $input
	 * @param Output $output
	 * @return int|null|void
	 * @throws Throwable
	 */
	public function execute(Input $input, Output $output) {
		$exchange = $input->getOption('exchange');
		$queue = $input->getOption('queue');
		$memory = $input->getOption('memory');
		$tries = $input->getOption('tries');
		$this->worker->pop($tries, $memory, ['exchange' => $exchange, 'queue' => $queue]);
	}
}
