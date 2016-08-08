<?php
 
use Illuminate\Routing\Route; 

class GeneratorTest extends \TestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->setAnnotationsPath();

        $this->assertTrue(!file_exists($this->jsonDocsFile()),"file should not exist before test");

    }


    /** @test */
    public function can_generate_api_json_file()
    {

        \L5Swagger\Generators\Generator::generateDocs();


        $this->assertTrue(file_exists($this->jsonDocsFile()));

        config(['l5-swagger.generate_always' => false]);

        $this->visit(route('l5-swagger.docs'))
            ->see('L5 Swagger API')
            ->assertResponseOk();
    }


    /** @test */
    public function can_generate_api_json_file_generate_always_option()
    {
        config(['l5-swagger.generate_always' => true]);


        // with generate_always set this should generate the file
        $this->visit(route('l5-swagger.api'))
            ->see('<title>Swagger UI</title>')
            ->assertResponseOk();

        $this->visit(route('l5-swagger.docs'))
            ->see('L5 Swagger API')
            ->assertResponseOk();

        $this->assertTrue(file_exists($this->jsonDocsFile()));
    }



    /** @test */
    public function can_generate_api_json_file_with_changed_base_path()
    {
        $this->setAnnotationsPath();

        $cfg = config('l5-swagger');
        $cfg['paths']['base'] = '/new/api/base/path';
        config(['l5-swagger' => $cfg]);

        \L5Swagger\Generators\Generator::generateDocs();

        $this->assertTrue(file_exists($this->jsonDocsFile()));

        $this->visit(route('l5-swagger.docs'))
            ->see('L5 Swagger API')
            ->see('/new/api/base/path')
            ->assertResponseOk();
    }
}
