<?php

namespace Sevenpointsix\Ctrl\Commands;

use Illuminate\Console\Command;

use DB;
use Config;
use View;
use File;

class CtrlInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ctrl:init {action?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command initialises the CMS';

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
        // This is all very much WIP. Note that we'll need to use a difference path if this is running as a Composer module.
        // I've not even tested this yet so it might completely bail. Just wanted to get the bare bones down before I forgot.

        // Check for (and create if necessary) the writeable app/Ctrl folder
        /* No, do this on boot?
        $ctrl_folder = 'Ctrl/';
        if(!File::exists(app_path($ctrl_folder))) {
            File::makeDirectory(app_path($ctrl_folder),0777,true); // See http://laravel-recipes.com/recipes/147/creating-a-directory
        }
        */
        // Check that we have a Modules file
        /* No, do this on vendor:publish in boot as well?
        $ctrl_modules = 'Ctrl/CtrlModules.php';
        if(!File::exists(app_path($ctrl_modules))) {
            $src_file = ''
            File::copy($file, app_path($ctrl_modules);
        }
        */

        /* WIP: will this work?
        $this->call('migrate', [
            '--path' => 'packages/sevenpointsix/Ctrl/database'
        ]);
        */
       
       /* Update: no! We should copy database migrations when we publish assets:
       // Something like...
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('/migrations')
        ], 'migrations');
        // See https://laravel.com/docs/5.0/packages#publishing-file-groups
        */
         

    }

}
