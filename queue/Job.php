<?php

namespace queue;

abstract class Job {

	/**
	 * 执行次数
	 * @var int
	 */
	private $attempts;

	/**
	 * 队列名称
	 * @var string
	 */
	private $name = '';

	/**
	 * 处理的数据
	 * @var array
	 */
	protected $data = [];

	/**
	 * @param string $name 队列名称
	 * @param int $attempts 执行次数
	 * @param array $data 执行数据
	 */
	public function __construct(string $name='', int $attempts=0, array $data=[]) {
		$this->attempts = $attempts;
		$this->name = $name;
		$this->data = $data;
	}

	/**
	 * 队列回归
	 * @throws \ReflectionException
	 */
	public function release() {
		Queue::push($this->name, $this->data, $this->attempts + 1);
	}

	/**
	 * 获取执行的消费类
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * 获取执行次数
	 * @return int
	 */
	public function attempts() {
		return $this->attempts;
	}

	abstract public function fire();
}
