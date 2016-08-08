<?php

use Illuminate\Routing\Route;

class LaravelSwaggerGeneratorTest extends \TestCase
{
    private $dispatcherMock;
    private $OIT;


    public function setUp()
    {
        parent::setUp();

    	$this->OIT = new \L5Swagger\Generators\LaravelSwaggerGenerator($this->getRouterMock(),config('l5-swagger'));

        config(['l5-swagger.use_alternate_generator' => true]);

        $this->assertEquals( "api-docs.json", config('l5-swagger.paths.docs_json'));

    }

    /** @test */
    public function can_generate_api_json_file_using_filesystems_api()
    {
        // configure to use filesystems api
        $this->loadFileSystemConfig();
        Storage::disk( config('l5-swagger.filesystems_api_disk') )->delete( config('l5-swagger.paths.docs_json') );
        // make sure swagger file does not exist on selected disk
        $this->assertTrue( !Storage::disk( config('l5-swagger.filesystems_api_disk') )->exists( config('l5-swagger.paths.docs_json') ) );

        // update config to use the filesystems api
        $this->setUse_filessystems_api();

        $this->OIT = new \L5Swagger\Generators\LaravelSwaggerGenerator($this->getRouterMock(),config('l5-swagger'));

        $this->assertEquals( true, config('l5-swagger.use_filesystems_api'));

        $this->OIT->generateDocs();

        //var_dump(Storage::disk(config('l5-swagger.filesystems_api_disk'))->allFiles());

        $this->assertTrue(
            Storage::disk( config('l5-swagger.filesystems_api_disk') )->exists( config('l5-swagger.paths.docs_json' ) ), 
            config('l5-swagger.paths.docs_json')."<<- should now exist on filestorage disk:".config('l5-swagger.filesystems_api_disk')
        );


        // don't generate the file when visiting the route
        config(['l5-swagger.generate_always' => false]);

        // get the file contents by visiting the route
        $this->visit(route('l5-swagger.docs'))
            ->see('Swagger 2.0 Template')
            ->assertResponseOk();
    }



    /** @test */
    public function can_generate_api_json_file()
    {

        $this->assertTrue(!file_exists($this->jsonDocsFile()),"file should not exists before test");

        $this->OIT->generateDocs();


        $this->assertTrue(file_exists($this->jsonDocsFile()));

        // don't generate the file when visiting the route
        config(['l5-swagger.generate_always' => false]);

        // get the file contents by visiting the route
        $this->visit(route('l5-swagger.docs'))
            ->see('Swagger 2.0 Template')
            ->assertResponseOk();
    }


    public function test_ensure_read_swagger_template_file()
    {
        $response = $this->OIT->readSwaggerTemplateFile();

        //var_dump($response);
    }


    public function test_ensure_operationId_is_set_in_data()
    {
        $routes = $this->getRoutes();
        $response = $this->OIT->getMethodInformation('post',$routes[0],$routes[0]->getUri());

        $this->assertTrue($response['operationId']=="this/is/a/uri:this/is/a/uri", "should set the proper operationId"); 
    }




    public function test_ensure_toString_returns_json_data_after_scan()
    {
        $this->OIT->scan();
        $json = $this->OIT->__toString();
        $this->assertTrue($this->isJsonString($json),"failed asserting that toString returns json data after scan() is called");
    }


    public function test_ensure_header_data_is_set_correctly()
    {
        $this->OIT->getSwaggerData();

    }


    public function testGetRouteStructure()
    {
        $this->OIT->getRoutesStructure($this->getRoutes());
    }




}