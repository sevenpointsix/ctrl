<?php

namespace	Sevenpointsix\Ctrl;

/**
 * 
 * @author Chris Gibson <chris@sevenpointsix.com>
 * Heavily based on https://github.com/jaiwalker/setup-laravel5-package
 */

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

use File;

class CtrlServiceProvider extends ServiceProvider{

	// Dummy comment to force a commit

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	// Add the artisan command; from http://stackoverflow.com/questions/28492394/laravel-5-creating-artisan-command-for-packages. See @register()
	protected $commands = [
       \Sevenpointsix\Ctrl\Commands\CtrlSynch::class
    ];

	public function boot()
	{

		// If we run `artisan vendor publish --force`, we can overwrite config files;
		// this is user error (most likely, my user error), but it's a major cock-up so let's catch it
		if (\App::runningInConsole()) {			
			$args = $_SERVER['argv'];
			if (!empty($args)) {
				// Are we attempting to run `artisan vendor publish --force`, without the public tag?
				if (
					in_array('artisan', $args)
					&& in_array('vendor:publish', $args)
					&& (
						!in_array('--tag=public', $args)
						||
						in_array('--tag=config', $args)
					)
					&& in_array('--force', $args)
				) {
					// Require a `--ctrl` flag in order to force a `vendor publish`
					if (!in_array('--ctrl', $args)) {
						$message = [
							'Running `artisan vendor publish --force` will override CTRL config files!',
							'If you really wish to do this, please add the flag `--ctrl`.',
							'Otherwise, to publish CSS files only, use the argument `--tag=public`.'
						];
						$maxlen = max(array_map('strlen', $message)); // Nice, http://stackoverflow.com/questions/1762191/how-to-get-the-length-of-longest-string-in-an-array
						$divider = str_repeat('*',$maxlen);
						array_unshift($message, $divider);
						array_push($message, $divider);
						echo "\n".implode("\n", $message)."\n\n";
						exit();
					}
				}
			}
		}

		/* Can I put this here? Just check that we have a Ctrl folder, for models and Modules */
		$ctrl_folder = app_path('Ctrl/');
        if(!File::exists($ctrl_folder)) {
            File::makeDirectory($ctrl_folder,0777,true); // See http://laravel-recipes.com/recipes/147/creating-a-directory
        }

		$this->loadViewsFrom(realpath(__DIR__.'/../views'), 'ctrl');
		$this->setupRoutes($this->app->router);

		// This allows the config file to be published using artisan vendor:publish
		$this->publishes([
				__DIR__.'/config/ctrl.php' => config_path('ctrl.php'),
		], 'config'); // See https://laravel.com/docs/5.0/packages#publishing-file-groups

		// Make sure we have a CtrlModules file:
		$this->publishes([
	        __DIR__.'/Modules/CtrlModules.php' => $ctrl_folder.'/CtrlModules.php',
	    ],'config');

		// This copies our assets folder into the public folder for easy access, again using artisan vendor:publish
		$this->publishes([
	        realpath(__DIR__.'/../assets') => public_path('assets/vendor/ctrl'),
	        	// We could potentially just use 'vendor/ctrl'; check best practice here.
	    ], 'public');



	}

	/**
	 * Define the routes for the application.
	 *
	 * @param  \Illuminate\Routing\Router  $router
	 * @return void
	 */
	public function setupRoutes(Router $router)
	{
		$router->group(['namespace' => 'Sevenpointsix\Ctrl\Http\Controllers'], function($router)
		{
			require __DIR__.'/Http/routes.php';
		});		
	}


	public function register()
	{
		$this->registerCtrl();
		config([
				'config/ctrl.php',
		]);

		// Register the DataTables service like this (saves having to add it to config/app.php)
		\App::register('Yajra\Datatables\DatatablesServiceProvider');

		// Excel module used when importing, exporting CSV data
		\App::register('Maatwebsite\Excel\ExcelServiceProvider');
		// This didn't seem to work, and isn't needed if we "use Maatwebsite\Excel\Facades\Excel;" at the top of the controller
		// \App::alias('Excel','Maatwebsite\Excel\Facades\Excel');

		// Can we create a custom Service Provider here to drive "modules"?
		/* Don't think so
		\App::register('App\Ctrl\Providers\CtrlModuleServiceProvider');
		*/

	}

	private function registerCtrl()
	{
		$this->commands($this->commands);
		$this->app->bind('ctrl',function($app){
			return new Ctrl($app);
		});

	}
}
