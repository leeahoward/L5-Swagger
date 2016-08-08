<?php

namespace L5Swagger\Console;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use L5Swagger\Generators\Generator;

class GenerateDocsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'l5-swagger:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate docs';


    protected $router;

    /**
     * Execute the console command.
     *
     * @return void
     */

    public function __construct()
    {
        parent::__construct();

    }


    public function fire()
    {
        $this->info('Regenerating docs');
        Generator::generateDocs();
    }
}
