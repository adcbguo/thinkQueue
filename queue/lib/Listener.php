<?php

namespace queue\lib;

use Closure;
use think\Process;

class Listener {

	/**
	 * 命令目录
	 * @var string
	 */
	protected $commandPath;

	/**
	 * 尝试次数
	 * @var int
	 */
	protected $maxTries = 3;

	/**
	 * 工作进程命令
	 * @var string
	 */
	protected $workerCommand;

	/**
	 * 输出句柄
	 * @var \Closure|null
	 */
	protected $outputHandler;

	/**
	 * @param  string $commandPath
	 */
	public function __construct($commandPath) {
		$this->commandPath = $commandPath;
		$this->workerCommand = '"' . PHP_BINARY . '" think queue:work --tries=%s --memory=%s';
	}

	/**
	 * 守护进程监听
	 * @param  string $memory
	 * @return void
	 */
	public function listen($memory) {
		$process = $this->makeProcess($memory);

		while (true) {
			$this->runProcess($process, $memory);
		}
	}

	/**
	 * 执行消费进程
	 * @param \Think\Process $process
	 * @param  int $memory
	 */
	public function runProcess(Process $process, $memory) {
		$process->run(function ($type, $line) {
			$this->handleWorkerOutput($type, $line);
		});

		if ($this->memoryExceeded($memory)) {
			$this->stop();
		}
	}

	/**
	 * 创建消费子进程
	 * @return \think\Process
	 */
	public function makeProcess($memory) {
		$string = $this->workerCommand;
		$command = sprintf($string, $this->maxTries, $memory);
		return new Process($command, $this->commandPath, null, null, 0);
	}

	/**
	 * 输出句柄
	 * @param  int $type
	 * @param  string $line
	 * @return void
	 */
	protected function handleWorkerOutput($type, $line) {
		if (isset($this->outputHandler)) {
			call_user_func($this->outputHandler, $type, $line);
		}
	}

	/**
	 * 允许最大内存
	 * @param  int $memoryLimit
	 * @return bool
	 */
	public function memoryExceeded($memoryLimit) {
		return (memory_get_usage() / 1024 / 1024) >= $memoryLimit;
	}

	/**
	 * 停止退出
	 * @return void
	 */
	public function stop() {
		die;
	}

	/**
	 * 设置输出句柄
	 * @param  \Closure $outputHandler
	 * @return void
	 */
	public function setOutputHandler(Closure $outputHandler) {
		$this->outputHandler = $outputHandler;
	}

	/**
	 * 设置尝试次数
	 * @param  int $tries
	 * @return void
	 */
	public function setMaxTries($tries) {
		$this->maxTries = $tries;
	}
}
