<?php

namespace L5Swagger\Http\Controllers;

use App;
use File;
use Request;
use Storage;
use Response;
use L5Swagger\Generator;
use Illuminate\Routing\Router;
use Illuminate\Routing\Controller as BaseController;

class SwaggerController extends BaseController
{


    /**
     * Dump api-docs.json content endpoint.
     *
     * @param string $jsonFile
     *
     * @return \Response
     */
    public function docs($jsonFile = null)
    {
        $content = "";

        // always run the generator if generator_always option is set
        if ( config('l5-swagger.generate_always') ) {
            $this->runGenerator();
        }

        // only run the generator if the file does not exist
        if(config('l5-swagger.use_filesystems_api')){
            // use filesystems_api_disk
            if(!Storage::drive(config('l5-swagger.filesystems_api_disk'))->exists(config('l5-swagger.paths.docs_json'))) {
                $this->runGenerator();
            }
            // file is there now get the contents
            $content = Storage::drive(config('l5-swagger.filesystems_api_disk'))->get(config('l5-swagger.paths.docs_json'));
        }
        else{
            // use configred "docs" path and concat with docs_json path to get the file
            $filePath = config('l5-swagger.paths.docs').'/'.(!is_null($jsonFile) ? $jsonFile : config('l5-swagger.paths.docs_json', 'api-docs.json'));

            if (File::extension($filePath) === '') {
                $filePath .= '.json';
            }
            if (! File::exists($filePath)) {
                // run generator if file not found
                $this->runGenerator();
            }
            $content = File::get($filePath);
        }



        return Response::make($content, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Display Swagger API page.
     *
     * @return \Response
     */
    public function api()
    {
        if (config('l5-swagger.proxy')) {
            $proxy = Request::server('REMOTE_ADDR');
            Request::setTrustedProxies([$proxy]);
        }

        $extras = [];
        if (array_key_exists('validatorUrl', config('l5-swagger'))) {
            // This allows for a null value, since this has potentially
            // desirable side effects for swagger. See the view for more
            // details.
            $extras['validatorUrl'] = config('l5-swagger.validatorUrl');
        }

        // Need the / at the end to avoid CORS errors on Homestead systems.
        $response = Response::make(
            view('l5-swagger::index', [
                'apiKey'             => config('l5-swagger.api.auth_token'),
                'apiKeyVar'          => config('l5-swagger.api.key_var'),
                'securityDefinition' => config('l5-swagger.api.security_definition'),
                'apiKeyInject'       => config('l5-swagger.api.key_inject'),
                'secure'             => Request::secure(),
                // -lee:commented this out because we always want the route only
                //   Not sure why this uses docs_json - which is the filename
                //   the 'docs' route by itself should always pull the json file
                //'urlToDocs'          => route('l5-swagger.docs', config('l5-swagger.paths.docs_json', 'api-docs.json')),
                'urlToDocs'          => route('l5-swagger.docs'),
                'requestHeaders'     => config('l5-swagger.headers.request'),
                'docExpansion'       => config('l5-swagger.docExpansion'),
                'highlightThreshold' => config('l5-swagger.highlightThreshold'),
            ], $extras),
            200
        );

        $headersView = config('l5-swagger.headers.view');
        if (is_array($headersView) and ! empty($headersView)) {
            foreach ($headersView as $key => $value) {
                $response->header($key, $value);
            }
        }

        return $response;
    }

    private function runGenerator(){
        if ( config('l5-swagger.use_alternate_generator') == false) 
        {
            \L5Swagger\Generators\Generator::generateDocs();
        } 
        else if ( config('l5-swagger.use_alternate_generator') == true) 
        {
            App::make('service.l5-swagger.swagger_generator')->generateDocs();
        }

    }
}
