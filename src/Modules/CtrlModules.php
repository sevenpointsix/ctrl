<?php

	namespace App\Ctrl;

	use \Sevenpointsix\Ctrl\Models\CtrlClass;
	use \Sevenpointsix\Ctrl\Models\CtrlProperty;

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

		// Is this the best place to store "empty" functions?
		// This is all TBC, I'd like to enable/disable modules using an artisan command really
		
		/**
		 * Allow the form fields array to be manipulated; this should allow us to add, remove or modify fields
		 * @param  array The existing $tabbed_form_fields array, in this format;
		 *                   [
		 *                   	TAB_NAME => [
		 *                   		'icon'=>'',
		 *                   		'text'=>'',
		 *                   		'form_fields'=>[
		 *                   			'id'       => '',
		 *								'name'     => '',
		 *								'values'   => [],
		 *								'value'    => '', // May an array, for relationships / multiple selects etc
		 *								'type'     => '',
		 *								'template' => '',
		 *								'label'    => '',
		 *								'tip'      => '',
		 *                   		]
		 *                   	]	
		 *                   ]
		 * @param  integer $ctrl_class_id The ID of the class we're editing
		 * @param  integer $object_id The ID of the object we're editing (zero if we're creating a new one)
		 * @param  string $filter_string Optional list filter; such as 43,1, which will set the value of the ctrl_property 43 to 1 when we save the form
		 * @return array The new form_fields array
		 */
		protected function manipulate_form($tabbed_form_fields, $ctrl_class_id, $object_id, $filter_string) {
			return $tabbed_form_fields;
		}

		/**
		 * Manipulate an object once it's been saved	
		 * @param  Request  $request	 
		 * @param  integer $object The object we're saving
		 * @param  string $filter_string Optional list filter; such as 43,1, which will set the value of the ctrl_property 43 to 1 when we save the object
		 * @return
		 */
		protected function post_save($request, $object, $filter_string) {

		}

		/**
		 * Import objects from a CSV file
		 * @param  string $action        There are various things that this function can do; count rows, check headers, import data and so on.
		 * @param  collection $results The results of the CSV import, as returned by Maatwebsite\Excel
		 * @param  int $ctrlclass_id  The ID of the CtrlClass we're importing
		 * @param  string $filter_string Any filters we've applied to the list before importing (not currently used)
		 * @return various				Cn be a boolean (for success/failure), an integer (for a row count), or a string (for a description of a result). Depends on context.
		 */
		protected function import_objects($action,$results, $ctrl_class_id,$filter_string = NULL) {
			// Argos has a good example of this function in use
			$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();

			// Configure requred_headers, define the callback function
			switch ($ctrl_class->name) {
				case '[CLASS_NAME]':
					$required_headers = ['HEADER_1','HEADER_3','HEADER_3'];						

					$import_callback = function($results) {
						$count = 0;
						foreach ($results as $result) {
							$count++;							
						}	
						return $count;
					};
					
					break;
				default:
					return false; // Can't import this class

			}

			// We should really move all this code back into the main CtrlController:

			// Have we defined any required headers? If so, and we're counting/checking them, return boolean as necessary
			if (in_array($action, ['count-headers','check-headers']) && !empty($required_headers)) {

				// Convert all headers into slugged values, as per http://www.maatwebsite.nl/laravel-excel/docs/import#results
				// Technically this uses the protected function Excel::getSluggedIndex()
				// but it's essentially the same as Laravel's str_slug():
				$required_headers = array_map('str_slug',$required_headers,
					array_fill(0,count($required_headers),'_')
					// This passes an '_' parameter to str_slug;
					// see http://stackoverflow.com/questions/8745447/array-map-function-in-php-with-parameter
				);

				$first_row = $results->first()->toArray();   	
			    $csv_headers   = array_keys($first_row);
			    
				if ($action == 'count-headers' && count($csv_headers) != count($required_headers)) {
					return false;
				}
				else  if ($action == 'check-headers' && $csv_headers != $required_headers) {
					return false;
				}
				else {
					return true;
				}
			}
			// Or, have we defined a callback function?
			else if ($action == 'import') {
				return $import_callback($results);
			}
		}


	}
