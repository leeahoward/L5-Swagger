<?php

namespace L5Swagger\Console;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;

class GenerateAlternateDocsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'l5-swagger:generate_alternate';


    protected $generator;

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

    public function __construct($generator)
    {
        parent::__construct();

        $this->generator = $generator;
    }


    public function fire()
    {
        $this->info('Regenerating docs using alternate method');
        $this->generator->generateDocs();
    }
}
