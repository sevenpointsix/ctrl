<?php

namespace Sevenpointsix\Ctrl\Commands;

use Illuminate\Console\Command;

use DB;
//use Config;
//use View;
//use File;

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
            $this->line('Usage: php artisan ctrl:synch files|data|tidy|all --wipe');
        }      
        
    }

    /**
     * Export the two ctrl_ tables to a dump file in app/Ctrl/data
     * @return none
     */
    public function export() {

    }

     /**
     * Import the two ctrl_ tables from a dump file in app/Ctrl/data
     * @return none
     */
    public function import() {

    }
}
