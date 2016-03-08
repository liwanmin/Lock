<?php
namespace Latrell\Lock;

use Illuminate\Support\ServiceProvider;
use Latrell\Lock\Console\ClearCommand;

class LockServiceProvider extends ServiceProvider
{

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('lock', function ($app) {
			return new LockManager($app);
		});

		$this->app->singleton('lock.store', function ($app) {
			return $app['lock']->driver();
		});

		$this->registerCommands();
	}

	/**
	 * Register the lock related console commands.
	 *
	 * @return void
	 */
	public function registerCommands()
	{
		$this->app->singleton('command.lock.clear', function ($app) {
			return new ClearCommand($app['lock']);
		});

		$this->commands('command.lock.clear');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [
			'lock',
			'lock.store',
			'command.lock.clear'
		];
	}
}
