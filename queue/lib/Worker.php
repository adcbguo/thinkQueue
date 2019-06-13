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
	 * @throws \ErrorException
	 * @throws \ReflectionException
	 */
	public function pop(int $maxTries = 0, $memory) {
		Mq::make()->monitor(function (AMQPMessage $msg) use ($maxTries, $memory) {

			$body = json_decode($msg->getBody(), true);

			if (empty($body) OR !isset($body['job'])) {
				$this->delete($msg);
				$this->failed("推送队列出错,请检查!", $body);
				return true;
			}

			$segments = explode('@', $body['job']);
			$action = count($segments) > 1 ? $segments[1] : 'fire';

			$job = $this->makeJob($body);

			if (is_object($job)) {
				$this->process($job, $action, $maxTries, $body, $msg);
			} else {
				$this->delete($msg);
				$this->failed("当前队列类:{$body['job']}不存在,请检查!", $body);
				return true;
			}
			if ($this->memoryExceeded($memory)) {
				exit();
			}
		});
	}

	/**
	 * 删除当前一条队列
	 * @param AMQPMessage $msg
	 */
	public function delete(AMQPMessage $msg) {
		$this->channel = $msg->delivery_info['channel'];
		$delivery_tag = $msg->delivery_info['delivery_tag'];
		$this->channel->basic_ack($delivery_tag);
	}

	/**
	 * 解析实例化消费类
	 * @param array $body
	 * @return Job|bool
	 */
	public function makeJob(array $body) {
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
	public function memoryExceeded($memoryLimit) {
		return (memory_get_usage() / 1024 / 1024) >= $memoryLimit;
	}

	/**
	 * 执行队列
	 * @param Job $job
	 * @param string $action
	 * @param int $maxTries
	 * @param array $body
	 * @param AMQPMessage $msg
	 * @throws Throwable
	 */
	public function process(Job $job, string $action, $maxTries, array $body, AMQPMessage $msg) {
		if ($maxTries > 0 && $job->attempts() > $maxTries) {
//			$this->failed("执行不能超过指定次数,当前指定{$maxTries}次!", $body);
		} else {
			try {
				if (!$job->{$action}()){
					$this->failed('执行失败,未返回"true"', $body);
				}
			} catch (PDOException $e) {
				$job->release();
				$this->failed('执行出错,数据库错误:' . json_encode(['error' => $e->getData()]), $body);
			} catch (Exception $e) {
				$job->release();
				$this->failed('执行出错,代码错误:' . json_encode(['error' => $e->getData()]), $body);
			} catch (Throwable $e) {
				$job->release();
				$this->failed('执行出错,异常错误:' . json_encode(['error' => $e->getMessage()]), $body);
			}
		}
		$this->delete($msg);
	}

	/**
	 * @param string $msg
	 * @param array $body
	 * @throws \think\Exception
	 */
	public function failed(string $msg, array $body) {
		Db::connect('mall_log')->name('queue_log')->insert([
			'job' => $body['job'],
			'data' => json_encode($body['data']),
			'msg' => $msg,
			'time' => date('Y-m-d H:i:s'),
		]);
	}
}
