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
		 * Manipulate an object before it's been saved
		 * @param  Request  $request
		 * @param  integer $object The object we're saving
		 * @param  string $filter_string Optional list filter; such as 43,1, which will set the value of the ctrl_property 43 to 1 when we save the object
		 * @return
		 */
		protected function pre_save($request, $object, $filter_string) {

		}

		/**
		 * Import objects from a CSV file
		 * @param  string $action        There are various things that this function can do; count rows, check headers, import data and so on.
		 * @param  collection $results The results of the CSV import, as returned by Maatwebsite\Excel
		 * @param  int $ctrlclass_id  The ID of the CtrlClass we're importing
		 * @param  string $filter_string Any filters we've applied to the list before importing (not currently used)
		 * @return various				Cn be a boolean (for success/failure), an integer (for a row count), or a string (for a description of a result). Depends on context.
		 */
		protected function import_objects($action,$results, $ctrl_class_id,$filter_string = NULL, $csv_file) {
			// Argos has a good example of this function in use
			$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();

			// Configure requred_headers, define the callback function
			switch ($ctrl_class->name) {
				case '[CLASS_NAME]':
					$required_headers = ['HEADER_1','HEADER_3','HEADER_3'];

					$pre_import_function = function($ctrl_class_id, $filter_string,$csv_file) {
						// For example, we might want to truncate the table here, or even bypass the Excel:: import altogether (as we do for Argos)
					};

					$callback_function = function($results) {
						$count = 0;
						foreach ($results as $result) {
							$count++;
						}
						return $count;
					};
					$import_type = 'data'; // Defaults to this anyway; can also be "files" or "images" for bulk file uploads
					break;
				default:
					return false; // Can't import this class

			}

			if ($action == 'get-headers') {
				return $headers;
			}
			else if ($action == 'get-callback-function') {
				return $callback_function;
			}
			else if ($action == 'get-pre-import-function') {
				return (!empty($pre_import_function)) ? $pre_import_function : false;
			}
			else if ($action == 'get-import-type') {
				return (!empty($import_type)) ? $import_type : 'data'; // Can also be files, images
			}
			else {
				dd("Unrecognised action $action");
			}
		}


		/**
		 * Add custom columns to the DataTable
		 * @param  integer $ctrl_class_id The ID of the class we're editing
		 * @return various				 Various; can be an array of headers, a function definition, and so on. Depends on context.
		 */
		protected function custom_columns($ctrl_class_id) {
			$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();

			$columns = [];

			// Configure requred_headers, define the callback function
			switch ($ctrl_class->name) {
				case '[CLASS_NAME]':
					$columns['[COLUMN_KEY]'] = [
						'table_heading' => 'COLUMN_TITLE',
						'searchable'    => false, // Do we want to add a search box to this column?
						'raw_sql'		=> "[QUERY]"
							// eg, SELECT count(*) from product_profile_cache WHERE profile_id = faqs.profile_id
					];
				break;
			}

			return $columns;
		}

		/**
		 * Add custom buttons to the DataTable
		 * @param  integer $ctrl_class_id The ID of the class we're listing
		 * @param  integer $object_id The ID of the object we're editing
		 * @param  string $filter_string Optional list filter; such as 43,1, which will set the value of the ctrl_property 43 to 1 when we save the form
     	 * @return array                 An array of buttons, as []['icon'=>'','count'=>'','title'=>'','link'=>'','class'=>'','rel'=>'']
		 */
		protected function custom_buttons($ctrl_class_id, $object_id, $filter_string = null) {

			$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();

			$custom_buttons = [];

			switch ($ctrl_class->name) {
				case 'Test':
					// Fill in some variables here... count, icon, etc.
					$count = 100;
					$custom_button = [
						'icon'  => 'fa fa-share-alt',
						'count' => $count,
						'title' => 'Shared Content',
						'link'  => '#',
						'class' => 'shared-content',
						'rel'   => ''
					];
					$custom_buttons[] = $custom_button;
				break;
			}

			return $custom_buttons;
		}

		/**
		 * Manipulate the DOM of a page; this is a flexible way to tweak a page or add custom content
		 * @param  Request  $request
		 * @param  string $dom The current dom
		 * @param  string $context Explain where this rendered view is actually displayed, eg "dashboard"
		 * @return string $dom The $dom object (which will be rendered directly as a string)
		 */
		protected function manipulate_dom($dom, $context) {

			/* For example...
			if ($context == 'dashboard') {
				$import_export_panel = $dom->find('div[id=import_export_panel]',0);
				$import_export_panel->outertext .= '<p>An extra panel goes here</p>';
			}
			*/

			return $dom;
		}

		/**
		 * Tweak permissions
		 * @param  Request  $request
		 * @param  integer $ctrl_class_id The ID of the class we're editing
		 * @param  $action What we're trying to do (eg, 'add')
		 * @return boolean
		 */
		protected function permissions($ctrl_class_id, $action) {
			// Load the Ctrl Class; could just pass in the object here
			try {
				$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();
			}
			catch (\Exception $e) {
				trigger_error($e->getMessage());
			}
			/* For example...
			if ($ctrl_class->name == 'Product' && $action == 'add') {
				// Are we filtering this list to show cached products for a profile...?
				if ($filter_string = \Request::segment(4)) {
					$filter_values = explode(',', $filter_string);
					if (count($filter_values == 2)) {
						$filter_property = CtrlProperty::where('id',$filter_values[0])->firstOrFail();
						if ($filter_property->name == 'profile_cache') {
							return false;
						}
					}
				}
			}
			*/

			/**
			 * I think we should always fall back to the class permissions:
			 */
			return $ctrl_class->can($action);
		}

		/**
		 * Hide a fieldset
		 * @param  Request  $request
		 * @param  string $tab_name The name of the tab (fieldset)
		 * @return boolean
		 */
		protected function hide_fieldset($tab_name) {

			/* For example...
			$user = \Auth::user();
			if ($user && $user->ctrl_group != 'repairs' && $tab_name == 'Repairs') {
				return true;
			}
			*/

			return false;
		}

		/**
		 * Hide a menu item
		 * @param  Request  $request
		 * @param  object $ctrl_class The CtrlClass of the menu item
		 * @return boolean
		 */
		protected function hide_menu_item($ctrl_class) {

			/* For example...
			$user = \Auth::user();
			if ($user && $user->ctrl_group != 'repairs' && $ctrl_class->menu_title == 'Repairs') {
				return true;
			}
			*/

			return false;
		}

		/**
		 * Get a custom fieldset icon
		 * @param  Request  $request
		 * @param  string $tab_name The name of the tab (fieldset)
		 * @return string The icon class (such as, 'fa fa-list')
		 */
		protected function custom_fieldset_icon($tab_name, $default) {

			/* For example...
			if ($tab_name == 'Repairs') {
				return 'fa fa-wrench';
			}
			*/

			return $default;
		}

		/**
		 * Override the ability to reorder a table (by default, any table with an "order" column can be reordered)
		 * @param  int $ctrlclass_id  The ID of the CtrlClass we're listing
		 * @param  string $filter_string Any filters we've applied to the list
		 * @return boolean true if we don't want to reorder the list
		 */
		protected function prevent_reordering( $ctrl_class_id,$filter_string = NULL) {
			return false;
		}

		/**
		 * Add some custom links above the main set of links; we could theoretically use the manipulate_dom module for this:
		 * @return array an array of links, see below
		 */
		protected function custom_dashboard_links() {
			/* Format of array is:
			$menu_links['TITLE'][] = [
				'icon'       => ($icon = $ctrl_class->get_icon()) ? '<i class="'.$icon.' fa-fw"></i> ' : '',
				'add_link'   => $add_link, // optional
				'add_title'  => $add_title,  // optional
				'list_link'  => $list_link,
				'list_title' => $list_title
			];
			*/
			$custom_dashboard_links = [];
			return $custom_dashboard_links;
		}

		/**
		 * Add some custom validation rules
		 * @param  object $ctrl_class The CtrlClass of the menu item
		 * @param  array $validation The current validation rules, as $field_name=>$rules
		 * @param  array $messages Any current error messages, as $field_name.$error=>$error_message (for example, $messages["$field_name.date"] = "The &ldquo;{$ctrl_property->label}&rdquo; field must be a valid date";)
		 * @return array An array consisting of [$validation,$messages]
		 */
		protected function custom_validation($ctrl_class,$validation,$messages) {

			/* For example,
			if ($ctrl_class->name == 'Repairuser') {
				 // Pipes are clumsy here; should we use an array instead? We need to explode the current rules first, as the main CtrlController uses pipes:
				$current_rules = !empty($validation['password']) ? explode('|', $validation['password']) : [];
				$validation['password'][] = 'regex:/^[1-9]{4}$/';
				$messages['password.regex'] = "The &ldquo;password&rdquo; field must consist of four digits, excluding zeros";
			}
			*/

			return [$validation,$messages];
		}

		/**
		 * Customise the get_select2 function for a given ctrl_class; this allows us to filter or manipulate the autocomplete
		 * @param  object $ctrl_class 	The CtrlClass of objects that we're listing for select2
		 * @param  query $query      	The Eloquent query that we're building up
		 * @param  object $editing 	  	The CtrlClass of objects that we're *editing* in the CMS
		 * @param  string $search_term  The search term that we're using, in case that's relevant
		 * @return query              	The Eloquent $query, modified if necessary
		 */
		protected function custom_select2($ctrl_class,$query, $editing, $search_term) {

			/* For example:
			if ($ctrl_class->name == 'Repairuser' && $editing->name == 'Repairmessage') {
				$query->where('repairagent_id',0);
			}
			*/

			return $query;

		}

		/**
		 * Export objects to a CSV file; very similar in some ways to the import module
		 * @param  string $action        There are various things that this function can ask for; headers, callbacks and so on
		 * @return various				 Various; can be an array of headers, a function definition, and so on. Depends on context.
		 */
		protected function export_objects($action, $ctrl_class_id) {

			try {
				$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();
			}
			catch (\Exception $e) {
				trigger_error($e->getMessage());
			}

			switch ($ctrl_class->name) {
				case 'Example':
					$pre_export_function = function($ctrl_class_id) {

						try {
							$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();
						}
						catch (\Exception $e) {
							trigger_error($e->getMessage());
						}

						$data = DB::table('_custom_table')->get();

						// $data here is a collection of objects; convert it to an array of arrays for the Excel export:
						// i.e., [['id'=>1,'title'=>'Item 1'],['id'=>2,'title'=>'Item 2'],...]
						$data = json_decode(json_encode($data), true);

						$filename = 'export-'.str_slug($ctrl_class->get_plural());

						\Maatwebsite\Excel\Facades\Excel::create($filename, function($excel) use ($data) {
						    $excel->sheet('sheet_1', function($sheet) use ($data) {
				        		$sheet->fromArray($data);
				    		});
						})->download('csv');

					};
					break;
				case 'Example2':
					$headers = ['id','title'];
					break;
				default:
					return false;
			}

			if ($action == 'get-headers') {
				return (!empty($headers)) ? $headers : false;
			}
			else if ($action == 'get-pre-export-function') {
				return (!empty($pre_export_function)) ? $pre_export_function : false;
			}
			else {
				dd("Unrecognised action $action");
			}
		}


		/**
		 * Filter the objects returned to DataTables by @get_data
		 * @param  object $ctrl_class 	The CtrlClass of objects that we're listing
		 * @param  query $query      	The Eloquent query that we're building up
		 * @param  string $filter_string Any filters we've applied to the list
		 * @return query              	The Eloquent $query, modified if necessary
		 */
		protected function custom_filter($ctrl_class,$query,$filter_string) {
			/* For example:
			if ($ctrl_class->name == 'Profile' && config('repairs.enabled')) {
				$query->where('title','NOT LIKE',"Repair profile%");
			}
			*/
		}

		/**
		 * Assign global scopes to certain models; good for filtering by certain users
		 * @param  object $ctrl_class 	The CtrlClass of objects that we're listing
		 * @return string The contents of the globalScope function using NowDoc syntax
		 */
		protected function globalScope($ctrl_class) {
			/* For example:
			$scope = <<<'EOD'
					$user = \Auth::user();
					if ($user && $user->ctrl_group == 'site') {
	                	$builder->where('user_id',$user->id);
	            	}
EOD;
			return $scope;
			*/
		}

	}
