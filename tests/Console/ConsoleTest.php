<?php

use Illuminate\Support\Facades\Artisan;

class ConsoleTest extends \TestCase
{
    /** @test */
    public function can_generate()
    {
        $this->setAnnotationsPath();

        $this->assertFalse(file_exists($this->jsonDocsFile()),"File should not exist before test");

        Artisan::call('l5-swagger:generate');

        $this->assertFileExists($this->jsonDocsFile());

        $fileContent = file_get_contents($this->jsonDocsFile());

        $this->assertJson($fileContent);
        $this->assertContains('L5 Swagger API', $fileContent);
    }


    /** @test */
    public function can_publish()
    {

        $this->setAnnotationsPath();

        $this->assertFalse(file_exists(config_path('l5-swagger.php')),"File should not exist before test");

        Artisan::call('l5-swagger:publish');

        $this->assertTrue(file_exists(config_path('l5-swagger.php')));
        $this->assertTrue(file_exists(config('l5-swagger.paths.views').'/index.blade.php'));

    }

    /** @test */
    public function can_generate_alternate()
    {
        $this->setAnnotationsPath();
        
        $this->assertFalse(file_exists($this->jsonDocsFile()),"File should not exist before test");

        Artisan::call('l5-swagger:generate_alternate');

        $this->assertFileExists($this->jsonDocsFile());

        $fileContent = file_get_contents($this->jsonDocsFile());

        $this->assertJson($fileContent);
        $this->assertContains('Swagger 2.0 Template', $fileContent);
    }


    /** @test */
    public function can_publish_config_and_swagger_template()
    {

        $this->setAnnotationsPath();

        $this->assertFalse(file_exists(config_path('l5-swagger.php')),"File should not exist before test");

        $this->assertTrue(!file_exists(config_path('../'.config('l5-swagger.template_file'))),"template_file should exist after publish:".config_path('../'.config('l5-swagger.template_file')));

        Artisan::call('l5-swagger:publish-config');

        $this->assertTrue(file_exists(config_path('l5-swagger.php')),"config file should exist after publish:".config_path('l5-swagger.php'));

        $this->assertTrue(file_exists(config_path('../'.config('l5-swagger.template_file'))),"template_file should exist after publish:".config_path('../'.config('l5-swagger.template_file')));

    }

    
}
