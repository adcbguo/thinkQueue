<?php

namespace queue\lib;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use \Closure;
use Throwable;
use PhpAmqpLib\Exception\AMQPIOException;

/**
 * MQ通信封装
 * @package extend
 */
class Mq {

	/**
	 * 配置项
	 * @var array
	 */
	private $options = [
		'host' => '',
		'port' => '5672',
		'user' => '',
		'password' => '',
		'vhost' => '/',
		'insist' => false,
		'login_method' => 'AMQPLAIN',
		'login_response' => null,
		'locale' => 'en_US',
		'connection_timeout' => 3.0,
		'read_write_timeout' => 130.0,
		'context' => null,
		'keepalive' => true,
		'heartbeat' => 60,
		'channel_rpc_timeout' => 0.0,
		'ssl_protocol' => null,
		'exchange' => 'mall',
		'queue' => 'mall/test',
	];

	/**
	 * 当前实例
	 * @var static
	 */
	private static $instance;

	/**
	 * @var AMQPStreamConnection
	 */
	public $handler;

	/**
	 * 构造函数
	 * Mq constructor.
	 * @param array $options
	 * @throws \ReflectionException
	 */
	public function __construct(array $options = []) {

		$this->options = array_merge($this->options, config('RabbitMQ'));

		if (!empty($options)) {
			$this->options = array_merge($this->options, $options);
		}

		try {
			//创建一个链接
			$this->handler = (new \ReflectionClass('PhpAmqpLib\Connection\AMQPStreamConnection'))->newInstanceArgs($this->options);
		} catch (AMQPIOException $exception) {
			logMsgDB("队列服务器不在线:" . json_encode($options));
			exit(json_encode(['code' => 400, 'data' => new  \stdClass(), 'msg' => '队列服务器不在线!']));
		}
	}

	public function __destruct() {
		if (isset($this->handler)) $this->handler->close();
	}

	/**
	 * 单进程单实例
	 * @param array $options
	 * @return Mq|static
	 * @throws \ReflectionException
	 */
	public static function make(array $options = []) {
		if (!is_object(self::$instance)) {
			self::$instance = new static($options);
		}
		return self::$instance;
	}

	/**
	 * 消息入列
	 * @param string $msg
	 * @return bool
	 */
	public function push(string $msg) {
		try {
			$channel = $this->handler->channel();
			$channel->exchange_declare($this->options['exchange'], 'fanout', false, true, false);
			$message = new AMQPMessage($msg, ['content_type' => 'text/plain']);
			$channel->basic_publish($message, $this->options['exchange']);
			$channel->close();
			return true;
		} catch (Throwable $e) {
			return false;
		}
	}

	/**
	 * 监听队列
	 * @param Closure $callback
	 * @throws \ErrorException
	 */
	public function monitor(Closure $callback) {
		$channel = $this->handler->channel();
		$channel->queue_declare($this->options['exchange'], false, true, false, false);
		$channel->basic_consume($this->options['queue'], '', false, false, false, false, function ($message) use ($callback) {
			$callback($message);
		});
		while (count($channel->callbacks)) {
			$channel->wait();
		}
		$channel->close();
	}
}