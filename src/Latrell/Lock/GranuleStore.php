<?php
namespace Latrell\Lock;

use Closure;

abstract class GranuleStore
{

	public function granule($key, Closure $callback)
	{
		try {
			$this->acquire($key);
			$callback();
		} finally {
			$this->release($key);
		}
	}
}
