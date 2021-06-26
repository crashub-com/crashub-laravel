<?php

namespace Crashub\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crashub:install {projectKey}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure the Crashub client';

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
     * @return int
     */
    public function handle()
    {
        file_put_contents(base_path('.env'), "\nCRASHUB_PROJECT_KEY={$this->argument('projectKey')}", FILE_APPEND | LOCK_EX);
        file_put_contents(base_path('.env.example'), "\nCRASHUB_PROJECT_KEY=", FILE_APPEND | LOCK_EX);

        return Artisan::call('vendor:publish', ['--tag' => 'crashub-config']);
    }
}
