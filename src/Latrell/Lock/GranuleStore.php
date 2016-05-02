<?php
namespace Latrell\Lock;

use Closure;

abstract class GranuleStore
{

	public function granule($key, Closure $callback)
	{
		try {
			if ($this->acquire($key)) {
				$callback();
			} else {
				throw new \RuntimeException("acquire lock key {$key} timeout!");
			}
		} finally {
			$this->release($key);
		}
	}

    /**
     * [synchronized alias of function granule]
     */
    public function synchronized($key, Closure $callback) {
        return $this->granule($key, $callback);
    }
}
