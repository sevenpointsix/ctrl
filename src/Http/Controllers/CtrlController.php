<?php namespace Sevenpointsix\Ctrl\Http\Controllers;
/**
 * 
 * @author Chris Gibson <chris@sevenpointsix.com>
 * Heavily based on https://github.com/jaiwalker/setup-laravel5-package
 */


use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;

use Auth;
use Redirect;
use Illuminate\Http\Request;
use Route; // Is there a better approach to checking the currentRouteName()?
use Validator;
use URL;
use DB;
use View;
use File;
use Storage;
use Log;
use Schema;

use Datatables;
use Maatwebsite\Excel\Facades\Excel;

use \App\Ctrl\CtrlModules;
use \Sevenpointsix\Ctrl\Models\CtrlClass;
use \Sevenpointsix\Ctrl\Models\CtrlProperty;

class CtrlController extends Controller
{

	protected $ctrlModules; // Based on http://stackoverflow.com/a/30373386/1463965
	
	public function __construct(CtrlModules $ctrlModules) {
		// Note that we don't need to call the parent __construct() here

		$this->module = $ctrlModules;
		/* We can now run modules:
			dd($this->modules->run('test',[
				'string' => 'Hello world!'
			]));
		}
		*/

		// Build the menu
		$ctrl_classes = CtrlClass::where('menu_title','!=','')
					 	->orderBy('menu_title', 'ASC')
					 	->orderBy('order')
					 	->get();
			
		$menu_links        = [];
		foreach ($ctrl_classes as $ctrl_class) {

			$count_ctrl_class = $ctrl_class->get_class();

			if (!class_exists($count_ctrl_class)) {
				die("Error: cannot load class files.<br><br><code style='border: 1px solid #999; padding: 5px 10px;'>php artisan ctrl:synch files</code>");
			}

			$count = $count_ctrl_class::count();

			$add_link  = route('ctrl::edit_object',$ctrl_class->id);
			$add_title = 'Add '.$this->a_an($ctrl_class->get_singular()).' '.$ctrl_class->get_singular();

			if ($count > 0) {
				$list_link  = route('ctrl::list_objects',$ctrl_class->id);
				$list_title = 'View '.$count.' '.($count == 1 ? $ctrl_class->get_singular() : $ctrl_class->get_plural());	
			}
			else {
				$list_link  = false;
				$list_title = 'No '.$ctrl_class->get_plural();	
			}
			

			$menu_links[$ctrl_class->menu_title][] = [
				'id'         => $ctrl_class->id,
				'title'      => ucwords($ctrl_class->get_plural()),
				'icon'       => ($icon = $ctrl_class->get_icon()) ? '<i class="'.$icon.' fa-fw"></i> ' : '',
				'icon_only'  => ($icon = $ctrl_class->get_icon()) ? $icon : '',
				'add_link'   => $add_link,
				'add_title'  => $add_title,
				'list_link'  => $list_link,
				'list_title' => $list_title
			];		
		}

		View::share ( 'menu_links', $menu_links );


		$this->_check_login(); // Check that the user is logged in, if necessary
	}

	/**
	 * Descrive the current $filter in human terms, to be used as the page title
	 * @param  array $filter The filter arrray
	 * @return string A description of the filter that makes sense
	 */
	public function describe_filter($filter_string = NULL) {
		$return = '';
		if (!empty($filter_string)) {
			$description = array();
			foreach ($filter_string as $filter) {
				$ctrl_property = CtrlProperty::where('id',$filter['ctrl_property_id'])->firstOrFail(); // This throws a 404 if not found; not sure that's strictly what we want
				// We only handle 'belongsTo' filters at the moment
				if ($ctrl_property->relationship_type == 'belongsTo') {
					$related_ctrl_class = CtrlClass::where('id',$ctrl_property->related_to_id)->firstOrFail(); // As above
					$related_class      = $related_ctrl_class->get_class();
					$related_object     = $related_class::where('id',$filter['value'])->firstOrFail();

					$description[] = "belonging to the ".strtolower($related_ctrl_class->name) ." <strong><a href=".route('ctrl::edit_object',[$related_ctrl_class->id,$related_object->id]).">".$this->get_object_title($related_object)."</a></strong>";
				}
			}
			$return = $this->comma_and($description);
		}
		return $return;
	}

	protected function _check_login() {



		$public_routes = ['ctrl::login','ctrl::post_login'];
		$user          = Auth::user();
		
		$is_public_route = in_array(Route::currentRouteName(),$public_routes);
		$logged_in       = $user && $user->ctrl_group != '';

		if (!$is_public_route && !$logged_in) {
			// The user is required to log in to see this page					
			Redirect::to(route('ctrl::login'))->send();
		}
		else {
			// The user doesn't need to be logged in to see this page				
			// Note that we redirect logged in users AWAY from /login (etc) in the actual controller method (eg @login)
		}
	}

	/**
	 * Show the dashboard to the user. 
	 *
	 * @return Response
	 */
	public function dashboard()
	{
		// Can we import, export any classes
		$ctrl_classes = CtrlClass::whereRaw(
			   '(find_in_set(?, permissions))',
			   ['import']		   
			)->orWhereRaw(
			   '(find_in_set(?, permissions))',
			   ['export']		   
			)->get();		
			
		$import_export_links = [];
		foreach ($ctrl_classes as $ctrl_class) {

			$import_link = $export_link = false;

			if ($ctrl_class->can('export')) {
				$export_link  = route('ctrl::export_objects',[$ctrl_class->id]); // This omits the filter string; will we ever use this? Possible from an existing (filtered) list...
			}
			
			if ($ctrl_class->can('import')) {
				$import_link  = route('ctrl::import_objects',[$ctrl_class->id]); // As above, this omits the filter string; will we ever use this?
			}			

			$import_export_links[] = [
				'id'          => $ctrl_class->id,
				'title'       => ucwords($ctrl_class->get_plural()),
				'icon'        => ($icon = $ctrl_class->get_icon()) ? '<i class="'.$icon.' fa-fw"></i> ' : '',
				'icon_only'   => ($icon = $ctrl_class->get_icon()) ? $icon : '',
				'export_link' => (!empty($export_link)) ? $export_link : false,
				'import_link' => (!empty($import_link)) ? $import_link : false
			];		
		}

		return view('ctrl::dashboard',[			
			'logo'                => config('ctrl.logo'),
			'layout_version'      => 3, // As I play around with layouts...
			'import_export_links' => $import_export_links
		]);
	}


	/**
	 * Generate JSON data to populate a select2 box using Ajax
	 * @param  string $related_ctrl_class The name of the Ctrl class defining the related objects we're loading
	 * @return json                       JSON data with id, text pairs
	 */
	public function get_select2(Request $request,$ctrl_class_name) {

		$json = [];

		$search_term = $request->input('q');

		// This is based heavily on get_typeahead
		$ctrl_class = CtrlClass::where('name',$ctrl_class_name)->firstOrFail();		
		$class      = $ctrl_class->get_class();

		// What are the searchable columns?
		$searchable_properties = $ctrl_class->ctrl_properties()->whereRaw(
		   '(find_in_set(?, flags))',
		   ['search']		   
		)->whereNull('relationship_type')->get();
			// I have no idea how to include searchable related columns in the query builder below...		

		if (!$searchable_properties->isEmpty()) {
			$query = $class::query(); // From http://laravel.io/forum/04-13-2015-combine-foreach-loop-and-eloquent-to-perform-a-search
			foreach ($searchable_properties as $searchable_property) {			
				$query->orWhere($searchable_property->name,'LIKE',"$search_term%"); // Or would a %$term% search be better?
			}
			$objects = $query->take(20)->get();	// Limits the dropdown to 20 items; this may need to be adjusted
			if (!$objects->isEmpty()) {
			    foreach ($objects as $object) {
			    	$result            = new \StdClass;
			    	$result->id        = $object->id;
			    	$result->text      = $this->get_object_title($object);			    	
			    	$json[]            = $result;
			    }
			}
		}

		$status = 200;

        return \Response::json($json, $status);
	}

	/**
	 * A dummy placeholder function that generates JSON, just for testing select2 for now
	 * @return Response
	 */
	public function json() {
		$json = [];

		$status = 200;

		foreach ([1,2,3,4,5] as $i) {
			$result = new \StdClass;
	    	$result->id   = $i;
	    	$result->text = "Sample item ".date("F",strtotime('2010-0'.$i.'-01'));
	    	$json[]       = $result;
	    }

        return \Response::json($json, $status);


	}


	/**
	 * List all objects of a given CtrlClass
	 * @param  integer $ctrl_class_id The ID of the class we're editing
	 * @param  string $filter_string Are we filtering the list? Currently stored as a ~ delimited list of property=>id comma-separated pairs; see below
	 *
	 * @return Response
	 */
	public function list_objects(Request $request, $ctrl_class_id, $filter_string = NULL)
	{		

		// Convert the the $filter parameter into one that makes sense
		$filter_array = $this->convert_filter_string_to_array($filter_string);			
		$filter_description = $this->describe_filter($filter_array);

		$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();		

		// We need to include the correct header columns on the table
		// (Search in set code here: http://stackoverflow.com/questions/28055363/laravel-eloquent-find-in-array-from-sql)
		// Some minor duplication of code from get_data here:
		$headers = $ctrl_class->ctrl_properties()->whereRaw(
		   '(find_in_set(?, flags) or find_in_set(?, flags))', // Note that the bracket grouping is required: http://stackoverflow.com/questions/27193509/laravel-eloquent-whereraw-sql-clause-returns-all-rows-when-using-or-operator
		   ['header','search']		   
		)->get();
		
		/*
		We need to basically recreate something like this for the JS column definitions		 
			{ data: 'title', name: 'title' },
            { data: 'one.title', name: 'one.title',"defaultContent": '<!-- NONE -->' },
            { data: 'action', name: 'action' }, 
        ... and this in the HTML:
			<th>Title</th>
            <th>One</th>
		 */
        $js_columns = [];
        $th_columns = [];

        foreach ($headers as $header) {

        	// We could exclude the column here if it's found in the $filter array; but really, we'd be unlikely to have a filterable column as a header anyway. Implement this if it becomes necessary.

        	$column = new \StdClass;
        	if ($header->relationship_type) {
        		// We need to identify the "string" column for this related class
        		// Note that this doesn't yet handle hasMany relationships, I don't think?
        		// We also haven't allowed for classes with multiple "string" values;
        		// we may have to utilise something from http://datatables.yajrabox.com/eloquent/dt-row for that
        		$related_ctrl_class = CtrlClass::where('id',$header->related_to_id)->firstOrFail();	
        		$string = $ctrl_class->ctrl_properties()->whereRaw(
				   'find_in_set(?, flags)',
				   ['string']				   
				)->firstOrFail();
				$value = $header->name.'.'.$string->name; // $header->name might not always hold true here?
        		$column->data = $value;
        		$column->name = $value;

        		// Hide any columns that are searchable, but not headers (probably quite rare in practice)
        		// See https://datatables.net/reference/option/columns.visible
        		if (in_array('search', explode(',',$header->flags)) && !in_array('header', explode(',',$header->flags))) {
        			$column->visible = false;
        		}

        		// Get around a problem with datatables if there's no relationship defined
        		// See https://datatables.net/manual/tech-notes/4
        		$column->defaultContent = 'None'; // We can't filter the list to show all "None" items though... not yet.
        		$th_columns[] = '<th data-search-dropdown="true">'.$header->label.'</th>';
        	}
        	else {
        		$column->data = $header->name;
        		$column->name = $ctrl_class->table_name.'.'.$header->name;
        			// Again, see http://datatables.yajrabox.com/eloquent/relationships
        			// "Important! To avoid ambiguous column name error, it is advised to declare your column name as table.column just like on how you declare it when using a join statements."
        		if ($header->name == 'order') {
        			// A special case, we use this to allow the table to be reordered
        			$th_columns[]          = '<th width="1" data-order-rows="true" _data-orderable="false" >'.$header->label.'</th>';
        			$column->orderSequence = ['asc'];
        				// I think it makes no sense to allow the "order" column to be reordered; it just confuses the user, as dragging and dropping items doesn't then have the expected results
        				// (Reordering items just swaps the relevant items, so if you reorder the list to put the last item first, then swap two items in the middle of the list, the last item is still last when you reload the page. If that makes sense. We could potentially reorder ALL items when you reorder anything, but this seems inefficient).
        					// We still have a bug whereby the sort icon disappears from the order column when we sort by another column though...?
        			$column->className      = "reorder";
        		}
        		else {
        			$th_columns[] = '<th data-search-text="true">'.$header->label.'</th>';
        		}
        	
        	}
        	$js_columns[] = $column;        	
        	
        }
        // dd($js_columns);
        // Add the "action" column
        $action_column       = new \StdClass;
        $action_column->data = 'action';
        $action_column->name = 'action';
        $js_columns[]        = $action_column;

        // Can we reorder this list?
        // if (property_exists($ctrl_class,'order')) {
        // From https://laracasts.com/discuss/channels/eloquent/test-attributescolumns-existence

        // see: https://github.com/laravel/framework/issues/1436
    	$class  = $ctrl_class->get_class();
    	$table = with(new $class)->getTable();    	
    	if (Schema::hasColumn($table, 'order')) {
        // if (Schema::hasColumn($ctrl_class->getTable(), 'order')) {
        	$can_reorder = true;
        }
        else {
        	$can_reorder = false;	
        }

        // Do we have an unfiltered list we can link back to?
        if ($filter_array) {
        	// dd($filter_array);
        	// $filter_array[0]['ctrl_property_id'] is now the ID of the property that links back to the "parent" list, so:
        	$unfiltered_ctrl_property = CtrlProperty::where('id',$filter_array[0]['ctrl_property_id'])->firstOrFail();
        	$unfiltered_ctrl_class    = CtrlClass::where('id',$unfiltered_ctrl_property->related_to_id)->firstOrFail();
        	$unfiltered_list_link     = route('ctrl::list_objects',[$unfiltered_ctrl_class->id]);
        }

        // Should we display a "View all" link? ie, is this list filtered, and are we allowed to list ALL items?
        if ($filter_array && $ctrl_class->menu_title) { // A crude way to test if we can list items; are we actually going to use the 'list' permission?
        	$show_all_link = route('ctrl::list_objects',$ctrl_class->id);
        }

        $add_link = route('ctrl::edit_object',[$ctrl_class->id,0,$filter_string]);

        $key = 		$key = $this->get_row_buttons($ctrl_class->id,0,true);

		return view('ctrl::list_objects',[
			'ctrl_class'           => $ctrl_class,
			'th_columns'           => implode("\n",$th_columns),
			'js_columns'           => json_encode($js_columns),
			'filter_description'   => $filter_description,
			'filter_string'        => $filter_string,
			'unfiltered_list_link' => (!empty($unfiltered_list_link) ? $unfiltered_list_link : false),
			'show_all_link'        => (!empty($show_all_link) ? $show_all_link : false),
			'can_reorder'          => $can_reorder,
			'add_link'             => $add_link,
			'key'                  => $key
		]);
	}

	/**
	 * Export all objects of a given CtrlClass
	 * @param  integer $ctrl_class_id The ID of the class we're editing
	 * @param  string $filter_string Are we filtering the list? Currently stored as a ~ delimited list of property=>id comma-separated pairs; see below
	 *
	 * @return Response
	 */
	public function export_objects(Request $request, $ctrl_class_id, $filter_string = NULL) {
		dd("Export option, not yet written");
	}

	/**
	 * Send the user a sample CSV file, which illustrates how to import data
	 * @param  integer $ctrl_class_id The ID of the class we're editing
	 * @param  string $filter_string Are we filtering the list? Currently stored as a ~ delimited list of property=>id comma-separated pairs; see below
	 *
	 * @return Response
	 */
	public function import_objects_sample(Request $request, $ctrl_class_id, $filter_string = NULL) {
		if (!$this->module->enabled('import_objects')) {
			// This can only happen if someone is fucking around with the URL, so just bail on them.
			\App::abort(403, 'Access denied');
		}
		// Should also check that we can import ctrlclass_id...

		$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();		
		if (!$ctrl_class->can('import')) {
			\App::abort(403, 'Access denied'); // As above
		}

		$headers = $this->module->run('import_objects',[
			'get-headers',
			$ctrl_class_id,
			// $filter_string
		]);

		$filename = 'import-'.str_slug($ctrl_class->get_plural()).'-example';

		// \Maatwebsite\Excel\Facades\Excel::create($filename, function($excel) use ($headers) {
		Excel::create($filename, function($excel) use ($headers) {
			 $excel->sheet('Sheetname', function($sheet) use ($headers) {

		        $sheet->fromArray(array(
		            $headers
		        ),null,'A1',false,false);

		    });		    
		})->download('csv');;


	}

	/**
	 * Handle the posted CSV when importing all objects of a given CtrlClass
	 * @param  integer $ctrl_class_id The ID of the class we're editing
	 * @param  string $filter_string Are we filtering the list? Currently stored as a ~ delimited list of property=>id comma-separated pairs; see below
	 *
	 * @return Response
	 */
	public function import_objects_process(Request $request, $ctrl_class_id, $filter_string = NULL) {
		
		$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();
    	
    	if (!$this->module->enabled('import_objects')) {
			// This can only happen if someone is fucking around with the URL, so just bail on them.
			\App::abort(403, 'Access denied');
			// Should also check that we can import ctrlclass_id...
		}
		else if (!$ctrl_class->can('import')) {
			\App::abort(403, 'Access denied'); // As above
		}

		$this->validate($request, [
        	'csv-import'=>'required'
        ],[
		    'csv-import.required' => 'Please select a CSV file to upload'
		]);

		$csv_file = trim($request->input('csv-import'),'/');
		$errors = [];

		// Work out what headers we need, what the callback functions are, whether we have a "pre-import" function, etc:

		$required_headers = $this->module->run('import_objects',[
			'get-headers',
			$ctrl_class_id,
			// $filter_string // required?
		]);

		// Convert all headers into slugged values, as per http://www.maatwebsite.nl/laravel-excel/docs/import#results
		// Excel does this on import automatically, so we need compare slugged values with the headers Excel has converted
		// Technically this uses the protected function Excel::getSluggedIndex()
		// but it's essentially the same as Laravel's str_slug():
		$slugged_headers = array_map('str_slug',$required_headers,
			array_fill(0,count($required_headers),'_')
			// This passes an '_' parameter to str_slug;
			// see http://stackoverflow.com/questions/8745447/array-map-function-in-php-with-parameter
		);

		$callback_function = $this->module->run('import_objects',[
			'get-callback-function',
			$ctrl_class_id,
			// $filter_string // required?
		]);

		
		// Run the pre-import-function if necessary; this can either prep data, or truncate tables,
		// or (in the case of the Argos CAT sheet) bypass the Excel import altogether		

		if ($pre_import_function = $this->module->run('import_objects',[
			'get-pre-import-function',
			$ctrl_class_id,
			// $filter_string // required?			
		])) {
			if ($response = $pre_import_function($ctrl_class_id,$filter_string,$csv_file)) {
				return $response;
			}
		}
			
		// Now import the data in chunks:

    	$loop = 0;
    	$count = 0;

    	set_time_limit(0); // Dammit, it's the LOAD that's taking a while, not the procecssing.
    						// This is a problem in Argos because it's a 25Mb CSV...
    						// Ah, it's definitely still quicker to chunk though

    	// Not sure if this is required or not, but it's been useful in the past
    	// Found the tip here: https://github.com/Maatwebsite/Laravel-Excel/issues/388
    	ini_set('auto_detect_line_endings', true);

    	Excel::filter('chunk')->load($csv_file)->chunk(250, function($results) use (
    		&$count,
    		$loop,
    		$ctrl_class_id,
    		$errors,
    		$required_headers,
    		$slugged_headers,
    		$callback_function
    	) {
			if ($loop++ == 0) { // First pass so check headers etc
				$first_row   = $results->first()->toArray();   	
			    $csv_headers = array_keys($first_row);

					
				if (count($results) == 0) {
		    		$errors['csv-import'] = 'That CSV file doesn\'t appear to contain any data';
		    	}
		    	elseif (count($csv_headers) != count($required_headers)) {
		    		// Can fairly easily run an array diff here...
		    		$errors['csv-import'] = 'That CSV file doesn\'t seem to have the correct number of columns';    	
				}
		    	elseif ($csv_headers != $slugged_headers) {
		    		// ... and here
		    		$errors['csv-import'] = 'That CSV file doesn\'t seem to have the correct column titles';    	
				}				
			}

			if (!$errors) {

	    		$response = $callback_function($results); // Again, we may need the filter string here in some cases...?

				if ($response === false) {
					$errors['csv-import'] = 'Cannot import data';
				}			
				else if ($response === 0) {
					$errors['csv-import'] = 'This import would have no effect; no rows would be processed';
					// Is this always right? Might we sometimes import zero rows from the first chunk, even though we'd import rows in subsequent chunks?
				}
				else {
					$count += $response;
				}
			}

			if ($errors) return; // Should exit the chunk, I think
			
		}, false); // False allows us to pass variables by reference; https://github.com/Maatwebsite/Laravel-Excel/issues/744
    	
		if (!empty($errors)) {
			return response()->json($errors,422);
       	}
       	else {
       		$message  = $count . ' records imported';
       		$messages = [$message];
       		$request->session()->flash('messages', $messages);		 
       		$back = route('ctrl::import_objects',[$ctrl_class_id, $filter_string]);
       		return response()->json(['redirect'=>$back]);
       	}
		
	}

	/**
	 * Import all objects of a given CtrlClass
	 * @param  integer $ctrl_class_id The ID of the class we're editing
	 * @param  string $filter_string Are we filtering the list? Currently stored as a ~ delimited list of property=>id comma-separated pairs; see below
	 *           ** Surely we won't need to filter the list before importing to it...?
	 *           ** I suppose we could automatically categorise imported records if we knew (eg) what category they were in...
	 *           ** We won't handle this yet though
	 *
	 * @return Response
	 */
	public function import_objects(Request $request, $ctrl_class_id, $filter_string = NULL)
	{		

		if (!$this->module->enabled('import_objects')) {
			// This can only happen if someone is fucking around with the URL, so just bail on them.
			\App::abort(403, 'Access denied');
		}
		// Should also check that we can import ctrlclass_id...

		$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();		
		if (!$ctrl_class->can('import')) {
			\App::abort(403, 'Access denied'); // As above
		}

		$back_link   = route('ctrl::list_objects',[$ctrl_class->id,$filter_string]);
		$save_link   = route('ctrl::import_objects_process',[$ctrl_class->id,$filter_string]);
		$sample_link = route('ctrl::import_objects_sample',[$ctrl_class->id,$filter_string]);

		$upload_field = [
			'id'       => 'csv-import',
			'name'     => 'csv-import',
			'type'     => 'file',
			'template' => 'krajee',
			'value'    => ''
		];

		return view('ctrl::upload_file',[
			'icon'             => $ctrl_class->get_icon(),
			'page_title'       => "Import ".ucwords($ctrl_class->get_plural()),
			'page_description' => 'Use this page to import records from a CSV file',
			'help_text'        => 'Please select a CSV file from your computer by clicking "Browse", and then click "Import". <a href="'.$sample_link.'">You can download an example CSV here</a>.',
			'back_link'        => $back_link,
			'save_link'        => $save_link,
			'form_field'       => $upload_field,
		]);
	}

	/**
	 * Get data for datatables
	 * @param  integer $ctrl_class_id The ID of the class we're editing
	 * @param  string $filter Optional list filter, passed in from the datatables Ajax call.
	 * 
	 * @return [type]                [description]
	 * 
	 */
	public function get_data($ctrl_class_id, $filter_string = NULL) {

		$filter_array = $this->convert_filter_string_to_array($filter_string);			

		//$objects = \App\Ctrl\Models\Test::query();
		//$users = User::select(['id', 'name', 'email', 'password', 'created_at', 'updated_at']);

		// if ($filter) dd($filter);

		$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();		
		$class      = $ctrl_class->get_class();
		//$objects    = $class::query(); // Why query() and not all()? Are they the same thing?
		// This will include all necessary relationships: see http://datatables.yajrabox.com/eloquent/relationships
		
		$headers = $ctrl_class->ctrl_properties()->whereRaw(
		   '(find_in_set(?, flags) or find_in_set(?, flags))',
		   ['header','search']
		)->get();

		$with = array();
		foreach ($headers as $header) {
			if ($header->relationship_type) {
				// If this is a relationship, include it in the main query below
				$with[] = $header->name;
			}
		}

		if ($with) {
			// Use Eager Loading to pull in related items
			// Again, see http://datatables.yajrabox.com/eloquent/relationships			
      		// Note that we shouldn't filter the query here; we want this to pull as much information back as possible
      		// so that we can rely on datatables to filter everything for us
			$objects = $class::with(implode(',', $with))->select($ctrl_class->table_name.'.*');	
		}
		else {
			$objects    = $class::query();
		}

		// See http://datatables.yajrabox.com/eloquent/dt-row for some good tips here

		// Known issue, that I'm struggling to resolve; if we have a dropdown to search related fields, but there's no relationship for an object, we can't select the "empty" value and show all items without a relationship. TODO.

        return Datatables::of($objects)  
        	->setRowId('id') // For reordering
        	->editColumn('order', '<i class="fa fa-reorder"></i>') // Set the displayed value of the order column to just show the icon        	        	
        	// ->editColumn('src', '<div class="media"><div class="media-left"><a href="{{$src}}" data-toggle="lightbox" data-title="{{$src}}"><img class="media-object" src="{{$src}}" height="30"></a></div><div class="media-body" style="vertical-align: middle">{{$src}}</div></div>') // Draw the actual image, if this is an image field
        	->editColumn('src', function($object) {
	    		if ($src = $object->src) { // If we have a "src" column, assume (for now!) that we render it as an image. We could probably load the corresponding ctrlproperty here and confirm this:
	    			if (strpos($src, '/') !== 0) $src = "/$src"; // We need a leading slash on the image source here

	    			$path_parts = pathinfo($src);
	    			$basename   = str_limit($path_parts['basename'],20);

					return sprintf('<div class="media"><div class="media-left"><a href="%1$s" data-toggle="lightbox" data-title="%2$s"><img class="media-object" src="%1$s" height="30"></a></div><div class="media-body" style="vertical-align: middle">%2$s</div></div>',$src, $basename);
				}
        	}) // Draw the actual image, if this is an image field
        	->editColumn('file', function($object) {  // If we have a "file" column, assume it's a clickable link. DEFINITELY need to query ctrlproperty->type here,see 'src' above:
	    		if ($file = $object->file) {
	    			if (strpos($file, '/') !== 0) $file = "/$file";

	    			$path_parts = pathinfo($file);
	    			$basename   = str_limit($path_parts['basename'],20);

					return sprintf('<i class="fa fa-download"></i> <a href="%1$s">%2$s</a>',$file, $basename);
				}
        	}) // Draw the actual image, if this is an image field
            ->addColumn('action', function ($object) use ($ctrl_class) {

            	return $this->get_row_buttons($ctrl_class->id, $object->id);

            })
            // Is this the best place to filter results if necessary?
            // I think so. See: http://datatables.yajrabox.com/eloquent/custom-filter
        	->filter(function ($query) use ($filter_array) {
	            if ($filter_array) {
	            	foreach ($filter_array as $filter) {
						$filter_ctrl_property = CtrlProperty::where('id',$filter['ctrl_property_id'])->firstOrFail(); // This throws a 404 if not found; not sure that's strictly what we want
						// We only handle 'belongsTo' filters at the moment
						if ($filter_ctrl_property->relationship_type == 'belongsTo') {
							// Duplication of code from @describe_filter here
							$related_ctrl_class = CtrlClass::where('id',$filter_ctrl_property->related_to_id)->firstOrFail(); // As above
							$related_class      = $related_ctrl_class->get_class();
							$related_object     = $related_class::where('id',$filter['value'])->firstOrFail();
							$query->where($filter_ctrl_property->foreign_key,$related_object->id);
						}						
	            	}
	            	//$query->where('title','LIKE',"%related%");	                
	            }	            
	        })
            ->make(true);

		// return Datatables::of($objects)->make(true);
	}

	/**
	 * Return the row buttons for the row that holds object $object_id of ctrl_class $ctrl_class_id
	 * @param  integer $ctrl_class+id
	 * @param  integer $object_id
	 * @param  $key Are we drawing a key, or returning actual buttons?
	 * @return string HTML
	 */
	protected function get_row_buttons($ctrl_class_id,$object_id, $key = false) {

		$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();	

    	$edit_link   = route('ctrl::edit_object',[$ctrl_class->id,$object_id]); 
    	$delete_link = route('ctrl::delete_object',[$ctrl_class->id,$object_id]);

    	// Do we have any filtered lists?
    	$filtered_list_links        = [];
    	$filtered_list_properties = $ctrl_class->ctrl_properties()->whereRaw(
		   '(find_in_set(?, flags))',
		   ['filtered_list']		   
		)->where('relationship_type','hasMany')->get(); // I think a filtered list will always be "hasMany"?           	            					
    	foreach ($filtered_list_properties as $filter_ctrl_property) {
    		// Build the filter string
    		/*
    		 Now, we need the INVERSE property here. That is:
    		 	- If we're loading a Test record, with a "Many" property set to "filtered_list"
    		 	- We need to find the "test" property of the "Many" object, so that we can show Many items where "test" is the value of this object
    		 I believe we can do this by matching the foreign key
    		 */
    		$inverse_filter_ctrl_property = CtrlProperty::where('ctrl_class_id',$filter_ctrl_property->related_to_id)
    														->where('related_to_id',$filter_ctrl_property->ctrl_class_id)
    														->where('foreign_key',$filter_ctrl_property->foreign_key) // Necessary?
    														->firstOrFail();
    		
    		$filtered_list_array    = [
    			'ctrl_property_id'=>$inverse_filter_ctrl_property->id, // We don't use the keys here, they're for clarity only (as we use them elsewhere when handling filters)
    			'value'=>$object_id
    		];            		
    		$filtered_list_string = implode(',', $filtered_list_array); // Add 1,2 to the array (ctrl_property_id,value). Discard keys as above
    		
    		// Establish the title and icon for the link; ie, the icon and title of the related class
    		$filter_ctrl_class = CtrlClass::where('id',$filter_ctrl_property->related_to_id)->firstOrFail();
			//$filter_related_class = $filter_ctrl_class->get_class();

			// Count the related items					
			$count_ctrl_class = $filter_ctrl_class;
			$count_class      = $count_ctrl_class->get_class();					
			$count_objects    = $count_class::where($inverse_filter_ctrl_property->foreign_key,$filtered_list_array['value']);
			$count            = $count_objects->count();
			
			if ($count > 0) {
				$filter_list_title = 'View '.$count . ' '.($count == 1 ? $filter_ctrl_class->get_singular() : $filter_ctrl_class->get_plural());
				$filter_list_link  = route('ctrl::list_objects',[$filter_ctrl_property->related_to_id,$filtered_list_string]);
			}
			else {
				$filter_list_title = 'No '.$filter_ctrl_class->get_plural();						
				$filter_list_link  = false;
			}
			$filter_add_title = 'Add '.$this->a_an($filter_ctrl_class->get_singular()).' '.$filter_ctrl_class->get_singular();
			$filter_add_link  = route('ctrl::edit_object',[$filter_ctrl_property->related_to_id,0,$filtered_list_string]); // TODO check permissions here; can we add items?

			// New: always link to the filtered list, regardless of whether we have any related items:
			$filter_list_link  = route('ctrl::list_objects',[$filter_ctrl_property->related_to_id,$filtered_list_string]);

			$title = ucwords($filter_ctrl_class->get_plural());

        	$filtered_list_links[]  = [
    			'icon'       => $filter_ctrl_class->get_icon(),
    			'count'      => $count,
    			'title'      => $title, // A generic title, only used by the key at the moment
    			'list_title' => $filter_list_title,
    			'list_link'  => $filter_list_link,
    			'add_title'  => $filter_add_title,
    			'add_link'   => $filter_add_link,
    		];

    	}

    	
    	// Add a "reorder" button to the key
    	// see: https://github.com/laravel/framework/issues/1436
    	$class  = $ctrl_class->get_class();
    	$table = with(new $class)->getTable();    	
    	if (Schema::hasColumn($table, 'order')) {
        	$can_reorder = true;
        }
        else {
        	$can_reorder = false;	
        }
    	
    	if ($key) {
    		$template = 'ctrl::tables.row-buttons-key';
    	}
    	else {
    		$template = 'ctrl::tables.row-buttons';	
    	}


    	$buttons = view($template, [
    		'edit_link'           => $edit_link,
    		'delete_link'         => $delete_link,
    		'filtered_list_links' => $filtered_list_links,
    		'can_reorder'         => $can_reorder
    	]);            	
       	return $buttons;
	}

	/**
	 * Delete the specifed object of the stated CtrlClass
	 * @return Response
	 */
	public function delete_object($ctrl_class_id, $object_id) {	
		$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();				
		$class  = $ctrl_class->get_class();
		$object = $class::where('id',$object_id)->firstOrFail();
		$object->delete();
		
		$response = 'Item deleted';
		$status = 200;
		$json = [
			'response'      => $response,			
        ];
        return \Response::json($json, $status);
	}

	/**
	 * A function used by the "test" module, as a demonstration of how to call functions from the parent controller
	 * @return string A test string
	 */
	public function testing() {
		return 'test';
	}

	/**
	 * Return the object defined by the ctrl_class $ctrl_class_id, with the ID $object_id (if present)
	 * @param  integer $ctrl_class_id the ctrl_class ID
	 * @param  integer $object_id  The ID of the object
	 * @return object The resulting object
	 */
	public function get_object_from_ctrl_class_id($ctrl_class_id,$object_id = NULL) {		
		$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();
		$class      = $ctrl_class->get_class();
		$object     = ($object_id) ? $class::where('id',$object_id)->firstOrFail() : new $class;		
		return $object;
	}

	/**
	 * Return the ctrl_class object defined by the object $object_id	 
	 * @param  integer $object_id  The ID of the object
	 * @return object The resulting object
	 */
	protected function get_ctrl_class_from_object($object) {	

		$ctrl_class_name = str_replace('App\Ctrl\Models\\','',get_class($object));		
		$ctrl_class = CtrlClass::where('name',$ctrl_class_name)->firstOrFail();		
		return $ctrl_class;
		
	}

	/**
	 * Return the title of the object $object
	 * @param  object $objectd  The object
	 * @return string The title of the object
	 */
	protected function get_object_title($object) {

		$ctrl_class = $this->get_ctrl_class_from_object($object);

		$title_properties = $ctrl_class->ctrl_properties()->whereRaw(
			'(find_in_set(?, flags))',
			['string']	
		)->get();
		$title_strings = [];
		foreach ($title_properties as $title_property) {
			$property = $title_property->name;
			$title_strings[] = $object->$property;
		}

		return implode(' ', $title_strings);
		
	}

	/**
	 * Edit an objects of a given CtrlClass, if an ID is given
	 * Or renders a blank form if not
	 * This essentially renders a form for the object
	 * @param  integer $ctrl_class_id The ID of the class we're editing
	 * @param  integer $object_id The ID of the object we're editing (zero to create a new one)
	 * @param  string $filter_string Optional list filter; such as 43,1, which will set the value of the ctrl_property 43 to 1 when we save the form
	 *
	 * @return Response
	 */
	public function edit_object($ctrl_class_id, $object_id = NULL, $filter_string = NULL)
	{		

		// Convert the the $filter parameter into one that makes sense
		// Used when linking BACK to a list
		$filter_array = $this->convert_filter_string_to_array($filter_string);		

		$default_values      = $this->convert_filter_string_to_array($filter_string); // Note that we use this to set default values, not filter the list
		$default_description = $this->describe_filter($default_values);
		
		$object             = $this->get_object_from_ctrl_class_id($ctrl_class_id,$object_id);

		$ctrl_class         = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();
		$ctrl_properties    = $ctrl_class->ctrl_properties()->where('fieldset','!=','')->get();	
		
		$tabbed_form_fields = [];
		$hidden_form_fields = [];

		foreach ($ctrl_properties as $ctrl_property) {

			unset($value); // Reset $value , $values
			$values = [];

			// Adjust the field type, mainly to handle relationships and multiple dropdowns
			/* Or, do we actually handle this in the dropdown.blade template? Currently, yes we do:
			if ($ctrl_property->field_type == 'dropdown' && $ctrl_property->relationship_type == 'belongsToMany') {
				$ctrl_property->field_type = 'dropdown_multiple';
			}
			*/
			// We do use the same view for image and file, though:
			if (in_array($ctrl_property->field_type,['image','file'])) {
				$ctrl_property->template = 'krajee';
			}
			elseif (in_array($ctrl_property->field_type,['date','datetime'])) {
				$ctrl_property->template = 'date';
			}
			else {
				$ctrl_property->template = $ctrl_property->field_type;
			}

			if (!view()->exists('ctrl::form_fields.'.$ctrl_property->template)) {
				trigger_error("Cannot load view for field type ".$ctrl_property->field_type);
			}

			// Ascertain the name current value of this field
			// This essentially converts 'one' to 'one_id' and so on
			$field_name = $ctrl_property->get_field_name();

			if ($ctrl_property->related_to_id && in_array($ctrl_property->relationship_type,['hasMany','belongsToMany'])) {
				$related_objects = $object->$field_name;
				$value = [];
				foreach ($related_objects as $related_object) {
					$value[$related_object->id] = $this->get_object_title($related_object);
				}
			}
			else {
				// Do we have a default value set in the querystring?
				if ($default_values && !$object_id) { // We're adding a new object
					foreach ($default_values as $default_value) {
						if ($ctrl_property->id == $default_value['ctrl_property_id']) {
							$value = $default_value['value'];
						}
					}
				}
				if (!isset($value)) { // No default value, so pull it from the existing object
					$value      = $object->$field_name;		
				}				
			}

			// Do we have a range of valid values for this field? For example, an ENUM or relationship field			
			if ($ctrl_property->related_to_id) {
				$related_ctrl_class = \Sevenpointsix\Ctrl\Models\CtrlClass::find($ctrl_property->related_to_id);
				$related_class 		= $related_ctrl_class->get_class();


				// This breaks as we have too many related items... but we load these via Ajax anyway if there are more than 20. So, just get the first 20...
				// dump($related_class);
				// $related_objects  	= $related_class::all();
				// $related_objects  	= $related_class::take(21)->get();
				// This needs an overhaul, can we chunk for now?
				/* No, doesn't work, times out
				$related_class::chunk(200, function ($related_objects) {
				    foreach ($related_objects as $related_object) {
						$values[$related_object->id] = $this->get_object_title($related_object); 
					}
				});
				*/
				/*
				foreach ($related_objects as $related_object) {
					$values[$related_object->id] = $this->get_object_title($related_object); 
				}
				*/
				// If we use select2 for EVERYTHING (sensible I think?), we can just do this...update template/dropdown accordingly
				if (!empty($value)) {
					$related_objects  	= $related_class::where('id',$value)->get();
					foreach ($related_objects as $related_object) {
						$values[$related_object->id] = $this->get_object_title($related_object); 
					}
				}
			}
			else {
				$column = DB::select("SHOW COLUMNS FROM {$ctrl_property->ctrl_class->table_name} WHERE Field = '{$ctrl_property->name}'");
				if (!isset($column[0])) {
					dump("SHOW COLUMNS FROM {$ctrl_property->ctrl_class->table_name} WHERE Field = '{$ctrl_property->name}'");
					dd($column);
				}
				$type = $column[0]->Type;
				// Is this an ENUM field?
				preg_match("/enum\((.*)\)/", $type, $matches);				
				if ($matches) {					
					// Convert 'One','Two','Three' into an array
					$enums = explode("','",trim($matches[1],"'"));
					$loop = 1;
					foreach ($enums as $enum) {
						// Note that apostrophes are doubled-up when exported from SHOW COLUMNS
						$value = str_replace("''","'",$enum);					
						$values[$loop++] = $value;
					}
				}
			}

			// Build the form_field anddd it to the tabs

			$tab_name = $ctrl_property->fieldset;
			$tab_icon = 'fa fa-list';
			$tab_text = '';

			if (!isset($tabbed_form_fields[$tab_name])) {
				$tabbed_form_fields[$tab_name] = [
					'icon'        => $tab_icon,
					'text'        => $tab_text,
					'form_fields' => []
				];
			}


			$tabbed_form_fields[$tab_name]['form_fields']['form_id_'.$ctrl_property->name] = [
				'id'                      => 'form_id_'.$ctrl_property->name,
				'name'                    => $field_name,
				'values'                  => $values, // A range of possible values
				'value'                   => $value, // Remember that $value can be an array, for relationships / multiple selects etc
				'type'                    => $ctrl_property->field_type, // This is used to modify some templates; date.blade.php can handle date or datetime types
				'template'                => $ctrl_property->template,
				'label'                   => $ctrl_property->label,
				'tip'                     => $ctrl_property->tip,
				'related_ctrl_class_name' => (!empty($related_ctrl_class) ? $related_ctrl_class->name : false)
			];
			/*
				Note: we pass in the related_ctrl_class so that we can use Ajax to generate the list of select2 options.
				Otherwise, if we're working with (eg) Sogra Products, we have a select box with thousands of options, which breaks.
			*/

		}		

		// TODO: right, we need to add something here that allows us to customise the list of form fields
		// I think we need to use a serviceprovider and inject it into this main controlller
		// See the comment on this page re. ReportingService: http://stackoverflow.com/questions/30365169/access-controller-method-from-another-controller-in-laravel-5
		if ($this->module->enabled('manipulate_form')) {
			$tabbed_form_fields = $this->module->run('manipulate_form',[
				$tabbed_form_fields,
				$ctrl_class_id,
				$object_id,
				$filter_string
			]);
		}

		// Add any filter properties as hidden fields
		if ($default_values) { // Set HIDDEN fields here; we can default known fields in the main loop above

			foreach ($default_values as $default_value) {
				$default_property = $ctrl_class->ctrl_properties()->
									where('fieldset','')->
									where('id',$default_value['ctrl_property_id'])->first();										
				if ($default_property !== null) {
					$default_field_name = $default_property->get_field_name();
					$hidden_form_fields[] = [
						'id'       => 'form_id_'.$default_field_name,
						'name'     => $default_field_name,							
						'value'    => $default_value['value'],										
						// Don't need $template, $values, $tip, $type or $label.
					];			
				}
			}			
		}

		if ($object_id) {
			$page_title       = 'Edit this '.$ctrl_class->get_singular();
			// $page_description = '&ldquo;'.$object->title.'&rdquo;';
			$page_description = '&ldquo;'.$this->get_object_title($object).'&rdquo;';
			$delete_link      = route('ctrl::delete_object',[$ctrl_class->id,$object->id]);
		}
		else {
			$page_title = 'Add '.$this->a_an($ctrl_class->get_singular()) . ' ' .$ctrl_class->get_singular();			
			$page_description = $default_description ? '&hellip;'.$default_description : ''; 
			$delete_link = '';
		}		
		// If we've set default values here, then that implies that we came through a filtered list; and we want to go back to THAT list, not a list of all these items
		// Or do we...? Hmmm. It might make more sense to return to a filtered list of *these* items... TBC.
		/* Yes, use the code below from @list_objects
		$back_link        = route('ctrl::list_objects',[$ctrl_class->id,$filter_string]);
		*/
		// Do we have an unfiltered list we can link back to?
        if ($filter_array) {
        	// dd($filter_array);
        	// $filter_array[0]['ctrl_property_id'] is now the ID of the property that links back to the "parent" list, so:
        	$unfiltered_ctrl_property = CtrlProperty::where('id',$filter_array[0]['ctrl_property_id'])->firstOrFail();
        	$unfiltered_ctrl_class    = CtrlClass::where('id',$unfiltered_ctrl_property->related_to_id)->firstOrFail();
        	$back_link     = route('ctrl::list_objects',[$unfiltered_ctrl_class->id]);
        }
        else {
        	// Is this a sensible fallback?
        	$back_link        = route('ctrl::list_objects',[$ctrl_class->id,$filter_string]);
        }

		// Similarly... once we've saved a filtered object, we want to bounce back to a filtered list. This enables it:
		$save_link        = route('ctrl::save_object',[$ctrl_class->id,$object_id,$filter_string]);
		
		return view('ctrl::edit_object',[
			'ctrl_class'         => $ctrl_class,
			'page_title'         => $page_title,
			'page_description'   => $page_description,
			'back_link'          => $back_link,
			'delete_link'        => $delete_link,
			'save_link'          => $save_link,
			'object'             => $object,
			'tabbed_form_fields' => $tabbed_form_fields,
			'hidden_form_fields' => $hidden_form_fields,
		]);
	}

	/**
	 * Update an object a given CtrlClass, if an ID is given
	 * Or create a new object if not
	 * @param  Request  $request
	 * @param  integer $ctrl_class_id The ID of the class we're editing
	 * @param  integer $object_id The ID of the object we're editing (zero to create a new one)
	 * @param  string $filter_string This tracks whether we're adding a filtered object -- so we can bounce back to the filtered list..
	 *
	 * @return Response
	 */
	public function save_object(Request $request, $ctrl_class_id, $object_id = NULL, $filter_string = NULL)
	{		
		
		$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();				
		$ctrl_properties = $ctrl_class->ctrl_properties()->where('fieldset','!=','')->get();

		// Validate the post:
		$validation = [];
		foreach ($ctrl_properties as $ctrl_property) {

			$field_name = $ctrl_property->get_field_name();

			$flags = explode(',', $ctrl_property->flags);
			if (in_array('required', $flags)) {
				$validation[$field_name][] = 'required';
			}
			// Note: could also do this in query builder:
			/*
				$required_properties = $ctrl_class->ctrl_properties()
					->whereRaw("FIND_IN_SET('required',flags) > 0")
					->get();  	
			*/
			if ($ctrl_property->field_type == 'email') {
				$validation[$field_name][] = 'email';
			}
			// Check for valid dates; not sure if tis is correct?
			if (in_array($ctrl_property->field_type,['date','datetime'])) {
				$validation[$field_name][] = 'date';
			}

			if (!empty($validation[$field_name])) {
				$validation[$field_name] = implode('|', $validation[$field_name]);
			}
		}
		if ($validation) {
			$this->validate($request, $validation);
	    }

	    $class 		= $ctrl_class->get_class();		
		$object  	= ($object_id) ? $class::where('id',$object_id)->firstOrFail() : new $class;		
		
		// Convert dates back into MySQL format; this feels quite messy but I can't see where else to do it:
		foreach ($ctrl_properties as $ctrl_property) {
			if (in_array($ctrl_property->field_type,['date','datetime']) && !empty($_POST[$ctrl_property->name])) {				
				$date_format = $ctrl_property->field_type == 'date' ? 'Y-m-d' : 'Y-m-d H:i:s';
				$_POST[$ctrl_property->name] = date($date_format,strtotime($_POST[$ctrl_property->name]));
			}
		}
		
        $object->fill($_POST);

        $object->save(); // Save the new object, otherwise we can't save any relationships...
       
        // Now load any related fields (excluding belongsTo, as this indicates the presence of an _id field)
        $related_ctrl_properties = $ctrl_class->ctrl_properties()
                                              ->where('fieldset','!=','')
                                              ->where(function ($query) {
                                                    $query->where('relationship_type','hasMany')
                                                          ->orWhere('relationship_type','belongsToMany');                                                    
                                                })
                                              ->get();  
        
		foreach ($related_ctrl_properties as $related_ctrl_property) {
			$related_field_name = $related_ctrl_property->get_field_name();

	        if ($request->input($related_field_name)) {
				// $request->input($related_field_name) is always an array here, I think?

	        	$related_ctrl_class = \Sevenpointsix\Ctrl\Models\CtrlClass::find($related_ctrl_property->related_to_id);
	            	// NOTE: we need some standard functions for the following:
	            	/*
	            		- Loading the object for a class
	            		- Loading the related class from a property
	            		- Loading related properties for a class
	            		... etc
	            	 */
	            $related_class = $related_ctrl_class->get_class();
	            $related_objects = $related_class::find($request->input($related_field_name));	
	            
	            if ($related_ctrl_property->relationship_type == 'hasMany'
	            	|| $related_ctrl_property->relationship_type == 'belongsToMany'
	            		// I initially thought we'd have to treat these differently, but it seems to work at the moment. Could break in more advanced cases though.
	            	) {

	            	// OK, I think we can use synch here; or does this break for hasMany?	         
	            	// Yeah, breaks for hasMany... :-(  This works though:
	            	if ($related_ctrl_property->relationship_type == 'belongsToMany') {
	            		$object->$related_field_name()->sync($related_objects);
	            	}
	            	else if ($related_ctrl_property->relationship_type == 'hasMany') {	            		
	            		$object->$related_field_name()->saveMany($related_objects);	            		
	            	}
	            	
	            	
					
					/*
		            // A hasMany relationship needs saveMany
		            // belongsToMany might need attach, or synch -- TBC


	            	
	            	dump ("Loading existing_related_objects with $related_field_name");
		          	$existing_related_objects = $object->$related_field_name();
		          	$inverse_property = CtrlProperty::where('ctrl_class_id',$related_ctrl_class->id)
		            								  ->where('foreign_key',$related_ctrl_property->foreign_key)
		            								  ->first(); // Does this always hold true?
					$inverse_field_name = $inverse_property->name;

		          	foreach ($existing_related_objects as $existing_related_object) {	
		          		dump('$existing_related_object'. $existing_related_object);
		          		$existing_related_object->$inverse_field_name()->dissociate();
		          		dump("Removing relationship to $related_field_name");
		          		$existing_related_object->save(); 
		          			// This seems unnecessarily complicated; review this.
		          			// Is there no equivalent of synch() for hasMany/belongsTo relationships?
		          			// Something like, $object->related_field_name()->sync($related_objects);
		          			// That doesn't work though...
		          	}
		          	// dd($related_objects->toArray());
		          	dump("Saving $related_field_name");
		          	// dump($related_objects->lists('id'));
		          	foreach ($related_objects as $r) {
		          		// dump("Saving $related_field_name with ID $r->id to object ".get_class($object));
		          		// $object->$related_field_name()->save($r);
		          	}
		          	// Why wouldn't this work?
		          	// $object->$related_field_name()->sync($related_objects);
		          	// Gives same error as below:
		            // $object->$related_field_name()->saveMany($related_objects);
		            
		          	// Maybe...

		          	if ($related_ctrl_property->relationship_type == 'hasMany') {
		          		$object->$related_field_name()->saveMany($related_objects);
		          	}
	            	else if ($related_ctrl_property->relationship_type == 'belongsToMany') {

	            		// $object->$related_field_name()->attach($related_objects);
	            		foreach      ($related_objects as $r) {
		          			dump("Saving $related_field_name with ID $r->id to object ".get_class($object));
			          		$object->$related_field_name()->attach($r->id);
			          	}
	            	}
		            //$object->save();
		            // This is ALMOST working but glitches; we seem to save the relationship then overwrite it when we try to remove it, even though we try to remove it first. Do we need to lock the tables here?
		            */
		            
		        }	
			}
		}
		
        $object->save();

        // Add a custom post_save module
        if ($this->module->enabled('post_save')) {
        	// We may eventually need to patch this into the validation...? Or would that imply the need for a validation (or pre_save) module?
			$this->module->run('post_save',[				
				$request,
				$object,
				$filter_string
			]);
		}
        
        $redirect = route('ctrl::list_objects',[$ctrl_class->id,$filter_string]);

        if ($request->ajax()) {
            return json_encode([
                'redirect'=>$redirect
            ]);
        }
        else {
            return redirect($redirect);            
        }

	}


	/**
	 * Upload an item (image, video) to the Froala WYSIWYG
	 * @param  Request $request [description]
	 * @return Response
	 */
	public function froala_upload(Request $request)
	{
		// See: https://laravel.com/docs/5.1/requests#files
		// See also: https://www.froala.com/wysiwyg-editor/docs/server-integrations/php-image-upload

		/*
			We could add validation here, BUT; froala appears to validate the file type (ie, images only) already.
			This may be a Chrome/HTML5 feature though; we may yet to run serverside validation in IE or similar.
		*/
		/*
		$this->validate($request, [
	        'file' => 'required|image'
	    ]);
	    */

	    $response = new \StdClass;

		if ($request->file('file')->isValid()) {
			
			$extension = $request->file('file')->getClientOriginalExtension();
			
			if ($request->type == 'image') {
				$name      = uniqid('image_');
			}
			else if ($request->type == 'file') {
				// We could add something a little more intelligent here
				$name = basename($request->file('file')->getClientOriginalName(),".$extension").'-'.rand(11111,99999);
			}
			
			$target_folder = 'uploads';
			$target_file   = $name.'.'.$extension;
			
			$moved_file      = $request->file('file')->move($target_folder, $target_file);			
			$response->link  = '/'.$moved_file->getPathname();
		}
		else {
			$response->error = 'An error has occurred';
			/*
				Or, we could potentially use:
					$request->file('file')->getErrorMessage();
				... or
					$request->file('file')->getError();
				See: http://api.symfony.com/2.7/Symfony/Component/HttpFoundation/File/UploadedFile.html#method_getError
				See also notes above regarding automatic validation in Froala/HTML5 though.
			*/
		}		
        
        return stripslashes(json_encode($response));
    }

    /**
	 * Upload an item (image, video) using the Krajee file input (http://plugins.krajee.com/file-input)
	 * See: http://webtips.krajee.com/ajax-based-file-uploads-using-fileinput-plugin/
	 * @param  Request $request [description]
	 * @return Response
	 */
	public function krajee_upload(Request $request)
	{

		// This code is very, very similar to froala_upload, but we'll keep them separate for future flexibility

		$response = new \StdClass;
		
		$field_name = $request->field_name;
		// We pass in field_name as a hidden parameter

		if ($request->hasFile($field_name)) {

			if ($request->file($field_name)->isValid()) {
				
				$extension = $request->file($field_name)->getClientOriginalExtension();
				
				if ($request->type == 'image') {
					$name      = uniqid('image_');
				}
				else if ($request->type == 'file') {
					// We could add something a little more intelligent here
					$name = basename($request->file($field_name)->getClientOriginalName(),".$extension").'-'.rand(11111,99999);
				}
				
				$target_folder = 'uploads';
				$target_file   = $name.'.'.$extension;
				
				$moved_file      = $request->file($field_name)->move($target_folder, $target_file);			
				$response->link  = '/'.$moved_file->getPathname();
			}
			else {
				$response->error = 'An error has occurred';
				
			}	
		}	

		return stripslashes(json_encode($response));
	}
	/**
	 * Present the login screen
	 *
	 * @return Response
	 */
	public function login()
	{
		$user          = Auth::user();
		$logged_in     = $user && $user->ctrl_group != '';
		// if (Auth::check()) { // This skips the ctrl_group bit, which can lead to an inifinte redirect loop
		if ($logged_in) {
			return redirect(route('ctrl::dashboard'));
		}
		return view('ctrl::login',[
			'logo' => config('ctrl.logo')
		]);
	}

	/**
	 * Random testing
	 *
	 * @return Response
	 */
	public function test()
	{			
	
		$model_folder = 'Ctrl';

        echo app_path($model_folder);
        
        if (!File::makeDirectory(app_path($model_folder))) {
        	echo "fail";
        }
        else {
        	echo "success";
        }
		exit();
		$test = \App\Ctrl\Models\Test::find(1);
		dd($test->title);
		$test->fill(['_token'=>'z0nOvDnYZ5BvAh5YAHvg0fcpTyUNG6wBhgYFqQvG','title'=>'testing']);
		$test->save();
	}

	/**
	 * Log the user out
	 *
	 * @return Response
	 */
	public function logout()
	{
		Auth::logout();
		return redirect(route('ctrl::login'));
	}

	/**
	 * Handle posted data from @login
	 *
	 * @return Response
	 */
	public function post_login(Request $request)
	{		
		// Note: there's a known issue here, which is that old('email') doesn't prepopulate the email address field if we somehow post without Ajax. I'm not sure why not. We don't prepopulate if we do submit an unsuccesful login via Ajax, because we use a clientside redirect which obviously loses the previous values; but this is a different issue.
		// IDEALLY, we'd add a custom rule to $this->validate(), to check for valid logins, but I'm not sure if ths is possible.
		
        $this->validate($request, [
            'email'    => 'required|email',
            'password' => 'required'            
        ]);       

        // Basic validation passed, now try to log the user in
        $email    = $request->input('email');
        $password = $request->input('password');
        $remember = $request->input('remember');

        if (Auth::attempt(['email' => $email, 'password' => $password], !empty($remember))) {
		    // User logged in, but check that they can actually access the CMS:
		    if (!empty(Auth::user()->ctrl_group)) {
		    	$redirect = URL::previous();
			    $message  = 'Logged in';
	        	$messages = collect([$message]);
	        	$request->session()->flash('messages', $messages);		        	
	        	$status = 200;
		    }
		    else {
		    	Auth::logout();		    	
		    }		    
		}
		
		if (!Auth::check()) {
			// Can't log in, try again			
        	$redirect = route('ctrl::login');
        	// Set a flash error message; we don't use these, in fact (we just trigger the "shake" error effect from Authenty)
        	$message  = 'Incorrect login';
        	$messages = collect([$message]);
        	$request->session()->flash('errors', $messages);
     		$status = 400; // Side note: we can set this to 422 to emulate a Laravel validation error
        }	    

        if ($request->ajax()) { 
        	// NOTE: the response here will refresh the page clientside; NOT display Ajax errors inline
        	// forms.js is designed to handle an Ajax error response from the Laravel validation method only
        	// We can replicate this manually (see $status below), but for the login form, why bother?
            $json = [
                'redirect' => $redirect                
            ];
           	return \Response::json($json, $status);
        }
        else {            
            return redirect($redirect);
        }
	}
	
	/**
	 * Return JSON data to the typehead search (used on the dashboard for now)
	 * @param  text $search_term The text we're searching for
	 * @return Response
	 */
	
	public function get_typeahead($search_term = NULL) {		

		$json = [];

		// OK, so. I don't think prefetch will work here, unless we use multiple lists and load the ten most recent items into each one...? Pointless. We'll always use $query.

		if ($search_term) {

			// Loop through all classes that we can edit			
			$ctrl_classes = CtrlClass::whereRaw(
			   '(find_in_set(?, permissions))',
			   ['edit']		   
			)->get();
			foreach ($ctrl_classes as $ctrl_class) {
				$class   = $ctrl_class->get_class();
				
				// What are the searchable columns?
				$searchable_properties = $ctrl_class->ctrl_properties()->whereRaw(
				   '(find_in_set(?, flags))',
				   ['search']		   
				)->whereNull('relationship_type')->get();
					// I have no idea how to include searchable related columns in the query builder below...
				

				if (!$searchable_properties->isEmpty()) {
					$query = $class::query(); // From http://laravel.io/forum/04-13-2015-combine-foreach-loop-and-eloquent-to-perform-a-search
					foreach ($searchable_properties as $searchable_property) {
						/* not needed
						if ($loop++ == 1) {							
							$class::where($searchable_property->name,'LIKE',"%$query%");
						}
						else {
							$objects::orWhere($searchable_property->name,'LIKE',"%$query%");
						}
						*/
						$query->orWhere($searchable_property->name,'LIKE',"%$search_term%");
					}
					$objects = $query->get();	
					if (!$objects->isEmpty()) {
					    foreach ($objects as $object) {
					    	$result             = new \StdClass;
					    	$result->class_name = $ctrl_class->get_singular();
					    	$result->title      = $this->get_object_title($object);
					    	$result->edit_link  = route('ctrl::edit_object',[$ctrl_class->id,$object->id]);							
					    	$result->icon       = $ctrl_class->get_icon() ? $ctrl_class->get_icon() : 'fa fa-toggle-right';
					    	$json[]             = $result;
					    }
					}
				}
			}
				
		}

		$status = 200;

        return \Response::json($json, $status);
	}	

	/**
	 * Handle the posted data when we reorder items in the datatable
	 * @param  int $ctrl_class_id The ID of the Ctrl Class of the objects we're reordering
	 * @return Response
	 */
	
	public function reorder_objects(Request $request, $ctrl_class_id) {		
		$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();				
		$class  = $ctrl_class->get_class();
		if ($new_order = $request->input('new_order')) {			
			foreach ($new_order as $order) {
				$object = $class::where('id',$order['id'])->firstOrFail();
				$object->order = $order['order'];
				$object->save();
			}		
			$response = 'Items reordered';
			$status = 200;
		}		
		/* No, this might just mean we didn't actually change the order:
		if (empty($response)) {
			$response = 'An error has occurred';
			$status = 400;
		}
		*/
		if (!empty($response)) {
			$json = [
				'response'      => $response,			
	        ];
	        return \Response::json($json, $status);
	    }
	}	

	/**
	 * A placeholder function to illustrate how to load config variables
	 *
	 * @return Response
	 */
	public function demo_config()
	{
		dd(Config::get("ctrl.message"));		
	}

	/**
	 * A placeholder function to illustrate how to load views
	 *
	 * @return Response
	 */
	public function demo_view()
	{		
		return view('ctrl::ctrl');
	}

	protected function convert_filter_string_to_array($filter_string) {
		$filter_array = [];
		if (!empty($filter_string)) {
			$filters = explode('~', $filter_string);
			foreach ($filters as $filter) {
				$filter_item = [];				
				list($filter_item['ctrl_property_id'], $filter_item['value']) = explode(',', $filter);
					// Take the two values from the comma-separated pair, and assign them to two array keys of $filter_array; ctrl_property_id and value
				$filter_array[] = $filter_item;
			}			
		}
		return $filter_array;
	}
	
	/**
	 * A function from the old CI CMS; Join a list of elements with commas and a final 'and'; 1) = '1', (1,2) = '1 and 2', (1,2,3) = '1, 2 and 3'
	 Optionally, wrap each element in <$tag> with $properties
	 * @param  array  $array      An array of values
	 * @param  string $tag        An optional HTML tag
	 * @param  array   $properties An array of properties for each HTML tag
	 * @return string The formatted string
	 */
	protected function comma_and($array,$tag = '', $properties = array()) {
		
		if ($tag) {
			foreach ($array as &$a) {
				$a = $this->wrap_in_tag($a,$tag,$properties);
			}
		}

		if (!$array) {
			return '';
		}
		else if (count($array) == 1) {
			if (isset($array[0])) { // This isn't always available!
				return $array[0];
			}
			else {
				reset($array);
				return current($array);
			}
		}
		else if (count($array) == 2) {
			return implode(' and ',$array);
		}
		else {
			$and = array_pop($array);
			return implode(', ',$array). ' and '.$and;
		}
	}

	/**
	 * Another function from the CI CMS; wraps $string in <$tag> with $properties
	 * @param  string $string     the string to wrap
	 * @param  string $tag        the HTML tag to wrap it in
	 * @param  array  $properties Properties for the tag if necessary
	 * @return string the wrapped string
	 */
	protected function wrap_in_tag($string,$tag,$properties = array()) {
		$p = '';
		foreach ($properties as $property=>$value) {
			$p .= $property.'="'.$value.'" '; // Is there a better way of doing this?
		}
		$return = "<$tag $p>$string</$tag>";
		return $return;
	}

	/**
	 * Another function from the CI CMS; Return 'a' or 'an', depending on whether $string starts with a vowel or not
	 * Fairly basic for now, will almost certainly need tweaking:
	 * @param  string $string The string that you need to prefix with 'a' or 'an'
	 * @return string         'a' or 'an' accordinglt
	 */
	protected function a_an($string) {
		$string = strtolower($string);
		if (strpos($string,'use') === 0) {
			// Catch "user" -- as I say, this is a very basic function!
			return 'a';
		}
		else if (strpos($string,'faq') === 0) {
			// As above
			return 'an';
		}
		else if (in_array($string{0},array('a','e','i','o','u'))) {
			return 'an';
		}
		else {
			return 'a';
		}
	}


	/****** Some archived function ******/
	
	/**
	 * Test some dummy models (Test, One, Many, Pivot)
	 *
	 * @return Response
	 */
	public function test_models()
	{
		// Check we can load models from our package
		$test = \App\Ctrl\Models\Test::find(1);
		
		
		dump($test->one->title);

		foreach ($test->many as $many) {
			dump($many->title);
		}
		
		foreach ($test->pivot as $pivot) {
			dump($pivot->title);
		}

		// Ones
		$one = \Sevenpointsix\Ctrl\Models\One::find(1);	
		foreach ($one->test as $t) {
			dump($t->title);
		}

		// Manies
		$many = \Sevenpointsix\Ctrl\Models\Many::find(1);	
		dump($many->test->title);

		// Pivots
		$pivot = \App\Ctrl\Models\Pivot::find(1);	
		foreach ($pivot->test as $t) {
			dump($t->title);
		}

	}
}