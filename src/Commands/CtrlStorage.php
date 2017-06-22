<?php

namespace Sevenpointsix\Ctrl\Commands;

use Illuminate\Console\Command;

use File;

class CtrlStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ctrl:storage
                                {direction : what do we want to do with files in Storage; pull or push? }
                                {environment : what environment are we synching with; staging or production? }
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise the storage/public folder that the CMS uses. Should eventually be able to pull or push files to and from staging and production. Currently, it only pulls files from staging.';

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

        if (app()->environment() != 'local') {
            $this->error("This command is designed to be run from a local environment only.");
            exit();
        }

        $direction   = $this->argument('direction');
        $environment = $this->argument('environment');

        if (
            !in_array($direction, ['pull','push'])
            ||
            !in_array($environment, ['staging','production'])
        ) {
            $this->error("Sample usage: ctrl:storage pull|push staging|production");
            exit();
        }

        // Temporarily...
        if ($direction == 'push' || $environment == 'production') {
            $this->error("Currently this command will only pull files from the staging environment");
            exit();
        }

        /**
         * We're going to assume that the name of the website folder on the remote environment
         * matches the one we're using locally...
         */
        $project_path   = realpath(base_path().'/..'); // eg, /Users/chrisgibson/Projects/argos-support.co.uk
        $website_folder = last(explode('/',$project_path));

        $remote_connection = 'phoenixdigital@staging.phoenixdigital.agency';
        $remote_path       = '/var/www/'.$website_folder.'/public_html/storage/app/public/';
        $local_path        = storage_path('app/public');

        $rsync_command = 'rsync -avz '.$remote_connection.':'.$remote_path.' '.$local_path;

        dd($rsync_command);

        $this->info('Done.');
    }
}
