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

use Illuminate\Support\Str;

use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use KubAT\PhpSimple\HtmlDomParser; // For manipulating pages, eg, customising the dashboard

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
			dd($this->module->run('test',[
				'string' => 'Hello world!'
			]));
		}
		*/

		/**
		 * This isn't used if we're running 5.4+, we switch to using Storage
		 */
		$this->uploads_folder = config('ctrl.uploads_folder','uploads');

		// This is required by Laravel 5.3+; see "Session In The Constructor", here: https://laravel.com/docs/5.3/upgrade
		/* Specifically:
			In previous versions of Laravel, you could access session variables or the authenticated user in your controller's constructor. This was never intended to be an explicit feature of the framework. In Laravel 5.3, you can't access the session or authenticated user in your controller's constructor because the middleware has not run yet.

			As an alternative, you may define a Closure based middleware directly in your controller's constructor. Before using this feature, make sure that your application is running Laravel 5.3.4 or above:
		*/
		if (\App::VERSION() >= 5.3) {
			$this->middleware(function ($request, $next) {
	            $this->_check_login(Auth::user()); // Check that the user is logged in, if necessary
	            $this->build_menu();
	            return $next($request);
	            //WIP: we need to move some more code here, such as building menu links (otherwise we can't customise them by user).
	        });
	    }
	    else {
			$this->_check_login(); // Check that the user is logged in, if necessary
			$this->build_menu();
		}

		// Can we automatically set the password of any new users (ie, those for which we've added a plaintext_password, but not set an actual password)?
		$new_users = DB::table('users')->where('password','')->where('plaintext_password','!=','')->get();
		foreach ($new_users as $new_user) {
			DB::table('users')
            ->where('id', $new_user->id)
            ->update([
            	'password' => \Hash::make($new_user->plaintext_password),
            	'plaintext_password'=>''
			]);
			/**
			 * Also set a default group if necessary:
			 */
			DB::table('users')
			->where('id', $new_user->id)
			->where('ctrl_group','')
            ->update([
            	'ctrl_group'=>'user'
            ]);

		}

		/**
		 * This is a hacky way to put the "Edit" page into "View" mode;
		 * what would the best approach be here? A function parameter?
		 * @var boolean
		 */
		$this->isViewingObject = false;
	}

	/**
	 * Build the menu and save it as a global view variable using View::share()
	 */
	protected function build_menu() {

		//if (is_null($user)) $user = Auth::user();

		// Build the menu
		$ctrl_classes = CtrlClass::where('menu_title','!=','')
					 	->orderBy('order')
					 	->orderBy('menu_title', 'ASC')
					 	->get();

		$menu_links      = [];

		foreach ($ctrl_classes as $ctrl_class) {


			if ($this->module->enabled('hide_menu_item')) {
				if ($this->module->run('hide_menu_item',[
					$ctrl_class
				])) {
					continue;
				}
			}

			$count_ctrl_class = $ctrl_class->get_class();

			if (!class_exists($count_ctrl_class)) {
				die("Error: cannot load class files.<br><br><code style='border: 1px solid #999; padding: 5px 10px;'>php artisan ctrl:synch files</code>");
			}

			$count = $count_ctrl_class::count();

			if ($this->module->enabled('permissions')) {
				$can_add = $this->module->run('permissions',[
					$ctrl_class->id,
					'add',
				]);
				$can_edit = $this->module->run('permissions',[
					$ctrl_class->id,
					'edit',
				]);
				$can_list = $this->module->run('permissions',[
					$ctrl_class->id,
					'list',
				]);

			}
			else {
				$can_list = $ctrl_class->can('list');
				$can_edit = $ctrl_class->can('edit');
				$can_add  = $ctrl_class->can('add');
			}

			/**
			 * Add items to the menu that indicate whether we can list items, and/or add or edit them.
			 * "Editing" is a special case, which we use when it's not possible to add or list items; this
			 * implies that we only have one record of a given type; such as, a "Settings" record for a single user.
			 * The logic will be, an "edit" link takes precedence over an "add" link; in fact, we should
			 * never have both present.
			 */


			if ($can_list) {
				$list_link  = route('ctrl::list_objects',$ctrl_class->id);
				if ($count > 0) {
					$list_title = 'View '.$count.' '.($count == 1 ? $ctrl_class->get_singular() : $ctrl_class->get_plural());
				}
				else {
					$list_title = 'No '.$ctrl_class->get_plural();
				}
			}
			else {
				$list_link = $list_title = false;
			}

			if ($can_add) {
				$add_link  = route('ctrl::edit_object',[$ctrl_class->id]);
				$add_title = 'Add '.$this->a_an($ctrl_class->get_singular()).' '.$ctrl_class->get_singular();
			}
			else {
				$add_link = $add_title = false;
			}

			// An "Edit" link in the context of menu_links is the option to edit just one single item'
			// for example, editing a "homepage" or "settings" record from a table.
			$first_object = $this->get_object_from_ctrl_class_id($ctrl_class->id,'first');

			if ($can_edit && !$can_list && !$can_add) {

				/**
				 * Surely we should always expect to find an item in this instance?
				 */
				if (is_null($first_object)) {
					trigger_error("No {$ctrl_class->name} object found");
					/**
					 * We could potentially create one here...?
					 */
				}

				$edit_link    = route('ctrl::edit_object',[$ctrl_class->id,$first_object->id]);
				$edit_title   = 'Edit the '.$ctrl_class->get_singular();
				$link_title = ucwords($ctrl_class->get_singular());
			}
			else {
				$edit_link = $edit_title = false;
				$link_title = ucwords($ctrl_class->get_plural());
			}

			// Note that we flag these links as "dashboard", to add them to the dashboard view
			// This relies on us having a "dashboard" flag in the ctrl_classes table, and having at least one item flagged as "dashboard"
			// This is because this flag is new; previously we listed everything on the dashboard, and we retain this old approach if necessary

			if ($can_edit || $can_list || $can_add) {


				$link = [
					'id'         => $ctrl_class->id,
					'title'      => $link_title,
					'icon'       => ($icon = $ctrl_class->get_icon()) ? '<i class="'.$icon.' fa-fw"></i> ' : '',
					'icon_only'  => ($icon = $ctrl_class->get_icon()) ? $icon : '',
					/* Replace all this with arrays for list, add and edit:
					'add_link'   => $add_link,
					'add_title'  => $add_title,
					'edit_link'  => $edit_link,
					'edit_title' => $edit_title,
					'list_link'  => $list_link,
					'list_title' => $list_title,
					*/
					'list'		 => [
						'title' => $list_title,
						'link'  => $list_link,
					],
					'edit'		 => [
						'title' => $edit_title,
						'link'  => $edit_link,
					],
					'add'		 => [
						'title' => $add_title,
						'link'  => $add_link,
					],
					'dashboard'  => $ctrl_class->flagged('dashboard')
				];


				$menu_links[$ctrl_class->menu_title][] = $link;
			}

		}
		// dd($menu_links);

		/**
		 * WIP: we need to make this array much more flexible, so that it can drive the main menu and
		 * the dashboard links. We need to store a "default" link (for the main menu?) and also make
		 * the name of the class (eg, "Homepages" or "Home page") separate from any buttons. I think?
		 * Or, do we just dynamically assess whether we have options to add, edit and/or list?
		 * They're the only three permutations, after all. No, I think we definitely need to strengthen
		 * the way in which we store information in menu_links.
		 */

		View::share ('menu_links', $menu_links );
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
				if ($ctrl_property->relationship_type == 'belongsTo' || $ctrl_property->relationship_type == 'belongsToMany') { // Can't be anything else, can it?
					$related_ctrl_class = CtrlClass::where('id',$ctrl_property->related_to_id)->firstOrFail(); // As above
					$related_class      = $related_ctrl_class->get_class();
					$related_object     = $related_class::where('id',$filter['value'])->firstOrFail();

					$description[] = "belonging to the ".strtolower($related_ctrl_class->get_singular()) ." <strong><a href=".route('ctrl::edit_object',[$related_ctrl_class->id,$related_object->id]).">".$this->get_object_title($related_object)."</a></strong>";
				}
			}
			$return = $this->comma_and($description);
		}
		return $return;
	}

	protected function _check_login($user = null) {

		if (is_null($user)) $user = Auth::user();

		$public_routes = ['ctrl::login','ctrl::post_login'];

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

			if ($this->can($ctrl_class->id,'export')) {
				$export_link  = route('ctrl::export_objects',[$ctrl_class->id]); // This omits the filter string; will we ever use this? Possible from an existing (filtered) list...
			}

			if ($this->can($ctrl_class->id,'import')) {
				$import_link  = route('ctrl::import_objects',[$ctrl_class->id]); // As above, this omits the filter string; will we ever use this?
			}

			if ($export_link || $import_link) {
				$import_export_links[] = [
					'id'          => $ctrl_class->id,
					'title'       => ucwords($ctrl_class->get_plural()),
					'icon'        => ($icon = $ctrl_class->get_icon()) ? '<i class="'.$icon.' fa-fw"></i> ' : '',
					'icon_only'   => ($icon = $ctrl_class->get_icon()) ? $icon : '',
					'export_link' => (!empty($export_link)) ? $export_link : false,
					'import_link' => (!empty($import_link)) ? $import_link : false
				];
			}
		}

		$dashboard_links = [];
		$menu_links = View::shared('menu_links'); // Pulling the shared menu_links view item back from the View; this is a bit clunky
		foreach ($menu_links as $menu_title=>$links) {
			foreach ($links as $link) {
				if ($link['dashboard']) {
					$dashboard_links[$menu_title][] = $link;
				}
			}
		}
		if (empty($dashboard_links)) { // The "dashboard" flag is new; if we don't have any, assume (for now) that we're running an older version of the CTRL database
			$dashboard_links = $menu_links;
		}

		// Add some custom links above the main set of links; we could theoretically use the manipulate_dom module for this:
		if ($this->module->enabled('custom_dashboard_links')) {
			$custom_dashboard_links = $this->module->run('custom_dashboard_links');
		}

		$view = view('ctrl::dashboard',[
			'logo'                   => config('ctrl.logo'),
			'import_export_links'    => $import_export_links,
			'dashboard_links'        => $dashboard_links,
			'custom_dashboard_links' => !empty($custom_dashboard_links) ? $custom_dashboard_links : []
		]);

		// Manipulate the dashboard to add custom content if necessary:
 		if ($this->module->enabled('manipulate_dom')) {
 			// Note that we need to pass some parameters here to prevent line breaks from being removed:
 			// str_get_html($str, $lowercase=true, $forceTagsClosed=true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT, $defaultSpanText=DEFAULT_SPAN_TEXT)
 			// From http://stackoverflow.com/questions/4812691/preserve-line-breaks-simple-html-dom-parser
 			$rendered_view = $view->render();
 			$dom = \KubAT\PhpSimple\HtmlDomParser::str_get_html($rendered_view, false, false, 'UTF-8', false);
			$view = $this->module->run('manipulate_dom',[
				$dom,
				'dashboard'
			]);
		}

		return $view;
	}


	/**
	 * Generate JSON data to populate a select2 box using Ajax
	 * @param  string $ctrl_class_name The name of the Ctrl class defining the objects we're loading
	 * @return json                       JSON data with id, text pairs
	 */
	public function get_select2(Request $request,$ctrl_class_name) {

		$json = [];

		// This is all based heavily on get_typeahead

		$ctrl_class = CtrlClass::where('name',$ctrl_class_name)->firstOrFail();
		$class      = $ctrl_class->get_class();

		// It's useful to know what class we're actually editing as well; this was a late addition
		// as it's only needed if we need to customise the returned data using a Module
		if ($editing = $request->input('editing')) {
			$editing_ctrl_class = CtrlClass::where('name',$editing)->firstOrFail();
		}

		// What are the searchable columns?
		$searchable_properties = $ctrl_class->ctrl_properties()->whereRaw(
		   '(find_in_set(?, flags))',
		   ['search']
		)->whereNull('relationship_type')->get();
			// I have no idea how to include searchable related columns in the query builder below...

		if (!$searchable_properties->isEmpty()) {

			$search_term = $request->input('q');

			$first_header_property = $ctrl_class->ctrl_properties()->whereRaw(
			   '(find_in_set(?, flags))',
			   ['header']
			)->orderBy('order')->first();

			$query = $class::query(); // From http://laravel.io/forum/04-13-2015-combine-foreach-loop-and-eloquent-to-perform-a-search

			if ($this->module->enabled('custom_select2')) {
				$query = $this->module->run('custom_select2',[
					$ctrl_class,
					$query,
					$editing_ctrl_class,
					$search_term
				]);
			}

			if (!empty($search_term)) {
				$query->where(function($query) use ($searchable_properties,$search_term) {
					foreach ($searchable_properties as $searchable_property) {
						$query->orWhere($searchable_property->name,'LIKE',"%$search_term%"); // Or would a %$term% search be better?
					}
				});
			}

			// $query->orderBy($first_header_property->name);
			// Wow, this actually works. Prefer matches at the start of a string, from:
			// http://stackoverflow.com/questions/6265544/how-to-prioritize-a-like-query-select-based-on-string-position-in-field
			// We need to handle relationships here though:
			if ($first_header_property->relationship_type == 'belongsTo') {
				$query->orderBy($first_header_property->foreign_key);
			}
			else if (!empty($search_term)) {
				// $query->orderByRaw("INSTR({$first_header_property->name}, '$search_term'), {$first_header_property->name}");
				// No, the above was mostly correct; if we escape the field names then the ordering has no effect
				// $query->orderByRaw("INSTR(?, ?), ?",[$first_header_property->name,$search_term,$first_header_property->name]);
				$query->orderByRaw("INSTR(`{$first_header_property->name}`, ?), `{$first_header_property->name}`",[$search_term]);
			}
			else {
				$query->orderBy($first_header_property->name);
			}

			$dump_query = false;
			if ($dump_query) {
				$sql = str_replace(array('%', '?'), array('%%', '\'%s\''), $query->toSql()); // Will this wrap integers in ''? Does that matter?
        		$bindings = $query->getBindings();
        		die(vsprintf($sql, $bindings).';'); // dd() wraps the query in "", which makes it tricky to copy and paste into Sequel Pro
        	}

			$objects = $query->take(50)->get();	// Limits the dropdown to 50 items; this may need to be adjusted
			if (!$objects->isEmpty()) {
			    foreach ($objects as $object) {
			    	$result            = new \StdClass;
			    	$result->id        = $object->id;
			    	$result->text      = $this->get_object_title($object);
			    	$json[]            = $result;
			    }
			}
		}
		else {
			trigger_error("Cannot search the class $class as no searchable properties are set");
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

		if (!$this->can($ctrl_class_id,'list')) return redirect()->route('ctrl::dashboard');

		// Convert the the $filter parameter into one that makes sense
		$filter_array = $this->convert_filter_string_to_array($filter_string);

		$filter_description = $this->describe_filter($filter_array);

		$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();

		// We need to include the correct header columns on the table
		// (Search in set code here: http://stackoverflow.com/questions/28055363/laravel-eloquent-find-in-array-from-sql)
		// Some minor duplication of code from get_data here:
		$headers = $ctrl_class->ctrl_properties()->whereRaw(
		   '(find_in_set(?, flags))', // Note that the bracket grouping is required: http://stackoverflow.com/questions/27193509/laravel-eloquent-whereraw-sql-clause-returns-all-rows-when-using-or-operator
		   ['header']
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
        	// Aha, this IS necessary; for example, when we want to only show the "Carousel" categories on Argos.
        	$make_searchable = true;
			$make_orderable = true;
			if ($header->field_type == 'image' || $header->field_type == 'video') {
				/**
				* It makes no sense to search image or video columns either
				* (assuming video columns will have a preview, as per ShowPonyPrep)
				* Ordering might make sense if you want to list all items without an image first...
				**/
				$make_searchable = false;
			}
			else {
        		foreach ($filter_array as $filter) {
        			if ($header->id == $filter['ctrl_property_id']) {
        				// This header (a header being a CTRL Property) exists in the filter array, so don't allow it to be searchable
        				$make_searchable = false;
        				break;
					}
        		}
        	}

			$column = new \StdClass;

        	if ($header->relationship_type) {

				if ($header->relationship_type == 'belongsToMany') {
					/**
					 * NOTE: we concatenate hasMany relationships into a single string value,
					 * and treat it as such. This works well BUT, as things stand, we can't
					 * search on that concatenated string; so hide the search box. See @get_data
					 * If this ever becomes an issue, we can probably fix the code elsewhere.
					 * I'm assuming it makes no sense to order these columns either
					 */
					$column->data = $header->name;
					$column->name = $ctrl_class->table_name.'.'.$header->name;
					$th_columns[] = '<th data-search-text="false" data-orderable="false">'.$header->label.'</th>';
				} else {

					$related_objects = $this->get_object_from_ctrl_class_id($header->related_to_id);

					$related_ctrl_class = CtrlClass::where('id',$header->related_to_id)->firstOrFail();
					$string = $related_ctrl_class->ctrl_properties()->whereRaw(
					'find_in_set(?, flags)',
					['string']
					)->firstOrFail();
					$value = $header->name.'.'.$string->name; // $header->name might not always hold true here?
					$column->data = $value;
					$column->name = $value;

					// Get around a problem with datatables if there's no relationship defined
					// See https://datatables.net/manual/tech-notes/4
					$column->defaultContent = 'None'; // We can't filter the list to show all "None" items though... not yet.

					// Only set data-search-dropdown (which converts the header to a dropdown) if we would have fewer than 50 items in the list:
					if ($related_objects::count() < 50) {
						$th_columns[] = '<th data-search-dropdown="'.($make_searchable ? 'true' : 'false').'" data-orderable="false">'.$header->label.'</th>';
					}
					else {
						$th_columns[] = '<th data-search-text="'.($make_searchable ? 'true' : 'false').'" data-orderable="'.($make_orderable ? 'true' : 'false').'">'.$header->label.'</th>';
					}
				}
        	} else {
        		$column->data = $header->name;
        		$column->name = $ctrl_class->table_name.'.'.$header->name;
        			// Again, see http://datatables.yajrabox.com/eloquent/relationships
        			// "Important! To avoid ambiguous column name error, it is advised to declare your column name as table.column just like on how you declare it when using a join statements."
        		if ($header->name == 'order') {
        			// A special case, we use this to allow the table to be reordered

        			// Allow us to override this with a module:
        			if ($this->module->enabled('prevent_reordering')) {
						$prevent_reordering = $this->module->run('prevent_reordering',[
							$ctrl_class->id,
							$filter_string
						]);
						if ($prevent_reordering) {
							continue; // This will omit to add the column to the headers or the JS
						}
					}


        			$th_columns[]          = '<th width="1" data-order-rows="true" _data-orderable="false" >'.$header->label.'</th>';
        			$column->orderSequence = ['asc'];
        				// I think it makes no sense to allow the "order" column to be reordered; it just confuses the user, as dragging and dropping items doesn't then have the expected results
        				// (Reordering items just swaps the relevant items, so if you reorder the list to put the last item first, then swap two items in the middle of the list, the last item is still last when you reload the page. If that makes sense. We could potentially reorder ALL items when you reorder anything, but this seems inefficient).
        					// We still have a bug whereby the sort icon disappears from the order column when we sort by another column though...?
        			$column->className      = "reorder";
        		}
        		else if ($header->field_type == 'checkbox') { // We convert these to Yes/No values, so allow a search dropdown...
	        		// $column->defaultContent = 'None'; // Necessary..?
        			$th_columns[] = '<th data-search-dropdown="'.($make_searchable ? 'true' : 'false').'" data-orderable="false">'.$header->label.'</th>';
        		}
        		else {
        			$th_columns[] = '<th data-search-text="'.($make_searchable ? 'true' : 'false').'" data-orderable="'.($make_orderable ? 'true' : 'false').'">'.$header->label.'</th>';
        		}

			}

        	$js_columns[] = $column;

        }
        if ($this->module->enabled('custom_columns')) {
			$custom_columns = $this->module->run('custom_columns',[
				$ctrl_class->id,
			]);
		}
		if (!empty($custom_columns)) {
			foreach ($custom_columns as $custom_column=>$details) {
				$cc 		  = new \StdClass;
		        $cc->data     = $custom_column;
		        $cc->name     = $custom_column;
		        $js_columns[] = $cc;
		        $th_columns[] = sprintf('<th %s>%s</th>',($details['searchable'] ? 'data-search-text="true"' : ''),$details['table_heading']);
			}
		}


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
    	// Why can't we just use $ctrl_class->table_name here?!
    	if (Schema::hasColumn($table, 'order') && empty($prevent_reordering)) {
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
        	// This only applies if we are filtering on a relationship property, so:
        	if (!empty($unfiltered_ctrl_property->relationship_type)) {
        		$unfiltered_ctrl_class    = CtrlClass::where('id',$unfiltered_ctrl_property->related_to_id)->firstOrFail();
        		$unfiltered_list_link     = route('ctrl::list_objects',[$unfiltered_ctrl_class->id]);
        	}
        }

        // Should we display a "View all" link? ie, is this list filtered, and are we allowed to list ALL items?
        if ($filter_array && $ctrl_class->menu_title) { // A crude way to test if we can list items; are we actually going to use the 'list' permission?
        	$show_all_link = route('ctrl::list_objects',$ctrl_class->id);
        }

		/* Think $this->can() replaces this:
        $can_add           = true;
        if ($this->module->enabled('permissions')) {
			$custom_permission = $this->module->run('permissions',[
				$ctrl_class->id,
				'add',
				// $filter_string
			]);
		}
		if (isset($custom_permission) && !is_null($custom_permission)) {
			$can_add = $custom_permission;
		}
		else if (!$ctrl_class->can('add')) {
			$can_add = false;
		}
		*/
		$can_add = $this->can($ctrl_class->id,'add');
		$add_link = $can_add ? route('ctrl::edit_object',[$ctrl_class->id,0,$filter_string]) : '';

		$can_export = $this->can($ctrl_class->id,'export');
		$export_link = $can_export ? route('ctrl::export_objects',[$ctrl_class->id,$filter_string]) : '';

		// Import button here untested!!
		$can_import = $this->can($ctrl_class->id,'import');
        $import_link = $can_import ? route('ctrl::import_objects',[$ctrl_class->id,$filter_string]) : '';

        //$key = 		$key = $this->get_row_buttons($ctrl_class->id,0,true);
        // Dropping the key, we don't use it; see notes elsewhere
        $key = false;

        // Per-page: could add a module here, but for now, just remove all pagination if we can reorder the table
		$page_length = ($can_reorder) ? false: 10; // 10 being the default

		if ($this->module->enabled('custom_css')) {
			$custom_css = $this->module->run('custom_css',[
				$ctrl_class,
				$filter_string
			]);
		}

		/**
		 * Force a default order in DataTables
		 */
		if ($this->module->enabled('defaultTableOrder')) {
			$defaultOrder = $this->module->run('defaultTableOrder',[
				$ctrl_class
			]);
		}

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
			'export_link'          => $export_link,
			'import_link'          => $import_link,
			'key'                  => $key,
			'page_length'          => $page_length,
			'custom_css'		   => (!empty($custom_css) ? $custom_css : false),
			'defaultOrder'         => (!empty($defaultOrder) ? $defaultOrder : false),
		]);
	}

	/**
	 * Export all objects of a given CtrlClass
	 * @param  integer $ctrl_class_id The ID of the class we're editing
	 * @param  string $filter_string Are we filtering the list? Currently stored as a ~ delimited list of property=>id comma-separated pairs; see below
	 *
	 * @return Response
	 */
	public function export_objects(Request $request, $ctrl_class_id) {

		ini_set("memory_limit",-1); // exporting large tables takes up loads of memory...
		set_time_limit(0); // ... and time

		// Check that we can specifically export this class:
		$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();
		if (!$ctrl_class->can('export')) {
			\App::abort(403, 'Access denied'); // As above
		}

		// Run the pre-import-function if necessary; this can either prep data, or truncate tables,
		// or (in the case of the Argos CAT sheet) bypass the Excel import altogether

		if ($pre_export_function = $this->module->run('export_objects',[
			'get-pre-export-function',
			$ctrl_class->id
		])) {
			if ($response = $pre_export_function($ctrl_class->id)) {
				return $response;
			}
		}

		// This is all very basic, although we can customise the headers
		// and run a pre-export function (that could modify data, for example)

		$headers = $this->module->run('export_objects',[
			'get-headers',
			$ctrl_class->id,
			// $filter_string
		]);

		$class   = $ctrl_class->get_class();

		if (!empty($headers)) {
			$objects = $class::select($headers);
		}
		else {
			$objects = $class::select();
		}

		if ($this->module->enabled('filter_export_objects')) {
			$objects = $this->module->run('filter_export_objects',[
				'ctrl_class_id' => $ctrl_class->id,
				'objects'    	=> $objects
			]);
		}

		$objects = $objects->get();

		if ($this->module->enabled('manipulate_export_objects')) {
			/**
			 * See Russell & Russell
			 */
			$objects = $this->module->run('manipulate_export_objects',[
				'ctrl_class_id' => $ctrl_class->id,
				'objects'    	=> $objects
			]);
		}

		/** Old code for previous Maat Excel
		$filename = 'export-'.Str::slug($ctrl_class->get_plural());

		\Maatwebsite\Excel\Facades\Excel::create($filename, function($excel) use ($objects) {
		    $excel->sheet('sheet_1', function($sheet) use ($objects) {
        		$sheet->fromModel($objects);
    		});
		})->download('csv');
		**/

		$filename = 'export-'.Str::slug($ctrl_class->get_plural()).'.csv';
		return $objects->downloadExcel(
            $filename,
			\Maatwebsite\Excel\Excel::CSV,
			true
        );
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

			// Dummy comment to force a push to Packagist

		$filename = 'import-'.Str::slug($ctrl_class->get_plural()).'-example';

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
	 * Generic permissions function that references the permissions module
	 * @param  integer $ctrlclass_id The ID of the Ctrl Class
	 * @param  string $action       The action we're trying to carry out
	 * @return boolean
	 */
	protected function can($ctrl_class_id,$action) {
		$can = true;
		try {
			$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();
		}
		catch (\Exception $e) {
			trigger_error($e->getMessage());
		}

        if ($this->module->enabled('permissions')) {
			$custom_permission = $this->module->run('permissions',[
				$ctrl_class->id,
				$action,
				// $filter_string
			]);
		}
		if (isset($custom_permission) && !is_null($custom_permission)) {
			$can = $custom_permission;
		}
		else if (!$ctrl_class->can($action)) {
			$can = false;
		}
		return $can;
	}

	/**
	 * Handle the posted files when importing all objects of a given CtrlClass, as 'files'; called by @import_objects_process
	 * @param  integer $ctrl_class_id The ID of the class we're editing
	 * @param  string $filter_string Are we filtering the list? Currently stored as a ~ delimited list of property=>id comma-separated pairs; see below
	 *
	 * @return Response
	 */
	protected function import_objects_process_files(Request $request, $ctrl_class_id, $filter_string = NULL) {
		$this->validate($request, [
        	'files-import'=>'required'
        ],[
		    'files-import.required' => 'Please select some files to upload'
		]);

		// Filter the array to remove empty values; we always have an empty initial value because of the way that the Krajee upload works
		// (ie, we clone the input element for each Ajax response post-upload, but that leaves the orginal node empty)
		$files = array_filter($request->input('files-import'));

		$callback_function = $this->module->run('import_objects',[
			'get-callback-function',
			$ctrl_class_id,
			// $filter_string // required?
		]);

		$count = $callback_function($files);

		/* At some point it'd make sense to run a rudimentary security check; this will need tweaking if we ever use a path other than /uploads for uploads. Realistically, though, this is only needed if someone is actively trying to hack the CMS:
		foreach ($files as $file) {
			if (strpos($file, '/uploads') !== 0) continue;
		}
		*/

		if (!empty($errors)) {
			return response()->json($errors,422);
       	}
       	else {
       		$message  = $count . ' files imported';
       		$messages = [$message];
       		$request->session()->flash('messages', $messages);
       		$back = route('ctrl::import_objects',[$ctrl_class_id, $filter_string]);
       		return response()->json(['redirect'=>$back]);
       	}
	}

	/**
	 * Handle the posted CSV when importing all objects of a given CtrlClass, as DATA; called by @import_obects_process
	 * @param  integer $ctrl_class_id The ID of the class we're editing
	 * @param  string $filter_string Are we filtering the list? Currently stored as a ~ delimited list of property=>id comma-separated pairs; see below
	 *
	 * @return Response
	 */
	protected function import_objects_process_data(Request $request, $ctrl_class_id, $filter_string = NULL) {
		$this->validate($request, [
        	'csv-import'=>'required'
        ],[
		    'csv-import.required' => 'Please select a CSV file to upload'
		]);

		$csv_file = trim($request->input('csv-import'),'/');
		$errors = [];

		// Convert .txt files into .csv; this is because Office can export .txt files in UTF8 (UTF16, in fact) but not .csv
		$converted = $this->convert_txt_to_csv($csv_file);

		// Work out what headers we need, what the callback functions are, whether we have a "pre-import" function, etc:
		$required_headers = $this->module->run('import_objects',[
			'get-headers',
			$ctrl_class_id,
			// $filter_string // required?
		]);

		// Convert all headers into slugged values, as per http://www.maatwebsite.nl/laravel-excel/docs/import#results
		// Excel does this on import automatically, so we need compare slugged values with the headers Excel has converted
		// Technically this uses the protected function Excel::getSluggedIndex()
		// but it's essentially the same as Laravel's Str::slug():
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

    	Excel::filter('chunk')->load(storage_path('app/public/'.$csv_file))->chunk(250, function($results) use (
    		&$count,
    		$loop, // Does this need to be passed by reference?
    		$ctrl_class_id,
    		&$errors,
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

		$import_type = $this->module->run('import_objects',[
			'get-import-type',
			$ctrl_class_id,
		]);

		if ($import_type == 'data') {
			return $this->import_objects_process_data($request, $ctrl_class_id, $filter_string);
		}
		else if ($import_type == 'files') {
			return $this->import_objects_process_files($request, $ctrl_class_id, $filter_string);
		}
		else {
			dd("Unrecognised import type $import_type");
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

		// Check that we can specifically import this class:
		$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();
		if (!$ctrl_class->can('import')) {
			\App::abort(403, 'Access denied'); // As above
		}

		$back_link   = route('ctrl::list_objects',[$ctrl_class->id,$filter_string]); // Should this actually link back to the dashboard, as this is where we currently link TO @import FROM?
		$save_link   = route('ctrl::import_objects_process',[$ctrl_class->id,$filter_string]);
		$sample_link = route('ctrl::import_objects_sample',[$ctrl_class->id,$filter_string]);

		// Now. We can repurpose this function to allow files and images to be uploaded in bulk too.
		$import_type = $this->module->run('import_objects',[
			'get-import-type',
			$ctrl_class_id,
		]); // Should default to 'data', can also be 'files' or 'images'

		if ($import_type == 'data') {
			$upload_field = [
				'id'            => 'csv-import',
				'name'          => 'csv-import',
				'type'          => 'file',
				'template'      => 'krajee',
				'value'         => '',
				'allowed-types' => ['text'] /* Only allow text files for CSV upload */
			];
			$page_description = 'Use this page to import records from a CSV file';

			/**
			 * Allow this text to be customised...
			 */
			if ($this->module->enabled('custom_strings')) {
				$custom_help_text = $this->module->run('custom_strings',[
					$ctrl_class_id,
					'import_objects.help_text'
				]);
			}
			if (!empty($custom_help_text)) {
				$help_text = $custom_help_text;
			}
			else {
				$help_text = 'Please select a CSV file from your computer by clicking "Browse", and then click "Import". <a href="'.$sample_link.'">You can download an example CSV here</a>.';
			}

		}
		else if ($import_type == 'files') {
			$upload_field = [
				'id'             => 'files-import',
				'name'           => 'files-import',
				'type'           => 'file',
				'template'       => 'krajee',
				'value'          => '',
				'allow-multiple' => true,
				'tip'  			 => 'You can select multiple files by holding down the Command (&#8984;) or CTRL key.'
			];
			$page_description = 'Use this page to import multiple '.$ctrl_class->get_plural() .' at the same time';
			$help_text = 'Please select files from your computer by clicking "Browse", and then click "Import".';
		}
		else {
			dd("Unrecognised import type $import_type");
		}

		return view('ctrl::upload_file',[
			'icon'             => $ctrl_class->get_icon(),
			'page_title'       => "Import ".ucwords($ctrl_class->get_plural()),
			'page_description' => $page_description,
			'help_text'        => $help_text,
			'back_link'        => $back_link,
			'save_link'        => $save_link,
			'form_field'       => $upload_field,
		]);
	}

	/**
	 * Populate the dropdown filters used by datatables
	 * This is required because the automatic way to do it is to take unique values from the column, but on the given page of the table only
	 * This means that the dropdown list is usually truncated
	 * @param  integer $ctrl_class_id The ID of the class we're editing
	 * @param  string $filter Optional list filter, passed in from the datatables Ajax call; NOT CURRENTLY USED
	 *
	 * @return [type]                [description]
	 *
	 */
	public function populate_datatables_dropdowns(Request $request, $ctrl_class_id, $filter_string = NULL) {

		/*
		OK. THis is partly written. I was trying to build this for the Argos products:brand column, but then I realised that we have 5000 brands, and listing them all in a dropdown won't work. Instead, I need to abandon the dropdown altogether, and make it a searchable field.
		However, this does have potential, if we're listing items with only 10 or 20 related items, but with the old problem that not all related values appear on page one of the table.
		We'll need to get this function to return valid JSON data, and then use this data to populate the dropdowns; see list_objects.blade.php.
		*/

		$filter_array = $this->convert_filter_string_to_array($filter_string);
		try {
			$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();
		}
		catch (\Exception $e) {
			trigger_error($e->getMessage());
		}
		$class      = $ctrl_class->get_class();

		$data_src = $request->input('data_src');
		$options = [];

		if (strpos($data_src, '.')) { // Suggests a related property, like brand.title

			list($related_ctrl_class_name,$related_ctrl_property_name) = explode('.', $data_src);

			// This should give us an array that looks like ['brand','title']

			// So, load all "title" values of the "brand" property for the current ctrl_class:

			$related_ctrl_property = CtrlProperty::where('name',$related_ctrl_class_name)
													->where('ctrl_class_id',$ctrl_class->id)
													->first();
			if (is_null($related_ctrl_property)) trigger_error("Cannot load related_ctrl_property");

			$related_ctrl_class = CtrlClass::where('id',$related_ctrl_property->related_to_id)->first();
			if (is_null($related_ctrl_class)) trigger_error("Cannot load related_ctrl_class");

			$related_items = DB::table($related_ctrl_class->table_name)->select('id',$related_ctrl_property_name)->orderBy($related_ctrl_property_name)->get(); // Previously had ->distinct() here but shouldn't be necessary for related items...

			// WIP, untested
			// I've refined this to include the correct ->id value, but it's still not quite working...
			foreach ($related_items as $related_item) {
				// $options[$related_item->id] = $related_item->$related_ctrl_property_name;
				// Or do we need to search by string?
				// Ah, yes, apparently -- this is flaky though, we should update this to use IDs for relationships
				$options[$related_item->$related_ctrl_property_name] = $related_item->$related_ctrl_property_name;
			}

		}
		else {
			$distinct_values = DB::table($ctrl_class->table_name)->select($data_src)->distinct()->get();

			foreach ($distinct_values as $distinct_value) {
				$options[] = $distinct_value->$data_src;
			}

			if ($options == [1,0] || $options == [0,1]) {
				$options = [0=>'No',1=>'Yes'];
			}
		}

		$status = 200;
		$json = [
            'options' => $options
        ];
       	return \Response::json($json, $status);

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

      		// Fail, we can't implode multiple $with values, we have to pass multiple arguments, which won't work :-(
			// $objects = $class::with(implode(',', $with))->select($ctrl_class->table_name.'.*');

			// Try this? I'm pretty sure this works:
			$objects = $class::select($ctrl_class->table_name.'.*');
			foreach ($with as $w) {
				$objects->with($with);
			}
			// TODO: in fact, we could call $class::select() first, and then only run with (if $with)
		}
		else {
			// $objects    = $class::query();
			// I think that select() and query() just return a Query Builder object, so both can be used here
			// Taking this approach means that we can add extra selects (ie, extra columns) via a module though
			$objects    = $class::select($ctrl_class->table_name.'.*');
		}

		if ($this->module->enabled('custom_columns')) {
			$custom_columns = $this->module->run('custom_columns',[
				$ctrl_class_id,
			]);
		}
		if (!empty($custom_columns)) {
			foreach ($custom_columns as $custom_column=>$details) {

				if (!empty($details['raw_sql'])) {
					$add_select = \DB::raw(sprintf('(%s) AS `%s`',$details['raw_sql'],$custom_column));
					$objects->addSelect($add_select);
				} else {
					$add_select = \DB::raw(sprintf('(\'empty\') AS `%s`',$custom_column));
					$objects->addSelect($add_select);
				}

				/**
				 * Add these to the headers array so that we can still manipulate the value in custom_column_values()
				 */
				$custom_header             = new CtrlProperty();
				$custom_header->field_type = 'custom';
				$custom_header->name       = $custom_column;
				$headers[] = $custom_header;
			}
		}

		// See http://datatables.yajrabox.com/eloquent/dt-row for some good tips here

		// Known issue, that I'm struggling to resolve; if we have a dropdown to search related fields, but there's no relationship for an object, we can't select the "empty" value and show all items without a relationship. TODO.

		// WIP. This is very niche, but if someone searches for "None" on a searchable relationship field (eg, product:brand on Argos), can we match "empty" values?
		/* No, drop this, it doesn't work at all:
		foreach ($_GET['columns'] as &$column) {
			if (!empty($column['searchable']) && $column['search']['value'] == 'None') {
				$column['search']['value'] = '';
			}
		}
		*/

	/**
	 * TODO: establish what image and file columns we have here, and then call editColumn dynamically below;
	 */
		$imageColumns   = [];
		$videoColumns   = [];
		$fileColumns    = [];
		$hasManyColumns = [];
		$rawColumns     = ['order','action'];  // Columns that allow raw HTML
		foreach ($headers as $header) {
			switch ($header->field_type) {
				case 'image':
					$imageColumns[] = $header->name;
					$rawColumns[]   = $header->name;
					break;
				case 'video':
					$videoColumns[] = $header->name;
					$rawColumns[]   = $header->name;
					break;
				case 'file':
					$fileColumns[] = $header->name;
					$rawColumns[]  = $header->name;
					break;
			}
			/**
			 * This is a hasMany relationship, so we need to concatenate this into a single string value
			 */
			if ($header->relationship_type == 'belongsToMany') {
				$hasManyColumns[] = $header;
				// Don't need $rawColumns, that just allows us to render HTML in the table cell
			}
		}
        $datatable = DataTables::of($objects)
        	->setRowId('id') // For reordering
        	->editColumn('order', function($object) { // Set the displayed value of the order column to just show the icon
        		return '<i class="fa fa-reorder"></i>';
        	}) // Set the displayed value of the order column to just show the icon
        	// ->editColumn('src', '<div class="media"><div class="media-left"><a href="{{$src}}" data-toggle="lightbox" data-title="{{$src}}"><img class="media-object" src="{{$src}}" height="30"></a></div><div class="media-body" style="vertical-align: middle">{{$src}}</div></div>') // Draw the actual image, if this is an image field
        ;
        foreach ($imageColumns as $imageColumn) {
        	$datatable->editColumn($imageColumn, function($object) use ($imageColumn) {
	    		if ($src = $object->$imageColumn) {

					if (strpos($src,'http') === 0) {
						// Remote file, treat as is
						$url = $src;
					}
					else {
						/*
						$path = storage_path('app/public/'.ltrim($src,'/'));
						$url  = asset('storage/'.ltrim($src,'/'));
						if (!file_exists($path)) {
							return 'Image missing <!--'.$path.'-->';
						}
						*/
						/**
						 * Better approach:
						 */
						$path = Storage::disk('public')->path($src);
						$url  = Storage::disk('public')->url($src);
						if (!Storage::disk('public')->exists($src)) {
							return 'Image missing <!--'.$path.'-->';
						}
					}
					$path_parts = pathinfo($url);
					$basename   = Str::limit($path_parts['basename'],20);

					return sprintf('<div class="media"><div class="media-left"><a href="%1$s" data-toggle="lightbox" data-title="%2$s"><img class="media-object" src="%1$s" height="30"></a></div></div>',$url, $basename);
				}
        	});
		}
		foreach ($videoColumns as $videoColumn) {
        	$datatable->editColumn($videoColumn, function($object) use ($videoColumn) {
	    		if ($video_src = $object->$videoColumn) {
					/**
					 * Do we have a corresponding _thumbnail field? If so, use that:
					 */
					$thumbnail = $videoColumn.'_thumbnail';
					if (!empty($object->$thumbnail)) {
						$src = $object->$thumbnail;
						if (strpos($src,'http') === 0) {
							// Remote file, treat as is
							$url = $src;
						}
						else {
							/*
							$path = storage_path('app/public/'.ltrim($src,'/'));
							$url  = asset('storage/'.ltrim($src,'/'));
							if (!file_exists($path)) {
								return 'Image missing <!--'.$path.'-->';
							}
							*/
							/**
							 * Better approach:
							 */
							$path = Storage::disk('public')->path($src);
							$url  = Storage::disk('public')->url($src);
							if (!Storage::disk('public')->exists($src)) {
								return 'Image missing <!--'.$path.'-->';
							}
						}

						return sprintf('<div class="media"><div class="media-left"><a href="#" class="videoModal" data-toggle="modal" data-target="#videoModal" data-video="%1$s"><img class="media-object" src="%2$s" height="30"></a></div></div>',Storage::disk('public')->url($video_src), $url);
					} else {
						return sprintf('<a href="#" class="videoModal" data-toggle="modal" data-target="#videoModal" data-video="%1$s">%2$s</a>',Storage::disk('public')->url($video_src), $video_src);
					}
				}
        	});
        }
        foreach ($fileColumns as $fileColumn) {
        	$datatable->editColumn($fileColumn, function($object) use ($fileColumn) {  // If we have a "file" column, assume it's a clickable link. DEFINITELY need to query ctrlproperty->type here,see 'src' above:
	    		if ($file = $object->$fileColumn) {

	    			$path = storage_path('app/public/'.ltrim($file,'/'));
	    			$url  = asset('storage/'.ltrim($file,'/'));

	    			$path_parts = pathinfo($url);
	    			$basename   = Str::limit($path_parts['basename'],20);

	    			if (!file_exists($path)) {
	    				return 'File missing';
	    			}
	    			else {
						return sprintf('<i class="fa fa-download"></i> <a href="%1$s">%2$s</a>',$url, $basename);
					}
				}
        	});
		}

		foreach ($hasManyColumns as $hasManyColumn) {
			$property = $hasManyColumn->name;
			$datatable->editColumn($property, function($object) use ($property) {
				$objects = $object->$property;
				if ($objects->count() > 0) {
					$string = [];
					foreach ($objects as $object) {
						$string[] = $this->get_object_title($object);
					}
					return implode(', ', $string);
				} else {
					return 'None';
				}
			});
		}

        $datatable->addColumn('action', function ($object) use ($ctrl_class, $filter_string) {
            	return $this->get_row_buttons($ctrl_class->id, $object->id, $filter_string);
            })
            ->rawColumns($rawColumns) // Allow HTML in columns; see https://github.com/yajra/laravel-datatables/issues/949
            // Is this the best place to filter results if necessary?
            // I think so. See: http://datatables.yajrabox.com/eloquent/custom-filter
        	->filter(function ($query) use ($filter_array, $ctrl_class, $filter_string) {
	            if ($filter_array) {
	            	foreach ($filter_array as $filter) {
						$filter_ctrl_property = CtrlProperty::where('id',$filter['ctrl_property_id'])->firstOrFail(); // This throws a 404 if not found; not sure that's strictly what we want

						if (!empty($filter_ctrl_property->relationship_type)) {
							$related_ctrl_class = CtrlClass::where('id',$filter_ctrl_property->related_to_id)->firstOrFail(); // As above
							$related_class      = $related_ctrl_class->get_class();
							$related_object     = $related_class::where('id',$filter['value'])->firstOrFail();

							if ($filter_ctrl_property->relationship_type == 'belongsTo') {
								// Duplication of code from @describe_filter here
								$query->where($filter_ctrl_property->foreign_key,$related_object->id);
							}
							else if ($filter_ctrl_property->relationship_type == 'belongsToMany') {
								// We need to join the query here, to generate something like this (from Argos, listing products related to a cached profile)
								/*
								$query->join('product_profile_cache', 'id', '=', $filter_ctrl_property->join_table.'product_profile_cache.product_id')
	                    			  ->where('product_profile_cache.profile_id',$related_object->id);
								 */
								// Technically 'id' here is 'products.id'; is 'id' always unambiguous?
								$query->join($filter_ctrl_property->pivot_table, 'id', '=', $filter_ctrl_property->pivot_table.'.'.$filter_ctrl_property->local_key)
	                    			  ->where($filter_ctrl_property->pivot_table.'.'.$filter_ctrl_property->foreign_key,$related_object->id);

		            		}
		            	}
		            	else {
		            		// Filter by property...
		            		$query->where($filter_ctrl_property->name,$filter['value']);
		            	}
	            	}
	            	//$query->where('title','LIKE',"%related%");
	            }
	            // $query->where('title','NOT LIKE',"Repair profile%");
	            if ($this->module->enabled('custom_filter')) {
					$this->module->run('custom_filter',[
						$ctrl_class,
						$query,
						$filter_string
					]);
				}

	        });

        /* Ah, this is the WRONG way to add a column. Instead, add it to the main ::select() routines above:
        $datatable->addColumn('product_count', function ($object) use ($ctrl_class) {
        	return DB::table('product_profile_cache')->where('profile_id',$object->profile_id)->count();
        });
        */

        // Can we filter certain columns here though? Try this:
        foreach ($headers as $header) {
        	$property = $header->name;
			if ($header->field_type == 'checkbox') {
				// Convert checkboxes to yes/no
				$datatable->editColumn($property, function($object) use ($property) {
		    		if ($object->$property) {
		    			return 'Yes';
					}
					else {
						return 'No';
					}
	        	});
			}
			else if ($header->field_type == 'date') {
				$datatable->editColumn($property, function($object) use ($property) {
		    		return \Carbon\Carbon::parse($object->$property)->format('jS F Y');
	        	});
			}
			else if ($header->field_type == 'datetime') {
				$datatable->editColumn($property, function($object) use ($property) {
		    		return \Carbon\Carbon::parse($object->$property)->format('jS F Y \a\t H:i');
	        	});
			}

			if ($this->module->enabled('custom_column_values')) {
				$this->module->run('custom_column_values',[
					$ctrl_class,
					$header,
					&$datatable
				]);
			}
		}

	    return $datatable->make(true);

		// return Datatables::of($objects)->make(true);
	}

	/**
	 * Return the row buttons for the row that holds object $object_id of ctrl_class $ctrl_class_id
	 * @param  integer $ctrl_class+id
	 * @param  integer $object_id
	 * @param  string $filter_string Optional list filter
	 * @param  string $scope Indicates where we're displaying buttons; can currently be 'list' or 'edit'(ie, listing or editing objects; 'edit' hides the 'edit' option)
	 * @return string HTML
	 */
	// protected function get_row_buttons($ctrl_class_id,$object_id, $key = false) {

	// Right. I'm dropping $key, partly because we don't really use it (it looks rubbish) and partly because passing in $key is really clunky
	// If we ever do need to format buttons differently for a "key", this function needs to be split into two; one to retrieve the buttons, and one to display them
	protected function get_row_buttons($ctrl_class_id,$object_id, $filter_string = null, $scope = 'list') {

		/**
		 * We draw "row buttons" on the edit page; we don't want these to display when adding a new object, though
		 */
		if (!$object_id) return false;

		$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();

		// TODO: check permissions using the module here if necessary
		if ($scope != 'edit' && $ctrl_class->can('edit')) {
    		$edit_link   = route('ctrl::edit_object',[$ctrl_class->id,$object_id,$filter_string]);
    	}
    	else {
    		$edit_link = false;
    	}

		// Check permissions:

		/**
		 * TODO: OK, this needs work. We want to check permissions here for Argos
		 * so that we can hide the delete flag IF we're listing products within
		 * a cached list. However, the get_data function has no concept of
		 * whether we're listing products or not, so the current hook
		 * (that hides the Add button) can't work. Hmmm.
		 */
		if ($this->module->enabled('permissions')) {
			$can_delete = $this->module->run('permissions',[
				$ctrl_class->id,
				'delete',
			]);
		}
		else {
			$can_delete = $ctrl_class->can('delete');
		}

    	if ($can_delete) {
    		$delete_link = route('ctrl::delete_object',[$ctrl_class->id,$object_id]);
    	}
    	else {
    		$delete_link = false;
    	}

    	// TODO: check permissions using the module here if necessary
    	if ($scope != 'view' && $ctrl_class->can('view')) {
    		$view_link = route('ctrl::view_object',[$ctrl_class->id,$object_id]);
    	}
    	else {
    		$view_link = false;
    	}

    	// Do we have any filtered lists?
    	$filtered_list_links        = [];
    	$filtered_list_properties = $ctrl_class->ctrl_properties()->whereRaw(
		   '(find_in_set(?, flags))',
		   ['filtered_list']
		// )->where('relationship_type','hasMany')->get(); // I think a filtered list will always be "hasMany"?
		)->get(); // maybe not...
    	foreach ($filtered_list_properties as $filter_ctrl_property) {
    		// Build the filter string
    		/*
    		 Now, we need the INVERSE property here. That is:
    		 	- If we're loading a Test record, with a "Many" property set to "filtered_list"
    		 	- We need to find the "test" property of the "Many" object, so that we can show Many items where "test" is the value of this object
    		 I believe we can do this by matching the foreign key
    		 */

			if ($filter_ctrl_property->relationship_type == 'belongsTo') {
				/**
				 * TODO: add a single edit link here, it's a "filtered list" of 1
				 */

				$related_ctrl_class = CtrlClass::where('id',$filter_ctrl_property->related_to_id)->firstOrFail();

				$class  = $ctrl_class->get_class();
				$object = $class::where('id',$object_id)->firstOrFail();

				$related_object_id = $object->{$filter_ctrl_property->foreign_key};

				if ($related_object_id) {
					$filter_list_link      = route('ctrl::edit_object',[$related_ctrl_class->id, $related_object_id]);
					$count = 1;
				} else {
					// Will we always want to add new objects in this scenario...?
					/**
					 * Now, this is tricky. We need to understand what we're filtering on;
					 * we're listing (eg) individuals, linking to their smart match, but we need to
					 * identify the "individual" property of a "smart match". I don't think we know that here.
					 * This should load it but I don't know if it's reliable...
					 */
					$filtered_property  = CtrlProperty::where('ctrl_class_id', $filter_ctrl_property->related_to_id)->where('related_to_id', $filter_ctrl_property->ctrl_class_id)->first();
					$filter_string    = implode(',',[$filtered_property->id,$object->id]);
					$filter_list_link = route('ctrl::edit_object', [$related_ctrl_class->id, 0, $filter_string]);
					$count            = 0;
				}
				$filter_list_title     = $filter_ctrl_property->label ? $filter_ctrl_property->label : ucwords($related_ctrl_class->get_plural());

				$filtered_list_links[]  = [
					'icon'       => $related_ctrl_class->get_icon(),
					'count'      => $count,
					'title'      => $filter_list_title,
					'list_link'  => $filter_list_link,
				];
			} else {


    		try {
    			if ($filter_ctrl_property->relationship_type == 'hasMany') {
    				$inverse_filter_ctrl_property = CtrlProperty::where('ctrl_class_id',$filter_ctrl_property->related_to_id)
    														->where('related_to_id',$filter_ctrl_property->ctrl_class_id)
    														->where('foreign_key',$filter_ctrl_property->foreign_key) // Necessary?
    														->firstOrFail();
    			}
    			else if ($filter_ctrl_property->relationship_type == 'belongsToMany') {
    				$inverse_filter_ctrl_property = CtrlProperty::where('ctrl_class_id',$filter_ctrl_property->related_to_id)
    														->where('related_to_id',$filter_ctrl_property->ctrl_class_id)
    														->where('pivot_table',$filter_ctrl_property->pivot_table)
    														->firstOrFail();
    			}
    			else {
    				throw new \Exception('Cannot set up a filtered list for a '.$filter_ctrl_property->relationship_type.' relationship.');
    			}

    		}
    		catch (\Exception $e) {
    			trigger_error($e->getMessage());
    		}


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

			// Need to vary this if we're counting belongsToMany:
			if ($filter_ctrl_property->relationship_type == 'hasMany') {
				$count_objects    = $count_class::where($inverse_filter_ctrl_property->foreign_key,$filtered_list_array['value']);
				$count            = $count_objects->count();
			}
			else if ($filter_ctrl_property->relationship_type == 'belongsToMany') {
				$count = DB::table($filter_ctrl_property->pivot_table)->where($inverse_filter_ctrl_property->foreign_key,$filtered_list_array['value'])->count();
			}
			else {
				trigger_error("Cannot process relationship_type");
			}

			/*
			if ($count > 0) {
				$filter_list_title = 'View '.$count . ' '.($count == 1 ? $filter_ctrl_class->get_singular() : $filter_ctrl_class->get_plural());
				$filter_list_link  = route('ctrl::list_objects',[$filter_ctrl_property->related_to_id,$filtered_list_string]);
			}
			else {
				$filter_list_title = 'No '.$filter_ctrl_class->get_plural();
				$filter_list_link  = false;
			}
			*/

			// No longer used
			// $filter_add_title = 'Add '.$this->a_an($filter_ctrl_class->get_singular()).' '.$filter_ctrl_class->get_singular();
			// $filter_add_link  = route('ctrl::edit_object',[$filter_ctrl_property->related_to_id,0,$filtered_list_string]); // TODO check permissions here; can we add items?

			// New: always link to the filtered list, regardless of whether we have any related items:
			$filter_list_link  = route('ctrl::list_objects',[$filter_ctrl_property->related_to_id,$filtered_list_string]);
			$filter_list_title = $filter_ctrl_property->label ? $filter_ctrl_property->label : ucwords($filter_ctrl_class->get_plural());

        	$filtered_list_links[]  = [
    			'icon'       => $filter_ctrl_class->get_icon(),
    			'count'      => $count,
    			'title'      => $filter_list_title,
    			// 'list_title' => $filter_list_title, // Not used
    			'list_link'  => $filter_list_link,
    			// 'add_title'  => $filter_add_title, // No longer used
    			// 'add_link'   => $filter_add_link, // No longer used
    		];
			}

		}

		// Do we have any properties we can toggle?
    	$toggleLinks       	= [];
    	$toggleProperties 	= $ctrl_class->ctrl_properties()->whereRaw(
		   '(find_in_set(?, flags))',
		   ['toggle']
		)->get();
		if ($toggleProperties) {
			$class  = $ctrl_class->get_class();
			$object = $class::where('id',$object_id)->firstOrFail();
			foreach ($toggleProperties as $toggleProperty) {
				$column = $toggleProperty->name;
				$state  = $object->$column; // Assume this is a 1 or 0 state for now

				$toggleLinks[]  = [
					'class'      => $state ? 'btn-warning' : 'btn-default',
					'icon'       => $state ? 'fa fa-check-square-o' : 'fa fa-square-o',
					'title'      => $toggleProperty->label,
					'link'		 => route('ctrl::update_object',[$ctrl_class->id,$object->id]),
					'rel'		 => implode('~',[$toggleProperty->id, $state ? 0 : 1])
				];
			}
		}

    	// Any custom buttons?
    	if ($this->module->enabled('custom_buttons')) {
			$custom_buttons = $this->module->run('custom_buttons',[
				$ctrl_class_id,
				$object_id,
				$filter_string
			]);
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

    	if (!empty($key)) { // No longer used, see notes elsewhere
    		$template = 'ctrl::tables.row-buttons-key';
    	}
    	else {
    		$template = 'ctrl::tables.row-buttons';
    	}


    	$buttons = view($template, [
    		'view_link'           => $view_link,
    		'edit_link'           => $edit_link,
    		'delete_link'         => $delete_link,
			'filtered_list_links' => $filtered_list_links,
			'toggleLinks' 		  => $toggleLinks,
    		'custom_buttons'      => isset($custom_buttons) ? $custom_buttons : [],
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
		if ($object_id == 'first') {
			$object     = $class::first();
		}
		else if ($object_id && is_numeric($object_id)) {
			try {
				$object     = $class::where('id',$object_id)->firstOrFail();
			}
			catch (\Exception $e) {
				trigger_error($e->getMessage());
			}
		}
		else {
			$object     = new $class;
		}

		return $object;
	}

	/**
	 * Convert a text export from Office 2013 (which can be in UTF8 format, unlike CSV exports from the same) into CSV
	 * @param  string $csv_file A file path
	 * @return boolean
	 */
	protected function convert_txt_to_csv($csv_file) {
		// From http://stackoverflow.com/questions/12489033/php-modify-a-single-line-in-a-text-file

		ini_set("memory_limit",-1); // mb_convert_encoding takes up loads of memory...

		// How is the current file encoded? Note that this is different to the value separator;
		// but Office exports tab-delimited files as UTF-16, which we can't run through INFILE, so we need to convert it
		$current_encoding = $this->detect_utf_encoding($csv_file);

		$fh = fopen(storage_path('app/public/'.$csv_file),'r+');

		$csv_rows = '';

		$loop = 0;
		while(!feof($fh)) {
			$row = fgets($fh);
			if ($loop++ == 0) { // Check first line to see if this is a tab-delimited file
				$commas = substr_count($row,',');
				$tabs   = substr_count($row,"\t");
				if ($commas >= $tabs) return false; // No need to change this file, it's already comma-delimited as far as we can tell
			}
			// Attempting to remove Windows-style endings while we're here, but the following line doesn't work...
		    // $csv_rows .= str_replace(["\t","\r"],[',',''],$row);

			// Instead, from http://stackoverflow.com/questions/7836632/how-to-replace-different-newline-styles-in-php-the-smartest-way
			// Nope, this doesn't work either. Fuck it, we can live with CRLF for now.
			// $row = preg_replace('~(*BSR_ANYCRLF)\R~', "\n", $row);

			$csv_row = str_replace("\t",',',$row);
		    $csv_rows .= $csv_row;
		}

		$csv_rows = mb_convert_encoding($csv_rows, 'UTF-8',$current_encoding);
		file_put_contents(storage_path('app/public/'.$csv_file), $csv_rows);
		fclose($fh);

		return true;
	}

	// From http://php.net/manual/en/function.mb-detect-encoding.php
	protected function detect_utf_encoding($filename) {

		// Unicode BOM is U+FEFF, but after encoded, it will look like this.
		define ('UTF32_BIG_ENDIAN_BOM'   , chr(0x00) . chr(0x00) . chr(0xFE) . chr(0xFF));
		define ('UTF32_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE) . chr(0x00) . chr(0x00));
		define ('UTF16_BIG_ENDIAN_BOM'   , chr(0xFE) . chr(0xFF));
		define ('UTF16_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE));
		define ('UTF8_BOM'               , chr(0xEF) . chr(0xBB) . chr(0xBF));

		/**
		 * $filename at this point is relative, so point file_get_contents to the storage folder
		 */
		// $text = file_get_contents($filename);
		$text = Storage::disk('public')->get($filename);
	    $first2 = substr($text, 0, 2);
	    $first3 = substr($text, 0, 3);
	    $first4 = substr($text, 0, 3);

	    if ($first3 == UTF8_BOM) return 'UTF-8';
	    elseif ($first4 == UTF32_BIG_ENDIAN_BOM) return 'UTF-32BE';
	    elseif ($first4 == UTF32_LITTLE_ENDIAN_BOM) return 'UTF-32LE';
	    elseif ($first2 == UTF16_BIG_ENDIAN_BOM) return 'UTF-16BE';
	    elseif ($first2 == UTF16_LITTLE_ENDIAN_BOM) return 'UTF-16LE';
	}

	/**
	 * Return the ctrl_class object defined by the object $object_id
	 * @param  integer $object_id  The ID of the object
	 * @return object The resulting object
	 */
	protected function get_ctrl_class_from_object($object) {

		$ctrl_class_name = str_replace('App\Ctrl\Models\\','',get_class($object));

		try {
			$ctrl_class = CtrlClass::where('name',$ctrl_class_name)->firstOrFail();
		}
		catch (\Exception $e) {
			trigger_error("Cannot load class by name $ctrl_class_name; {$e->getMessage()}");
		}
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

			// So, if $title_property here is a relationship, like a "link type"
			// load the related object, and then call get_object_title...?
			if ($title_property->relationship_type == 'belongsTo') {
				$related_object = $object->{$title_property->name};
				if (!is_null($related_object)) { // We won't always have a related object, a link may not have a type...
					$title_strings[] = '('.$this->get_object_title($related_object).')'; // Not sure that brackets will always be appropriate
				}
			}
			else {
				$property = $title_property->name;
				$title_strings[] = $object->$property;
			}
		}

		return implode(' ', $title_strings);

	}

	/**
	 * Display an object for viewing, not editing
	 * This mirrors @edit_object, but puts all fields in read_only mode; is this the best approach?
	 */
	public function view_object($ctrl_class_id, $object_id = NULL, $filter_string = NULL)
	{
		$this->isViewingObject = true;
		return $this->edit_object($ctrl_class_id, $object_id, $filter_string);
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

		$ctrl_class         = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();
		$ctrl_properties    = $ctrl_class->ctrl_properties()->where('fieldset','!=','')->get();

		$object             = $this->get_object_from_ctrl_class_id($ctrl_class_id,$object_id);
		$mode 			    = $this->isViewingObject ? 'view' : (($object_id) ? 'edit' : 'add');

		/**
		 * Check permissions; this is too clunky, can we set a "default" in a better way?
		 * @var boolean
		 */
		$can_edit = true;
		$can_add  = true;

        if ($this->module->enabled('permissions')) {
			$custom_add_permission = $this->module->run('permissions',[
				$ctrl_class->id,
				'add',
				// $filter_string
			]);
			if (!is_null($custom_add_permission)) {
				$can_add = $custom_add_permission;
			}
			$custom_edit_permission = $this->module->run('permissions',[
				$ctrl_class->id,
				'edit',
				// $filter_string
			]);
			if (!is_null($custom_edit_permission)) {
				$can_edit = $custom_edit_permission;
			}
		}

		if ($mode == 'edit' && !$can_edit) abort(403);
		if ($mode == 'add' && !$can_add) abort(403);

		$tabbed_form_fields = [];
		$hidden_form_fields = [];

		foreach ($ctrl_properties as $ctrl_property) {

 			// Reset $value, $values
			unset($value);
			unset($related_ctrl_class);
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
			elseif (in_array($ctrl_property->field_type,['date','datetime','time'])) {
				$ctrl_property->template = 'date';
			}
			elseif (empty($ctrl_property->field_type)) {
				trigger_error("No field_type set for property {$ctrl_property->name}");
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
				if (!is_null($related_objects)) { // 20161111: Why would this be null? Does this imply a data error?
					foreach ($related_objects as $related_object) {
						$value[$related_object->id] = $this->get_object_title($related_object);
					}
				}
			}

			// Do we have a default value set in the querystring?
			if ($default_values && $mode == 'add') { // We're adding a new object
				foreach ($default_values as $default_value) {
					if ($ctrl_property->id == $default_value['ctrl_property_id']) {
						$value = $default_value['value'];
					}
				}
			}
			if (!isset($value)) { // No default value, so pull it from the existing object
				$value      = $object->$field_name;
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
					// dump("SHOW COLUMNS FROM {$ctrl_property->ctrl_class->table_name} WHERE Field = '{$ctrl_property->name}'");
					trigger_error("Cannot locate column {$ctrl_property->name} in table {$ctrl_property->ctrl_class->table_name}");
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
						$enum_value = str_replace("''","'",$enum);
						// $values[$loop++] = $value;
						// Hmmm. Since we switched to Ajax select2 lists, I think we've broken ENUM selects...
						$values[$enum_value] = $enum_value;
					}
				}

			}

			// Build the form_field and it to the tabs

			$tab_name = $ctrl_property->fieldset;
			if ($this->module->enabled('hide_fieldset')) {
				if ($this->module->run('hide_fieldset',[
					$tab_name
				])) {
					continue;
				}
			}


			$tab_icon = 'fa fa-list';
			if ($this->module->enabled('custom_fieldset_icon')) {
				$tab_icon = $this->module->run('custom_fieldset_icon',[
					$tab_name,
					$tab_icon // Acts as the default
				]);
			}

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
				'type'                    => $ctrl_property->field_type, // This is used to modify some templates; date.blade.php can handle date, datetime or time types for example
				'template'                => $ctrl_property->template,
				'label'                   => $ctrl_property->label,
				'tip'                     => $ctrl_property->tip,
				'ctrl_class_name'		  => $ctrl_class->name,
				'related_ctrl_class_name' => (!empty($related_ctrl_class) ? $related_ctrl_class->name : false),
				'readOnly'				  => $mode == 'view' || $ctrl_property->flagged('read_only')
			];
			/*
				Note: we pass in the related_ctrl_class so that we can use Ajax to generate the list of select2 options.
				Otherwise, if we're working with (eg) Sogra Products, we have a select box with thousands of options, which breaks.
			*/

		}
		// dd($tabbed_form_fields);

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

		if ($mode == 'edit' || $mode == 'view') {

			// TODO: we should technically say, "Edit THE {singular}" if this is a single item
			// We can probably assess this by looking at menu_items, although we might need
			// to add some more array keys in order to identify (eg) the "homepage" item in the menu.

			$page_title       = ($mode == 'view' ? 'View' : 'Edit') .' this '.$ctrl_class->get_singular();
			// $page_description = '&ldquo;'.$object->title.'&rdquo;';
			$page_description = $this->get_object_title($object) ? '&ldquo;'.$this->get_object_title($object).'&rdquo;' : '';
			$delete_link      = $ctrl_class->can('delete') ? route('ctrl::delete_object',[$ctrl_class->id,$object->id]) : '';
		}
		else if ($mode == 'add') {
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
        	// Only if we can list the items... we almost always can
        	$back_link        = $ctrl_class->can('list') ? route('ctrl::list_objects',[$ctrl_class->id,$filter_string]) : url()->previous();
        }

		// Similarly... once we've saved a filtered object, we want to bounce back to a filtered list. This enables it:
		$save_link        = route('ctrl::save_object',[$ctrl_class->id,$object_id,$filter_string]);

		// NEW: can we repeat the row buttons on the edit page? This makes a lot of sense I think.
		/**
		 * We can, but only if we're *editing* an object, not adding one...
		 */

		$row_buttons = $this->get_row_buttons($ctrl_class->id, $object->id, $filter_string, $mode);

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
			'row_buttons'		 => $row_buttons,
			'mode'				 => $mode
		]);
	}

	/**
	 * Update an existing object of a given CtrlClass
	 * to set a property to a specfic value, set in $_POST
	 * Designed to be used with Ajax, I think?
	 * This is currently only used by the Toggle buttons --
	 * we could extend it to allow multiple values to be set,
	 * but realistically will this ever be necessary?
	 *
	 * @param  Request  $request
	 * @param  integer $ctrl_class_id The ID of the class we're editing
	 * @param  integer $object_id The ID of the object we're editing
	 *
	 * @return Response
	 */
	public function update_object(Request $request, $ctrl_class_id, $object_id)
	{

		try {
			$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();
		}
		catch (\Exception $e) {
			trigger_error($e->getMessage());
		}

		$object = $this->get_object_from_ctrl_class_id($ctrl_class->id,$object_id);

		$update = $request->input('update');
		list($ctrl_property_id, $value) = explode('~', $update);

		$ctrl_property = CtrlProperty::where('id',$ctrl_property_id)->first();
		if (is_null($ctrl_property)) trigger_error("Cannot load related_ctrl_property");

		$column = $ctrl_property->name;

		$object->$column = $value;

		$object->save();

        if ($request->ajax()) {
            return json_encode([
                'success'=>1
            ]);
        }
        else {
            return back();
        }
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

		// We need to disable Debugbar when returning Froala AJAX, if used
		$this->disableDebugBar();

		/* For Chrome's benefit, otherwise dd() isn't rendered in the console
		http_response_code(500);
		dd($_POST);
		*/
		try {
			$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();
		}
		catch (\Exception $e) {
			trigger_error($e->getMessage());
		}
		$ctrl_properties = $ctrl_class->ctrl_properties()->where('fieldset','!=','')->get();

		// Validate the post:
		$validation = [];
		$messages = []; // Polish the validation messages a bit
		foreach ($ctrl_properties as $ctrl_property) {

			$field_name = $ctrl_property->get_field_name();

			$flags = explode(',', $ctrl_property->flags);
			if (in_array('required', $flags)) {
				$validation[$field_name][] = 'required';
				$messages["$field_name.required"] = "The &ldquo;{$ctrl_property->label}&rdquo; field is required";
			}
			// Note: could also do this in query builder:
			/*
				$required_properties = $ctrl_class->ctrl_properties()
					->whereRaw("FIND_IN_SET('required',flags) > 0")
					->get();
			*/
			if ($ctrl_property->field_type == 'email') {
				$validation[$field_name][] = 'email';
				$messages["$field_name.email"] = "The &ldquo;{$ctrl_property->label}&rdquo; field must be a valid email address";
			}
			if (in_array($ctrl_property->field_type,['date','datetime'])) {
				if (in_array('required', $flags)) {
					$validation[$field_name][] = 'date';
				}
				else {
					// Allow empty dates
					$validation[$field_name][] = 'nullable|date';
				}
				$messages["$field_name.date"] = "The &ldquo;{$ctrl_property->label}&rdquo; field must be a valid date";
			}
			else if (in_array($ctrl_property->field_type,['time'])) {
				if (in_array('required', $flags)) {
					$validation[$field_name][] = 'date_format:g:i\ A';
				}
				else {
					// Allow empty times
					$validation[$field_name][] = 'nullable|date_format:g:i\ A';
				}
				$messages["$field_name.date_format"] = "The &ldquo;{$ctrl_property->label}&rdquo; field must be a valid time";
			}

			if (!empty($validation[$field_name])) {
				$validation[$field_name] = implode('|', $validation[$field_name]);
			}
		}

		if ($this->module->enabled('custom_validation')) {
        	// We may eventually need to patch this into the validation...? Or would that imply the need for a validation (or pre_save) module?
			list ($validation,$messages) = $this->module->run('custom_validation',[
				$ctrl_class,
				$validation,
				$messages
			]);
		}

		if ($validation) {
			$this->validate($request, $validation, $messages);
	    }

	    // $class 		= $ctrl_class->get_class();
		// $object  	= ($object_id) ? $class::where('id',$object_id)->firstOrFail() : new $class;
		$object = $this->get_object_from_ctrl_class_id($ctrl_class->id,$object_id);

		// Convert dates back into MySQL format; this feels quite messy but I can't see where else to do it:
		foreach ($ctrl_properties as $ctrl_property) {
			if (in_array($ctrl_property->field_type,['date','datetime','time']) && !empty($_POST[$ctrl_property->name])) {
				switch ($ctrl_property->field_type) {
					case 'date':
						$date_format = 'Y-m-d';
						break;
					case 'datetime':
						$date_format = 'Y-m-d H:i:s';
						break;
					case 'time':
						$date_format = 'H:i:s';
						break;
				}
				$_POST[$ctrl_property->name] = date($date_format,strtotime($_POST[$ctrl_property->name]));
			}
		}

		//dd($_POST);

        $object->fill($_POST);

        // OK. I don't want nullable fields (typically integers or floats) to be set to zero, if the posted value is an empty string
        // What's the best way to do this?
        $check_nullable_properties = $ctrl_class->ctrl_properties()
                                              ->where('fieldset','!=','')
                                              ->where('relationship_type','=',NULL) // This makes sense, I think
                                              ->get();

		foreach ($check_nullable_properties as $check_nullable_property) {
			$column = $check_nullable_property->name;
	        $nullable = DB::table('INFORMATION_SCHEMA.COLUMNS')
	        			// ->select("IS_NULLABLE")
	        			->where('table_name',$ctrl_class->table_name)
	        			->where('COLUMN_NAME',$column)
	        			->value('IS_NULLABLE');
	        if (
					!is_null($nullable)
					&& $nullable == 'YES'
					&& isset($_POST[$column])
					&& $_POST[$column] === ''
				) {
	        	$object->$column = null;
	        }
	        // Also, if the column ISN'T nullable, and we haven't passed in a value, set the value to '';
	        // this gets around an issue in Laravel >= 5.3, which uses "strict" MySQL mode and needs columns to have default values
	        else if (
					!is_null($nullable)
					&& $nullable == 'NO'
					&& !isset($_POST[$column])
					&& !$object->$column
				) {
	        	$object->$column = '';
			}
		}

		$check_boolean_properties = $ctrl_class->ctrl_properties()
                                              ->where('fieldset','!=','')
                                              ->where('field_type','=','checkbox')
											  ->get();
		foreach ($check_boolean_properties as $check_boolean_property) {
			$column = $check_boolean_property->name;
			if (empty($_POST[$column])
			) {
				$object->$column = 0;
			}
		}

        // Set the URL automatically as well:
        if (Schema::hasColumn($ctrl_class->table_name, 'url') && !$object->url) {
        	$title = $this->get_object_title($object);
        	$slug  = Str::slug($title);
        	$append = 1;
        	while (!is_null(DB::table($ctrl_class->table_name)->where('url', $slug)->first())) {
        		$slug = Str::slug($title .' '.$append++);
        		if ($append >= 100) {
        			trigger_error("Infinite loop");
        		}
        	}
        	$object->url = $slug;
        }

        if ($this->module->enabled('pre_save')) {
			$this->module->run('pre_save',[
				$request,
				$object,
				$filter_string
			]);
		}


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
						if (!is_array($related_objects)) {
							// If we're saving a single hasMany value from a hidden field...?
							// This happened for LE when saving smart matches for individuals
							// (where the individual is displayed as a readonly value)
							$related_objects = [$related_objects];
						}
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
			// Note: if we're posting a multiple select with nothing selected, the $_POST value doesn't exist; meaning that we can't remove the relationship
			else if ($related_ctrl_property->relationship_type == 'belongsToMany') {
				// Try this:
				$object->$related_field_name()->detach();
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

		$message  = ucwords($ctrl_class->get_singular()) . ' saved';
   		$messages = [$message];
   		$request->session()->flash('messages', $messages);

		if ($ctrl_class->can('list')) {
        	$redirect = route('ctrl::list_objects',[$ctrl_class->id,$filter_string]);
        }
        else {
  			$redirect = url()->previous(); // route('ctrl::dashboard');
        }

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

	    $this->disableDebugBar();

	    $response = new \StdClass;

		if ($request->file('file')->isValid()) {

			$path           = $this->upload($request, 'file');
			if (\App::VERSION() >= 5.4) {
				$response->link = Storage::url($path);
			}
			else {
				$response->link = $path;
			}

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
		$this->disableDebugBar();

		$response = new \StdClass;

		$field_name = $request->field_name;
		// We pass in field_name as a hidden parameter

		if ($request->hasFile($field_name)) {

			if ($request->file($field_name)->isValid()) {

				$path           = $this->upload($request,$field_name);
				$response->link = $path;

			}
			else {
				$response->error = 'An error has occurred';

			}
		}
		else {
			$response->error = 'Uploaded file cannot be found; please contact support';
		}

		return stripslashes(json_encode($response));
	}

	/**
	 * A generic "upload" function used by both Krajee and Froala
	 * @param  Request $request
	 * @param  string $fieldName    The name of the "file" field
	 * @return string The path of the uploaded file
	 */
	protected function upload($request, $fieldName) {
		/**
		 * New approach using Storage
		 */
		if (\App::VERSION() >= 5.4) {
			/**
			 * Storage images with a hash, preserve original filename for files
			 * We'll store both in the public folder though, as we're likely to need direct
			 * access at some point -- especially files, but images as well, as we
			 * might not always run them through Intervention or whatever.
			 */
			if ($request->type == 'image') {
				 $path = $request->file($fieldName)->store('images','public');
			}
			else if ($request->type == 'file') {
				$fileName = $request->file($fieldName)->getClientOriginalName();
				/**
				 * TODO: Untested! Duplicate name code should also go here.
				 *  Something like this, from http://stackoverflow.com/a/28710192:
				 *
				if (Storage::exists($fileName)) {
				    // Split filename into parts
				    $pathInfo = pathinfo($fileName);
				    $extension = isset($pathInfo['extension']) ? ('.' . $pathInfo['extension']) : '';

				    // Look for a number before the extension; add one if there isn't already
				    if (preg_match('/(.*?)(\d+)$/', $pathInfo['filename'], $match)) {
				        // Have a number; increment it
				        $base = $match[1];
				        $number = intVal($match[2]);
				    } else {
				        // No number; add one
				        $base = $pathInfo['filename'];
				        $number = 0;
				    }

				    // Choose a name with an incremented number until a file with that name
				    // doesn't exist
				    do {
				        $fileName = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $base . ++$number . $extension;
				    } while (Storage::exists($fileName));
				}
				 */
				$path = $request->file($fieldName)->storeAs(
				    'files', $fileName, 'public'
				);
			}

			/**
			 * OK, so. Using Storage as above will stick everything in (eg) /storage/app/public/images
			 * We then use the Laravel symlink to server these files from (eg) /storage/images
			 * However, the store() and storeAs() methods return the 'public' path; eg, /public/images
			 * whereas really, I think it makes more sense to store the actual path we'd use; eg, /storage/images
			 * So, swap out the /public for /storage. This is all a bit flaky and may well need revisiting.
			 */
			// $path = preg_replace('/^public\//', 'storage/', $path);
		}
		else {
			/**
			 * Old approach
			 */
			$extension = $request->file($fieldName)->getClientOriginalExtension();

			if ($request->type == 'image') {
				$name      = uniqid('image_');
			}
			else if ($request->type == 'file') {
				// We could add something a little more intelligent here
				$name = basename($request->file($fieldName)->getClientOriginalName(),".$extension").'-'.rand(11111,99999);
			}

			$target_folder = $this->uploads_folder;
			$target_file   = $name.'.'.$extension;

			$moved_file = $request->file($fieldName)->move($target_folder, $target_file);

			$path = '/'.$moved_file->getPathname();
		}
		return $path;

	}

	/**
	 * Disable the DebugBar, for instances where it clashes with Ajax responses
	 */
	protected function disableDebugBar() {
		// We need to disable Debugbar when returning Froala AJAX, if used
	    if (\class_exists('Debugbar')) {
			// Debugbar enabled, but in order to disable it, we also need to have enabled the Facade...
			// This isn't necessary if we're using 5.5+
			if (\App::VERSION() >= 5.5) {
				\Debugbar::disable();
			}
			else {
				if (!array_key_exists('Debugbar', config('app.aliases'))) {
					trigger_error("If using Debugbar, the alias must be enabled so that we can in turn disable Debugbar...");
				}
				else {
					\Debugbar::disable();
				}
			}
		}
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
	        	$messages = [$message => 'success'];
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

			// Loop through all classes that we can edit or view

			$ctrl_classes = CtrlClass::whereRaw(
				'(find_in_set(?, permissions) or find_in_set(?, permissions))',
				['edit','view']
			 )->get();

			foreach ($ctrl_classes as $ctrl_class) {
				if ($this->module->enabled('permissions')) {
					$can_edit = $this->module->run('permissions',[
						$ctrl_class->id,
						'edit',
					]);
					$can_view = $this->module->run('permissions',[
						$ctrl_class->id,
						'view',
					]);
				}
				else {
					$can_edit = $ctrl_class->can('edit');
					$can_view = $ctrl_class->can('view');
				}

				if (!$can_edit && !$can_view) {
					continue;
				}

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

					/**
					 * Dump SQL if necessary
					 */
					if (false) {
						$sql      = str_replace(array('%', '?'), array('%%', '%s'), $query->toSql());
						$bindings = $query->getBindings();
						array_walk($bindings, function(&$value, $key) {
							if (!is_numeric($value)) $value = "'$value'";
						});
						dd(vsprintf($sql, $bindings));
					}

					$objects = $query->get();
					if (!$objects->isEmpty()) {
					    foreach ($objects as $object) {

							if ($can_edit) {
								$link = route('ctrl::edit_object',[$ctrl_class->id,$object->id]);
							}
							else if ($can_view) {
								$link = route('ctrl::view_object',[$ctrl_class->id,$object->id]);
							}

					    	$result             = new \StdClass;
					    	$result->class_name = $ctrl_class->get_singular();
					    	$result->title      = $this->get_object_title($object);
					    	$result->link  		= $link;
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

	public function convert_filter_string_to_array($filter_string) {
		$filter_array = [];
		if (!empty($filter_string) && (
				strpos($filter_string,'~') !== false
				||
				strpos($filter_string,',') !== false
			)
		) { /* There's an issue here, where the image upload (within Redactor I think) believes it has a filter string but doesn't... this needs to be checked properly. */
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
		else if (in_array($string[0],array('a','e','i','o','u'))) {
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
