<?php

namespace Sevenpointsix\Ctrl\Commands;

use Illuminate\Console\Command;

use File;

class CtrlSymLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ctrl:symlink
                                {folder? : the project folder to point to, such as argos-support.co.uk }
                                {webroot=webroot : the name of the webroot we\'re using, defaults to \'webroot\' }
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the symlink that points to another app/Ctrl folder; this is how we can use dev.ctrl-c.ms to manage other sites. We also now link to Http/Controllers/Ctrl if it exists, as this is where we should be keeping a Custom Controller.';

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

        if (
            env('APP_URL', false) != 'http://dev.ctrl-c.ms'
            ||
            app()->environment() != 'local'
        ) {
            $this->error("This command is designed to be run from the local ctrl-c.ms site only.");
            exit();
        }

        $project_folder = $this->argument('folder');
        $webroot        = $this->argument('webroot');

        if (!$project_folder) {
            $this->error("Sample usage: ctrl:symlink argos-support.co.uk");
            exit();
        }

        $project_root = realpath(base_path().'/../..'); // eg, /Users/chrisgibson/Projects
        $project_path = implode('/', [$project_root,$project_folder]);

        if(!File::exists($project_path)) {
            $this->error("$project_folder doesn't seem to be a valid project folder");
            $this->info("This command will look for a directory at $project_path");
            exit();
        }

        $ctrl_path              = implode('/', [$project_path,$webroot,'app','Ctrl']);

        if(!File::exists($ctrl_path)) {
            $this->error("$project_folder doesn't seem to contain a valid Ctrl folder");
            $this->info("This command will look for a folder at $ctrl_path");
            exit();
        }

        // OK, we have a valid app/Ctrl folder to link to. Remove the existing one (if present) and then create a new symlink:
        $symlink = app_path('Ctrl');
        if (is_link($symlink)) {
            $this->line("Removing existing symlink at $symlink");
            unlink($symlink);
        }
        
        $this->line("Creating new symlink at ".implode('/',['app','Ctrl']));
        symlink ($ctrl_path, $symlink); // Effectively ln -s $ctrl_path $symlink

        // Also create a symlink to Http/Controllers/Ctrl if it exists; see $description above

        $custom_controller_path = implode('/', [$project_path,$webroot,'app','Http','Controllers','Ctrl']);        
        $symlink = app_path('Http/Controllers/Ctrl');

        if (is_link($symlink)) {
            $this->line("Removing existing symlink at $symlink");
            unlink($symlink);
        }

        if (!File::exists($custom_controller_path)) {
            $this->line("Creating new symlink at ".implode('/',['app','Http','Controllers','Ctrl']));
            symlink ($custom_controller_path, $symlink); // Effectively ln -s $custom_controller_path $symlink");
        }

        $this->info('Done. Don\'t forget to switch database in .env if necessary.');        
    }
}
