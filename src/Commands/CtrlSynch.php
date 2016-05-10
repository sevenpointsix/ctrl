<?php

namespace Sevenpointsix\Ctrl\Commands;

use Illuminate\Console\Command;

use DB;
use Config;
use View;
use File;

class CtrlSynch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ctrl:synch {action?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command updates the ctrl_ table to reflect the current database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $action = $this->argument('action');
        
        if ($action == 'files') {            
            $this->generate_model_files();
        }
        else if ($action == 'data') {
            $this->populate_ctrl_tables();            
        }
        else if ($action == 'all') {
            $this->populate_ctrl_tables();
            $this->generate_model_files();
        }
        else {
            $this->line('Usage: php artisan ctrl:synch files|data|all');
        }      
        
    }

    /**
     * Loop through all database tables, and create the necessary records in ctrl_classes and ctrl_properties
     * @return Response
     */
    protected function populate_ctrl_tables() {

        $wipe_all_existing_tables = true; // While testing, it's easier to start from scratch each time

        if ($wipe_all_existing_tables) {
            DB::table('ctrl_classes')->truncate();
            DB::table('ctrl_properties')->truncate();
        }

        // Loop through all tables in the database

        // We'll store the tables in two arrays; $standard_tables and $pivot_tables
        $standard_tables = [];
        $pivot_tables    = [];

        // Get the current database name (from https://octobercms.com/forum/post/howto-get-the-default-database-name-in-eloquentlaravel-config)
        $database_name = Config::get('database.connections.'.Config::get('database.default').'.database');
        $tables = DB::select('SHOW TABLES');
        $ignore_tables = ['ctrl_classes','ctrl_properties','migrations','password_resets','revisions'];         
        foreach ($tables as $table) {           
            $table_name = $table->{'Tables_in_'.$database_name};

            /*          
                Ignore the following tables:
                - The ctrl_ tables
                - The migrations table
                - The password_resets table
                - The revisions table (assuming we're using this)
                - Any tables prefixed with '_'
            */
            
            if (in_array($table_name, $ignore_tables) || starts_with($table_name,'_')) continue;

            // We now need to identify whether the table we're looking at is a pivot table or not
            /*
                The assumption at this stage will be that if the table consists purely of *_id columns, with an optional 'id' or 'order' column, it's a pivot table. Realistically, I rarely use extra attributes on a pivot table; 'order' would be the only one, and even that is rarely used.
             */
            $table_columns = DB::select("SHOW COLUMNS FROM {$table_name}"); // Bindings fail here for some reason
            $pivot_table   = true;
            $pivot_columns = ['id','order'];
            foreach ($table_columns as $table_column) {
                $column_name = $table_column->Field;
                if (!in_array($column_name,$pivot_columns) && !ends_with($column_name,'_id')) {
                    $pivot_table = false;
                    continue;
                }
            }

            if ($pivot_table) {
                // $table_name is a pivot table
                $pivot_tables[] = $table_name;
            }
            else {              
                // $table_name is a standard table
                $standard_tables[] = $table_name;
            }
        }

        // We now have an array of standard tables, and an array of pivot tables
        // We can loop through these in order and generate all the classes and properties we'll need

        $tables_processed  = 0;
        $columns_processed = 0; // Could track added/updated counts here, and possibly even 'deleted'
        $ignore_columns = ['id','remember_token']; // Do we ever want to see these fields?

        for ($pass = 1; $pass <= 2; $pass++) { // Properties on pass 1, relationships on pass 2
            foreach ($standard_tables as $standard_table) {

                $model_name = studly_case(str_singular($standard_table));           
                $ctrl_class = \Sevenpointsix\Ctrl\Models\CtrlClass::firstOrNew(['name' => $model_name]);
                $ctrl_class->table_name = $standard_table; 

                // Set some default permissions, icons and menu items (?) here
                $ctrl_class->permissions = implode(',',array('list','add','edit','delete'));
                $ctrl_class->icon        = 'fa-toggle-right';
                // Let's leave menu_title for now

                $ctrl_class->save();
                    

                $columns = DB::select("SHOW COLUMNS FROM {$standard_table}");
                $column_order = 1;
                foreach ($columns as $column) {

                    $column_name = $column->Field;

                    if (in_array($column_name, $ignore_columns) || starts_with($column_name,'_')) continue;
                        // Not sure we ever prefix columns with _, but I suppose it's possible
                        
                    /*
                        Is this column a straight property, or a relationship?
                        We'll handle relationships on the second pass, so that it's easy enough
                        to identify the corresponding table we're linking to. 
                        For example, if we have tables A and B, with A.B_id, we need to know
                        that table B exists before we can create *both* relationships.
                     */
                    
                    if (!ends_with($column_name,'_id') && $pass == 1) { // A straight property
                        $ctrl_property = \Sevenpointsix\Ctrl\Models\CtrlProperty::firstOrNew([
                            'ctrl_class_id' => $ctrl_class->id,
                            'name'          => $column_name
                        ]);     

                        // $ctrl_property->ctrl_class()->save($ctrl_class);
                        // I think we can omit this, as we've already set ctrl_class_id when calling firstOrNew():

                        if ($ctrl_property->name == 'order') {
                            // Set this as a header, so that we can reorder the table, then skip the rest
                            $ctrl_property->add_to_set('flags','header');
                            $ctrl_property->order = -1; // To force this to be the first column on the left of the table
                            $ctrl_property->save(); 
                            continue; // Is this correct? Or break?
                        }

                         // Set some default flags, labels, field_types and so on:
                        switch ($ctrl_property->name) {
                            case 'title':
                            case 'name':
                                $ctrl_property->add_to_set('flags','header');
                                $ctrl_property->add_to_set('flags','string');
                                $ctrl_property->add_to_set('flags','required');
                                $ctrl_property->add_to_set('flags','search');
                                break;
                            case 'image':
                            case 'photo':
                                $ctrl_property->field_type = 'image';
                                break;
                            case 'file':                            
                                $ctrl_property->field_type = 'file';
                                break;
                            case 'email':
                            case 'email_address':
                                $ctrl_property->field_type = 'email';
                                break;
                            case 'content':                            
                                $ctrl_property->field_type = 'froala';
                                break;
                        }

                        if (!$ctrl_property->field_type) {
                            $ctrl_property->field_type    = $ctrl_property->get_field_type_from_column($column->Type);
                        }

                        $ctrl_property->order = $column_order++;

                        $ctrl_property->label    = ucfirst(str_replace('_',' ',$ctrl_property->name));

                        // There are some columns we rarely want to display as editable fields
                        $exclude_fields_from_form = ['created_at','updated_at','deleted_at','url','uri'];
                        if (!in_array($ctrl_property->name, $exclude_fields_from_form)) {
                            $ctrl_property->fieldset = 'Content';
                        }

                        $ctrl_property->save();             
                    }
                    else if (ends_with($column_name,'_id') && $pass == 2) { // A relationship

                        // Identify the table (and hence ctrl class) that this is a relationship to
                        $inverse_table_name = str_plural(str_replace('_id', '', $column_name));
                        $inverse_ctrl_class = \Sevenpointsix\Ctrl\Models\CtrlClass::where([
                            ['table_name',$inverse_table_name]
                        ])->first();

                        if (is_null($inverse_ctrl_class)) {
                            trigger_error("Cannot load ctrl_class for table $inverse_table_name");
                            continue;
                        }

                        $ctrl_property = \Sevenpointsix\Ctrl\Models\CtrlProperty::firstOrNew([                      
                            'ctrl_class_id'     => $ctrl_class->id,
                            'related_to_id'     => $inverse_ctrl_class->id,
                            'name'              => str_replace('_id', '', $column_name),
                            'relationship_type' => 'belongsTo',
                            'foreign_key'       => $column_name,
                            'local_key'         => 'id',
                            // Assume we always want to include simple "belongsTo" relationships on the form
                            'field_type' => 'dropdown',
                            'label'      => ucfirst($column_name),
                            'fieldset'   => 'Content'
                        ]); 

                        $ctrl_property->order = $column_order++; // Do we leave this out of the firstOrNew, as order is something we'd tweak manually?                

                        $ctrl_property->save(); // As above, no need to explicitly save relationship                            
                            
                        // We do need to create the inverse property though:
                    
                        $inverse_ctrl_property = \Sevenpointsix\Ctrl\Models\CtrlProperty::firstOrNew([
                            'name'              => strtolower($ctrl_class->name),
                                // 'name' here could possibly be ascertained in other ways; TBC
                            'ctrl_class_id'     => $inverse_ctrl_class->id,
                            'related_to_id'     => $ctrl_class->id,
                            'relationship_type' => 'hasMany',
                                // This could in theory be haveOne, but in practice hasOne is very rarely used;
                                // any hasOne relationship could actually be a hasMany in most cases.
                            'foreign_key'       => $column_name,
                            'local_key'         => 'id'
                        ]);
                        $inverse_ctrl_property->save();  // As above, no need to explicitly save relationship
                    }
                    if ($pass == 1) $columns_processed++;   
                }               

                if ($pass == 1) $tables_processed++;
            }
        }

        // Now loop through the pivot tables and create the hasMany relationships
        foreach ($pivot_tables as $pivot_table) {
            $columns = DB::select("SHOW COLUMNS FROM {$pivot_table}");

            // Filter out anything that isn't an _id
            $columns = array_where($columns, function ($key, $value) {
                return ends_with($value->Field,'_id');
            });
            // Make sure we have the columns in alphabetical order; is this necessary?
            // I think it's just the NAME of the pivot table that matters, and that's beyond our control
            $columns = array_sort($columns, function ($value) {
                return $value->Field;
            });
            
            for ($pass = 1; $pass <= 2; $pass++) { // Allows us to create (invert) both relationships without duplicating code

                if ($pass == 1) {
                    $pivot_one = head($columns)->Field;
                    $pivot_two = last($columns)->Field;
                }
                else if ($pass == 2) {
                    $pivot_one = last($columns)->Field;
                    $pivot_two = head($columns)->Field;
                }
            
                // Identify the tables (and hence ctrl classes) that we're relating
                $related_table_one = str_plural(str_replace('_id', '', $pivot_one));
                $related_ctrl_class_one = \Sevenpointsix\Ctrl\Models\CtrlClass::where([
                    ['table_name',$related_table_one]
                ])->first();

                $related_table_two = str_plural(str_replace('_id', '', $pivot_two));
                $related_ctrl_class_two = \Sevenpointsix\Ctrl\Models\CtrlClass::where([
                    ['table_name',$related_table_two]
                ])->first();

                if (is_null($related_ctrl_class_one) || is_null($related_ctrl_class_two)) {
                    trigger_error("Cannot load related ctrl_classes for pivot table $related_table_name");
                    continue;
                }

                $ctrl_property = \Sevenpointsix\Ctrl\Models\CtrlProperty::firstOrCreate([                       
                    'ctrl_class_id'     => $related_ctrl_class_one->id,
                    'related_to_id'     => $related_ctrl_class_two->id,
                    'name'              => str_replace('_id', '', $pivot_two),
                    'relationship_type' => 'belongsToMany',
                    'foreign_key'       => $pivot_two,
                    'local_key'         => $pivot_one,
                    'pivot_table'       => $pivot_table
                ]);

                $ctrl_property->save();
                
                if ($pass == 1) $columns_processed++;   
            }               

            if ($pass == 1) $tables_processed++;
        }
        
        $this->info("$tables_processed tables and $columns_processed columns processed");
    }

    /**
     * Generate model files based on the ctrl_tables
     *
     * @return Response
     */
    public function generate_model_files()
    {
        $model_folder = 'Ctrl/Models/';
        if(!File::exists(app_path($model_folder))) {
            File::makeDirectory(app_path($model_folder),0777,true); // See http://laravel-recipes.com/recipes/147/creating-a-directory
        }
        else {
            // Otherwise, empty the folder:
            File::cleanDirectory(app_path($model_folder));
        }

        $ctrl_classes = \Sevenpointsix\Ctrl\Models\CtrlClass::get();

        foreach ($ctrl_classes as $ctrl_class) {

            $view_data = [
                'model_name'    => $ctrl_class->name,
                'soft_deletes'  => false, // Let's leave soft deletes for now
                'table_name'    => $ctrl_class->table_name,
                'fillable'      => [],
                'belongsTo'     => [],
                'hasMany'       => [],
                'belongsToMany' => [],

            ];
            
            $fillable_properties = $ctrl_class->ctrl_properties()
                                              ->where('fieldset','!=','')
                                              ->where(function ($query) {
                                                    $query->whereNull('relationship_type')
                                                          ->orWhere('relationship_type','belongsTo');
                                                })
                                              ->get();      
                                              
                // We can only fill relationships if they're belongsTo (ie, have a specific local key, such as one_id)
            foreach ($fillable_properties as $fillable_property) {
                $view_data['fillable'][] = $fillable_property->get_field_name();
                    // Does Laravel/Eloquent give us a quick way of extracting all ->name properties into an array?
                    // I think it does.
            }          
            
            $relationship_properties = $ctrl_class->ctrl_properties()->whereNotNull('related_to_id')->get();
            foreach ($relationship_properties as $relationship_property) {
                $related_ctrl_class = \Sevenpointsix\Ctrl\Models\CtrlClass::find($relationship_property->related_to_id);
                $relationship_data = [
                    'name'        => $relationship_property->name,
                    'model'       => $related_ctrl_class->name,
                    'foreign_key' => $relationship_property->foreign_key,
                    'local_key'   => $relationship_property->local_key,
                ];
                if ($relationship_property->relationship_type == 'belongsToMany') {
                    $relationship_data['pivot_table'] = $relationship_property->pivot_table;
                }
                $view_data[$relationship_property->relationship_type][] = $relationship_data;
            }

            $model_code = View::make('ctrl::model_template',$view_data)->render();
            $model_path = app_path($model_folder.$ctrl_class->name.'.php');

            File::put($model_path, $model_code);
        
        }

        $this->info($ctrl_classes->count() . ' files generated');
        
    }
}
