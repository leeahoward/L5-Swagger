<?php

use Illuminate\Support\Facades\Artisan;

class SwaggerControllerTest extends \TestCase
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

    
}
