<?php

class TestCase extends Orchestra\Testbench\TestCase
{


    protected function getPackageProviders($app)
    {
        return [
            L5Swagger\L5SwaggerServiceProvider::class,
        ];
    }


    /* 
        clean up the files, etc.. created by these tests
    */
    public function tearDown()
    {

        if (file_exists($this->jsonDocsFile())) {
            unlink($this->jsonDocsFile());
            rmdir(config('l5-swagger.paths.docs'));
        }

        if (! is_dir(config('l5-swagger.paths.docs'))) {
            mkdir(config('l5-swagger.paths.docs'));
        }


        if(file_exists(config_path('l5-swagger.php'))){
            unlink(config_path('l5-swagger.php'));
        }

        if(file_exists(config_path('../'.config('l5-swagger.template_file')))){
            unlink(config_path('../'.config('l5-swagger.template_file')));
        }
        
        parent::tearDown();
    }

    /* 
        places a dummy file in the location of the swagger annotations file
    */
    protected function createJsonDocumentationFile()
    {
        if (! is_dir(config('l5-swagger.paths.docs'))) {
            mkdir(config('l5-swagger.paths.docs'));
        }
        file_put_contents($this->jsonDocsFile(), '{}');
    }


    /* 
        gets the configured path of the swagger annotations file
        and creates the folder that it should exist in
    */
    protected function jsonDocsFile()
    {
        return config('l5-swagger.paths.docs').'/'.config('l5-swagger.paths.docs_json');
    }


    /* 
        sets the config path where annotations are stored
        and sets the generate_always flag to true
    */
    protected function setAnnotationsPath()
    {
        $cfg = config('l5-swagger');
        $cfg['paths']['annotations'] = __DIR__.'/storage/annotations';
        $cfg['generate_always'] = true;

        //Adding constants which will be replaced in generated json file
        $cfg['constants']['L5_SWAGGER_CONST_HOST'] = 'http://my-default-host.com';

        config(['l5-swagger' => $cfg]);
    }

    /* 
        change the docs filename in the config
    */
    protected function setCustomDocsFileName($fileName)
    {
        $cfg = config('l5-swagger');
        $cfg['paths']['docs_json'] = $fileName;
        config(['l5-swagger' => $cfg]);
    }

    /* 
        change the config to use the filestorage api
    */
    protected function setUse_filessystems_api()
    {
        $cfg = config('l5-swagger');
        $cfg['use_filesystems_api'] = true;
        config(['l5-swagger' => $cfg]);
    }


    protected function loadFileSystemConfig()
    {
        $cfg = [
            'default' => 'local',
            'cloud' => 's3',
            'disks' => [
                'l5swagger' => [
                    'driver' => 'local',
                    'root'   => storage_path('app/l5swagger'),
                ],
                'local' => [
                    'driver' => 'local',
                    'root'   => storage_path('app'),
                ],

                'public' => [
                    'driver'     => 'local',
                    'root'       => storage_path('app/public'),
                    'visibility' => 'public',
                ],

                's3' => [
                    'driver' => 's3',
                    'key'    => 'your-key',
                    'secret' => 'your-secret',
                    'region' => 'your-region',
                    'bucket' => 'your-bucket',
                ],

            ],

        ];

        config(['filesystems' => $cfg]);


    }

    
    /**
     * helper methods
     *
     */
    public function getRoutes(){
        $routes = [
            new Illuminate\Routing\Route(
                ['POST','GET','PUT'], /* methods */
                'this/is/a/uri', /* uri */
                $this->getMockAction() /* action */
            )
        ];
        return $routes;
    }

    public function getRouterMock(){

        $dispatcherMock = $this->getMockBuilder('Illuminate\Contracts\Events\Dispatcher')
            ->getMock();

        $stub = $this->getMockBuilder('Illuminate\Routing\Router')
            ->setConstructorArgs(array($dispatcherMock))
            ->getMock();
        $stub->method('getRoutes')
            ->will($this->returnValue($this->getRoutes()));
        return $stub;

    }

    public function getMockAction()
    {
        return ["uses"=>"TestCase@check"];
        /*return function(){
            echo "this is a callable function";
            return true;
        };*/
    }


    public function isJsonString($string) {
         json_decode($string);
         return (json_last_error() == JSON_ERROR_NONE);
    }

    public function check() {
        return 1;
    }
}
