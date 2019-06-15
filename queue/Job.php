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
	private $name;

	/**
	 * 处理的数据
	 * @var array
	 */
	protected $data = [];

	/**
	 * 是否释放
	 * @var bool
	 */
	protected $is_release = false;

	/**
	 * @param string $name 队列名称
	 * @param int $attempts 执行次数
	 * @param array $data 执行数据
	 */
	public function __construct(string $name = '', int $attempts = 0, array $data = []) {
		$this->attempts = $attempts;
		$this->name = $name;
		$this->data = $data;
	}

	/**
	 * 队列回归
	 * @param array $config
	 * @return string
	 * @throws \ReflectionException
	 */
	public function release($config) {
		return Queue::push($this->name, $this->data, $this->attempts + 1, $config);
	}

	/**
	 * 设置是否释放
	 * @param bool $is_release
	 * @return $this
	 */
	public function setRelease(bool $is_release){
		$this->is_release = $is_release;
		return $this;
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

	/**
	 * 默认的消耗类
	 * @return mixed
	 */
	abstract public function fire();

	/**
	 * 记录队列错误
	 * @param string $msg 错误描述
	 * @param array $trace 错误trace
	 * @param array $body 执行的数据
	 * @param array $mq 来自于来个队列
	 * @return bool
	 */
	abstract public function failed(string $msg, array $trace, array $body, array $mq);
}
