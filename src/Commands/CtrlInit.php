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

        // We should check for (and create?) the writeable app/Ctrl folder here.

        $this->call('migrate', [
            '--path' => 'packages/sevenpointsix/Ctrl/database'
        ]);
         
        /* Don't think we need to check an action for this command, do we?
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
        */
    }

}
