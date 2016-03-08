<?php
namespace Latrell\Lock;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Filesystem\Filesystem;

class FileStore extends GranuleStore implements LockInterface
{

	/**
	 * The Illuminate Filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * The file lock directory.
	 *
	 * @var string
	 */
	protected $directory;

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
	 * Create a new file lock store instance.
	 *
	 * @param  \Illuminate\Filesystem\Filesystem  $files
	 * @param  string  $directory
	 * @return void
	 */
	public function __construct(Filesystem $files, $directory, $timeout = 30, $max_timeout = 300, $retry_wait_usec = 100000)
	{
		$this->files = $files;
		$this->directory = $directory;
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
	public function acquire($key)
	{
		// TODO: 还没有实现。
	}

	/**
	 * 解锁
	 * @param unknown $key
	 */
	public function release($key)
	{
		// TODO: 还没有实现。
	}

	/**
	 * 清理过期的死锁
	 *
	 * @return integer 清理的死锁数量
	 */
	public function clear()
	{
		// TODO: 还没有实现。
		return 0;
	}

	/**
	 * Get the Filesystem instance.
	 *
	 * @return \Illuminate\Filesystem\Filesystem
	 */
	public function getFilesystem()
	{
		return $this->files;
	}

	/**
	 * Get the working directory of the lock.
	 *
	 * @return string
	 */
	public function getDirectory()
	{
		return $this->directory;
	}

	/**
	 * Get the lock key prefix.
	 *
	 * @return string
	 */
	public function getPrefix()
	{
		return '';
	}
}
