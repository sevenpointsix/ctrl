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

use Datatables;

use \Sevenpointsix\Ctrl\Models\CtrlClass;
use \Sevenpointsix\Ctrl\Models\CtrlProperty;

class CtrlController extends Controller
{

	public function __construct() {
		// Note that we don't need to call the parent __construct() here

		$this->_check_login(); // Check that the user is logged in, if necessary
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
		
		$ctrl_classes = CtrlClass::where('menu_title','!=','')
								 	->orderBy('menu_title')
								 	->orderBy('order')
								 	->get();
			// The ordering here will need work, this is purely a quick solution while bootstrapping the site
		$menu_links        = [];
		foreach ($ctrl_classes as $ctrl_class) {

			if ($ctrl_class->plural) {
				$menu_title = $ctrl_class->plural;
			}
			else if ($ctrl_class->singular) {
				$menu_title = str_plural($ctrl_class->singular);
			}
			else {
				$menu_title = str_plural(strtolower($ctrl_class->name));
			}
			

			$menu_links[$ctrl_class->menu_title][] = [
				'id'    => $ctrl_class->id,
				'title' => ucwords($menu_title),
			];
		}

		return view('ctrl::dashboard',[
			'menu_links'=>$menu_links
		]);
	}



	/**
	 * List all objects of a given CtrlClass
	 *
	 * @return Response
	 */
	public function list_objects($ctrl_class_id)
	{		

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
        		$column->defaultContent = '';
        		$th_columns[] = '<th data-search-dropdown="true">'.$header->label.'</th>';
        	}
        	else {
        		$column->data = $header->name;
        		$column->name = $ctrl_class->table_name.'.'.$header->name;
        			// Again, see http://datatables.yajrabox.com/eloquent/relationships
        			// "Important! To avoid ambiguous column name error, it is advised to declare your column name as table.column just like on how you declare it when using a join statements."
        		$th_columns[] = '<th data-search-text="true">'.$header->label.'</th>';
        	}
        	$js_columns[] = $column;        	
        	
        }
        // dd($js_columns);
        // Add the "action" column
        $action_column       = new \StdClass;
        $action_column->data = 'action';
        $action_column->name = 'action';
        $js_columns[]        = $action_column;

		return view('ctrl::list_objects',[
			'ctrl_class' => $ctrl_class,
			'th_columns' => implode("\n",$th_columns),
			'js_columns' => json_encode($js_columns),
		]);
	}

	/**
	 * Get data for datatables
	 * @param  [type] $ctrl_class_id [description]
	 * @return [type]                [description]
	 */
	public function get_data($ctrl_class_id) {

		//$objects = \App\Ctrl\Models\Test::query();
		//$users = User::select(['id', 'name', 'email', 'password', 'created_at', 'updated_at']);

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
            ->addColumn('action', function ($object) use ($ctrl_class) {
            	$edit_link = route('ctrl::edit_object',[$ctrl_class->id,$object->id]);
            	$buttons = '
            	<!-- Split button -->
<div class="btn-group flex">
  <a class="btn btn-sm btn-info" href="'.$edit_link.'"><i class="fa fa-pencil"></i> Edit</a>
  <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    <i class="fa fa-caret-down"></i>
    <span class="sr-only">Toggle Dropdown</span>
  </button>
  <ul class="dropdown-menu dropdown-menu-right">
    <li><a href="#"><i class="fa fa-trash"></i> Delete</a></li>
    <!--
    <li><a href="#">Delete</a></li>
    <li><a href="#">Delete</a></li>
    <li role="separator" class="divider"></li>
    <li><a href="#">Separated link</a></li>
    -->
  </ul>
</div>';
				/*
            	$buttons = [
            		'<a href="'.$edit_link.'" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i> Edit</a>'
            	];
                return implode("\n", $buttons);
                */
               	return $buttons;
            })
            ->make(true);

		// return Datatables::of($objects)->make(true);
	}

	/**
	 * Edit an objects of a given CtrlClass, if an ID is given
	 * Or renders a blank form if not
	 * This essentially renders a form for the object
	 *
	 * @return Response
	 */
	public function edit_object($ctrl_class_id, $object_id = NULL)
	{		
		$ctrl_class = CtrlClass::where('id',$ctrl_class_id)->firstOrFail();				
		
		$class  = $ctrl_class->get_class();
		$object = ($object_id) ? $class::where('id',$object_id)->firstOrFail() : new $class;		

		$form_fields = [];
		$ctrl_properties = $ctrl_class->ctrl_properties()->where('fieldset','!=','')->get();	
		foreach ($ctrl_properties as $ctrl_property) {

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

			// Do we have a range of values for this field? For example, an ENUM or relationship field
			$values = [];
			if ($ctrl_property->related_to_id) {
				$related_ctrl_class = \Sevenpointsix\Ctrl\Models\CtrlClass::find($ctrl_property->related_to_id);
				$related_class 		= $related_ctrl_class->get_class();
				$related_objects  	= $related_class::all();
				foreach ($related_objects as $related_object) {
					$values[$related_object->id] = $related_object->title; // 'title' won't always be true
				}
			}
			else {
				$column = DB::select("SHOW COLUMNS FROM {$ctrl_property->ctrl_class->table_name} WHERE Field = '{$ctrl_property->name}'");
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


			// Ascertain the name current value of this field
			// This essentially converts 'one' to 'one_id' and so on
			$field_name = $ctrl_property->get_field_name();

			if ($ctrl_property->related_to_id && in_array($ctrl_property->relationship_type,['hasMany','belongsToMany'])) {
				$related_objects = $object->$field_name;
				$value = [];
				foreach ($related_objects as $related_object) {
					$value[$related_object->id] = $related_object->title; // 'title' won't always be true
				}
			}
			else {
				$value      = $object->$field_name;	
			}

			$form_fields[] = [
				'id'       => 'form_id_'.$ctrl_property->name,
				'name'     => $field_name,
				'values'   => $values,
				'value'    => $value, // Remember that $value can be an array, for relationships / multiple selects etc
				'type'     => $ctrl_property->field_type,
				'template' => $ctrl_property->template,
				'label'    => $ctrl_property->label,
				'tip'      => $ctrl_property->tip,
			];
		}		

		return view('ctrl::edit_object',[
			'ctrl_class'  => $ctrl_class,
			'object'      => $object,
			'form_fields' => $form_fields,
		]);
	}

	/**
	 * Update an object a given CtrlClass, if an ID is given
	 * Or create a new object if not
	 *
	 * @return Response
	 */
	public function save_object(Request $request, $ctrl_class_id, $object_id = NULL)
	{		

		// dd($_POST);

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
       
        // Now load any related fields (excluding belongsTo, as this indicates the presence of an _id field)
        $related_ctrl_properties = $ctrl_class->ctrl_properties()
                                              ->where('fieldset','!=','')
                                              ->where(function ($query) {
                                                    $query->where('relationship_type','hasMany');
                                                          // ->orWhere('relationship_type','belongsToMany');
                                                    // Not tested belongsToMany yet
                                                })
                                              ->get();  

		foreach ($related_ctrl_properties as $related_ctrl_property) {
			$related_field_name = $related_ctrl_property->get_field_name();

	        if ($request->input($related_field_name)) {

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
	            
	            if ($related_ctrl_property->relationship_type == 'hasMany') {

		            // A hasMany relationship needs saveMany
		            // belongsToMany might need attach, or synch -- TBC

		          	$existing_related_objects = $object->$related_field_name;
		          	$inverse_property = CtrlProperty::where('ctrl_class_id',$related_ctrl_class->id)
		            								  ->where('foreign_key',$related_ctrl_property->foreign_key)
		            								  ->first(); // Does this always hold true?
					$inverse_field_name = $inverse_property->name;

		          	foreach ($existing_related_objects as $existing_related_object) {		          		
		          		$existing_related_object->$inverse_field_name()->dissociate();
		          		$existing_related_object->save(); 
		          			// This seems unnecessarily complicated; review this.
		          			// Is there no equivalent of synch() for hasMany/belongsTo relationships?
		          			// Something like, $object->related_field_name()->sync($related_objects);
		          			// That doesn't work though...
		          	}

		            $object->$related_field_name()->saveMany($related_objects);
		            //$object->save();
		            // This is ALMOST working but glitches; we seem to save the relationship then overwrite it when we try to remove it, even though we try to remove it first. Do we need to lock the tables here?
		        }
			}
		}
		

        $object->save();
        
        $redirect = route('ctrl::list_objects',$ctrl_class->id);

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

		return stripslashes(json_encode($response));
	}
	/**
	 * Present the login screen
	 *
	 * @return Response
	 */
	public function login()
	{
		if (Auth::check()) {
			return redirect(route('ctrl::dashboard'));
		}
		return view('ctrl::login');
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

        if (Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
		    // User logged in, but check that they can actually access the CMS:
		    if (!empty(Auth::user()->ctrl_group)) {
		    	$redirect = URL::previous();
			    $message  = 'Logged in';
	        	$messages = collect([$message]);
	        	$request->session()->flash('messages', $messages);	
		    }
		    else {
		    	Auth::logout();		    	
		    }		    
		}
		
		if (!Auth::check()) {
			// Can't log in, try again			
        	$redirect = route('ctrl::login');
        	// Set a flash error message
        	$message  = 'Incorrect login';
        	$messages = collect([$message]);
        	$request->session()->flash('errors', $messages);
        }	    

        if ($request->ajax()) {        	
            return json_encode([
                'redirect' => $redirect
            ]);
        }
        else {            
            return redirect($redirect);
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