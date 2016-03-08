<?php
namespace Latrell\Lock;

use Illuminate\Redis\Database as Redis;
use Carbon\Carbon;
use RuntimeException;

class RedisStore extends GranuleStore implements LockInterface
{

	/**
	 * The Redis database connection.
	 *
	 * @var \Illuminate\Redis\Database
	 */
	protected $redis;

	/**
	 * A string that should be prepended to keys.
	 *
	 * @var string
	 */
	protected $prefix;

	/**
	 * The Redis connection that should be used.
	 *
	 * @var string
	 */
	protected $connection;

	/**
	 * 锁超时时间（秒）
	 */
	protected $timeout;

	/**
	 * 上锁最大超时时间（秒）
	 */
	protected $max_timeout;

	/**
	 * 重试等待时间（微秒）
	 */
	protected $retry_wait_usec;

	/**
	 * 锁识别码
	 */
	protected $identifier;

	/**
	 * 锁的到期时间列表
	 */
	protected $expires_at = [];

	/**
	 * Create a new Redis store.
	 *
	 * @param  \Illuminate\Redis\Database  $redis
	 * @param  string  $prefix
	 * @param  string  $connection
	 * @return void
	 */
	public function __construct(Redis $redis, $prefix = '', $connection = 'default', $timeout = 30, $max_timeout = 300, $retry_wait_usec = 100000)
	{
		$this->redis = $redis;
		$this->setPrefix($prefix);
		$this->connection = $connection;
		$this->timeout = $timeout;
		$this->max_timeout = $max_timeout;
		$this->retry_wait_usec = $retry_wait_usec;
		$this->identifier = md5(uniqid(gethostname(), true));
	}

	/**
	 * 上锁
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function acquire($name)
	{
		$key = $this->getKey($name);

		$time = time();
		while (time() - $time < $this->max_timeout) {
			$value = $this->getLockExpirationValue();

			if ($this->redis->setnx($key, $value)) {
				// 加锁成功。
				$this->acquired($name);
				return true;
			}

			// 未能加锁成功。
			// 检查当前锁是否已过期，并重新锁定。
			$current_value = $this->redis->get($key);

			// 检查当前锁是否到期。
			if (! is_null($current_value) && $this->hasLockValueExpired($current_value)) {

				// 回收锁。
				$getset_result = $this->redis->getset($key, $value);

				if (! is_null($getset_result) && $this->hasLockValueExpired($getset_result)) {
					// 回收锁成功。
					$this->acquired($name);
					return true;
				}
			}
			usleep($this->retry_wait_usec);
		}
		return false;
	}

	/**
	 * 记录该锁的到期时间
	 */
	protected function acquired($name)
	{
		$this->expires_at[$name] = Carbon::now()->addSeconds($this->timeout);
	}

	/**
	 * 解锁
	 *
	 * @param string $key
	 */
	public function release($name)
	{
		$key = $this->getKey($name);

		if (! $this->isLocked($name)) {
			throw new RuntimeException('Attempting to release a lock that is not held');
		}

		$value = $this->redis->get($key);
		unset($this->expires_at[$name]); // 释放内存占用。
		if (! $this->hasLockValueExpired($value)) {
			$this->redis->del($key); // 释放锁。
		} else {
			trigger_error(sprintf('A PredisLock was not released before the timeout. Class: %s Lock Name: %s', get_class($this), $name), E_USER_WARNING);
		}
	}

	/**
	 * 我们有一个锁？
	 */
	protected function isLocked($name)
	{
		return key_exists($name, $this->expires_at);
	}

	/**
	 * 取得用于该锁的Key。
	 */
	protected function getKey($name)
	{
		return $this->prefix . $name;
	}

	/**
	 * 取得锁的值。
	 * 添加到期时间与识别码。
	 *
	 * @return string
	 */
	protected function getLockExpirationValue()
	{
		return serialize([
			'identifier' => $this->identifier,
			'expires_at' => Carbon::now()->addSeconds($this->timeout)
		]);
	}

	/**
	 * 确定一个锁已过期。
	 *
	 * @param string 锁的值
	 * @return boolean
	 */
	protected function hasLockValueExpired($value)
	{
		$data = @unserialize($value);
		if (! $data) {
			return true;
		}
		return Carbon::now() > $data['expires_at'];
	}

	/**
	 * 清理过期的死锁
	 *
	 * @return integer 清理的死锁数量
	 */
	public function clear()
	{
		$keys = $this->redis->keys($this->getKey('*'));
		$num = 0;
		foreach ($keys as $key) {
			$value = $this->redis->get($key);
			$data = @unserialize($value);
			if ($data && Carbon::now()->addSeconds($this->max_timeout) < $data['expires_at']) {
				$this->redis->del($key);
				$num ++;
			}
		}
		return $num;
	}

	/**
	 * Get the Redis connection instance.
	 *
	 * @return \Predis\ClientInterface
	 */
	public function connection()
	{
		return $this->redis->connection($this->connection);
	}

	/**
	 * Set the connection name to be used.
	 *
	 * @param  string  $connection
	 * @return void
	 */
	public function setConnection($connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Get the Redis database instance.
	 *
	 * @return \Illuminate\Redis\Database
	 */
	public function getRedis()
	{
		return $this->redis;
	}

	/**
	 * Get the lock key prefix.
	 *
	 * @return string
	 */
	public function getPrefix()
	{
		return $this->prefix;
	}

	/**
	 * Set the lock key prefix.
	 *
	 * @param  string  $prefix
	 * @return void
	 */
	public function setPrefix($prefix)
	{
		$this->prefix = ! empty($prefix) ? $prefix . ':' : '';
	}
}
