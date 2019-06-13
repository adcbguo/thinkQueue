<?php

namespace queue;

use queue\lib\Mq;

/**
 * 队列操作类
 * @package queue
 */
class Queue {

	/**
	 * 入列
	 * @param string $job 两种方式  1:消费类名@消费类方法 2:直接消费类名(默认执行消费类fire方法)
	 * @param array $data 发到消费类的数据
	 * @param int $attempts
	 * @param array $mq
	 * @return bool|string
	 * @throws \ReflectionException
	 */
	public static function push(string $job, array $data = [], $attempts = 1, array $mq = []) {
		$job_id = uniqid();
		$data = [
			'job_id' => $job_id,
			'job' => $job,
			'ip' => get_client_ip(0, true),
			'url' => request()->url(),
			'time' => time(),
			'data' => $data,
			'attempts' => $attempts
		];
		$data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		if (!Mq::make()->push($data)) return false;
		return $job_id;
	}
}
