<?php

namespace Sevenpointsix\Ctrl\Commands;

use Illuminate\Console\Command;

use DB;
//use Config;
//use View;
use File;

class CtrlTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ctrl:tables
                        {action? : Whether to import or export data}
                        ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command imports, or exports, the ctrl_ tables from the codebase.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $folder = app_path('Ctrl/database/');
        $file   = 'ctrl_tables.sql';

        if(!File::exists($folder)) {
            File::makeDirectory($folder,0777,true); // See http://laravel-recipes.com/recipes/147/creating-a-directory
        }

        $this->sql_file = $folder.$file;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $action = $this->argument('action');

        if ($action == 'import') {
            $this->import();
        }
        else if ($action == 'export') {
            $this->export();
        }        
        else {
            $this->line('Usage: php artisan ctrl:tables import|export');
        }      
        
    }

    /**
     * Export the two ctrl_ tables to a dump file in app/Ctrl/data
     * @return none
     */
    public function export() {

        if (app()->environment() != 'local') {
            $this->error(sprintf("Please note that it makes little sense to export these tables from the %s environment",app()->environment()));
        }

        // From https://gist.github.com/kkiernan/bdd0954d0149b89c372a

        $database = env('DB_DATABASE');
        $user     = env('DB_USERNAME');
        $password = env('DB_PASSWORD');

        if ($password) {
            // Compile the full mysql password prompt here; otherwise, we end up passing -p'' to mysqldump, which fails
            $password = sprintf('-p\'%s\'',$password);
        }

        $command = sprintf('mysqldump %s -u %s %s ctrl_classes ctrl_properties > %s', $database, $user, $password, $this->sql_file);

        exec($command);

        $this->info("Data file exported");

    }

     /**
     * Import the two ctrl_ tables from a dump file in app/Ctrl/data
     * @return none
     */
    public function import() {
         DB::unprepared(File::get($this->sql_file));
         $this->info("Data file imported");
    }
}
