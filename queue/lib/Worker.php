<?php

namespace queue\lib;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use queue\Job;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use Throwable;

class Worker {

	/**
	 * 队列管道
	 * @var AMQPChannel
	 */
	private $channel;

	/**
	 * 处理消息队列
	 * @param int $maxTries
	 * @param int $memory
	 * @param array $config
	 * @throws \ErrorException
	 * @throws \ReflectionException
	 */
	public function pop(int $maxTries = 1, int $memory = 128, array $config = []) {
		Mq::make($config)->monitor(function (AMQPMessage $msg) use ($maxTries, $memory, $config) {

			$body = json_decode($msg->getBody(), true);

			//没有队列对象删除消息
			if (empty($body) OR !isset($body['job'])) $this->delete($msg);

			//实例化消耗类
			$job = $this->makeJob($body);

			//有实例化对象执行方法
			if (is_object($job)) {
				$this->process($job, $this->parseJob($body, 'action'), $maxTries, $body, $msg, $config);
			} else {
				$this->delete($msg);
			}

			//内存超出杀死进程
			if ($this->memoryExceeded($memory)) {
				exit();
			}
		});
	}

	/**
	 * 删除当前一条队列
	 * @param AMQPMessage $msg
	 */
	private function delete(AMQPMessage $msg) {
		$this->channel = $msg->delivery_info['channel'];
		$delivery_tag = $msg->delivery_info['delivery_tag'];
		$this->channel->basic_ack($delivery_tag);
	}

	/**
	 * 解析队列类和消耗方法
	 * @param array $body
	 * @param string $type
	 * @return array|string
	 */
	private function parseJob(array $body, string $type = '') {
		$segments = explode('@', $body['job']);

		//没有消耗方法默认fire
		$jobs = ['job' => $segments[0], 'action' => count($segments) > 1 ? $segments[1] : 'fire'];

		return isset($jobs[$type]) ? $jobs[$type] : $jobs;
	}

	/**
	 * 解析实例化消费类
	 * @param array $body
	 * @return Job|bool
	 */
	private function makeJob(array $body) {
		list($job) = explode('@', $body['job']);

		if (class_exists($job)) {
			$job = new $job($body['job'], $body['attempts'], $body['data']);
			if ($job instanceof Job) return $job;
		}

		return false;
	}

	/**
	 * 允许最大内存
	 * @param  int $memoryLimit
	 * @return bool
	 */
	private function memoryExceeded($memoryLimit) {
		return (memory_get_usage() / 1024 / 1024) >= $memoryLimit;
	}

	/**
	 * 执行队列
	 * @param Job $job
	 * @param string $action
	 * @param int $maxTries
	 * @param array $body
	 * @param AMQPMessage $msg
	 * @param array $config
	 * @throws Throwable
	 */
	private function process(Job $job, string $action, $maxTries, array $body, AMQPMessage $msg, array $config) {

		//不能执行超过最大次数
		if ($maxTries > 0 && $job->attempts() > $maxTries) {
			$job->setRelease(true)->failed("执行不能超过{$maxTries}次", [], $body, $config);
			$this->delete($msg);
			return;
		}

		//执行消耗方法
		try {
			if (!$job->{$action}()) $job->failed('执行后未返回"true"', [], $body, $config);
		} catch (PDOException $e) {
			$job->failed('执行数据库操作错误', $e->getTrace(), $body, $config);
		} catch (Exception $e) {
			$job->failed('执行代码异常2', $e->getTrace(), $body, $config);
		} catch (Throwable $e) {
			$job->failed('执行代码异常1', $e->getTrace(), $body, $config);
		}

		//执行后删除
		$this->delete($msg);
	}
}
