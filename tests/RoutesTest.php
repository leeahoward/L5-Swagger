<?php

class RoutesTest extends \TestCase
{
    /** @test */
    public function json_file_is_generated_if_it_doesnt_exist()
    {

        $this->setAnnotationsPath();
        $this->assertFalse(file_exists($this->jsonDocsFile()),"File should not exist before test");

        // set generate_always to false, so it will generate 
        // only if file is missing
        $cfg = config('l5-swagger');
        $cfg['generate_always'] = false;
        config(['l5-swagger' => $cfg]);


        $jsonUrl = route('l5-swagger.docs');

        $this->visit($jsonUrl)
            ->see('L5 Swagger API')
            ->assertResponseOk();

        $this->assertTrue(file_exists($this->jsonDocsFile()),"File should exist once the test is done");
    }

    /** @test */
    public function user_can_access_json_file_if_it_is_generated()
    {
        $jsonUrl = route('l5-swagger.docs');

        $this->createJsonDocumentationFile();
        
        $this->assertTrue(file_exists($this->jsonDocsFile()),"File should not exist before test");

        $this->visit($jsonUrl)
            ->see('{}')
            ->assertResponseOk();
    }

    /** @test */
    public function user_can_access_and_generate_custom_json_file()
    {
        $customJsonFileName = 'docs.v1.json';

        $jsonUrl = route('l5-swagger.docs', $customJsonFileName);

        $this->setCustomDocsFileName($customJsonFileName);
        
        $this->createJsonDocumentationFile();

        $this->visit($jsonUrl)
            ->see('{}')
            ->assertResponseOk();
    }

    /** @test */
    public function user_can_access_documentation_interface()
    {
        $this->visit(config('l5-swagger.routes.api'))
        ->see(route('l5-swagger.docs'))
        ->assertResponseOk();
    }
}
