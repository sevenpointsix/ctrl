<?php

	namespace App\Ctrl;

	class CtrlModules {
		
		protected $enabled_modules = [
			'test'
		];

		/**
		 * A test function that demonstrates how modules work - including, calling functions on the main controller
		 * @param  string $string A string
		 * @return string         "Test function $string"
		 */
		protected function test($string = '') {		
			$string_from_main_controller = app('\Sevenpointsix\Ctrl\Http\Controllers\CtrlController')->testing();
			return 'Test function: '.$string.', '.$string_from_main_controller;
		}

		/**
		 * Check that module $module_name is enabled
		 * @param  string $module_name The module name
		 * @return [type]              [description]
		 */
		public function enabled($module_name) {
			return in_array($module_name, $this->enabled_modules);
		}

		/**
		 * Run a module
		 * This is how modules are called from the primary CtrlController; we check that the module is enabled
		 * and then run the function, passing in each item of the arguments array as a separate argument
		 * @param  string $module_name The name of the module
		 * @param  array  $arguments   any arguments required
		 * @return [type]              The return value of the module; could be anything.
		 */
		public function run($module_name, $arguments = []) {
			if (!$this->enabled($module_name)) return; // We should always have checked enabled() first, I think
			return call_user_func_array(array($this,$module_name),$arguments);
		}



	}
