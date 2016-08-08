<?php
namespace L5Swagger\Generators;
use Doctrine\Common\Annotations\DocParser;

use App;
use Config;
use File;
use Storage;
use ReflectionParameter;
//use L5Swagger\Helpers\ReflectionHelper;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Console\Command;
use Illuminate\Routing\Controller;

class LaravelSwaggerGenerator
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name= 'swagger:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate swagger documentation';

    /**
     * The router instance.
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;
    /**
     * An array of all the registered routes.
     *
     * @var \Illuminate\Routing\RouteCollection
     */
    protected $routes;
    /**
     * An array of all the tags.
     *
     * @var array
     */
    protected $tags;
    /**
     * is this running in php unit
     * required because error handler causes a fatal exception
     * when calling this->error() from parent class
     *
     * @var array
     */
    protected $phpunit;

    protected $basePath;

    protected $config;
    protected $swagger_php_docs;


    /**
     * Create a new route command instance.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function __construct(Router $router, $config )
    {
        $this->config= $config;
        $this->router = $router;
        $this->tags = [];
        $this->routes = $router->getRoutes();
        $this->generatedFile = "";


    }



    public function generateDocs()
    {
        $docDir = $this->config['paths']['docs'];
        $docsJson= $this->config['paths']['docs_json'];
        $excludeDirs = $this->config['paths']['excludes'];
        $swagger = "";

        // load the docs from the annotations
        //$this->$swagger_php_docs = \Swagger\scan($appDir, ['exclude' => $excludeDirs]);

        // need to add the ability to exclude certain routes
        // add filters/groups for public and private docs
        $this->scan();




        $filename = $docDir.'/'.$this->config['paths']['docs_json']; // api-docs.jsn

        // save the file
        $this->save();
    }



    public function saveToPath()
    {
        $docDir = $this->config['paths']['docs'];
        $docsJson= $this->config['paths']['docs_json'];
        $excludeDirs = $this->config['paths']['excludes'];


        if (! File::exists($docDir) || is_writable($docDir)) {
            // delete all existing documentation
            if (File::exists($docDir)) {
                File::deleteDirectory($docDir);
            }
            File::makeDirectory($docDir);
        }

        $filename = $docDir.'/'.$this->config['paths']['docs_json']; // api-docs.jsn

        // save the file
        if (file_put_contents($filename, $this) === false) {
            throw new \Exception('Failed to saveAs("' . $filename . '")');
        }
    }
    

    public function saveToFileSystem()
    {
        $disk= $this->config['filesystems_api_disk'];
        $docsJson= $this->config['paths']['docs_json'];

        // save the file
        Storage::disk($disk)->put($docsJson,$this);
    }


    public function scan(){
        $this->generatedFile = json_encode($this->getSwaggerData(),JSON_PRETTY_PRINT);
    }


    public function readSwaggerTemplateFile(){
        $file = $this->config['template_file'];
        if(File::exists($file)){
            $contents = File::get($file);
        }
        else{
            throw new \Exception('The Template file was not found("' . $file. '")');
        }

        return json_decode($contents);
    }

    public function getSwaggerData(){

        $data = $this->readSwaggerTemplateFile();
        $this->basePath = $data->basePath;

        $data->paths = $this->getRoutesStructure($this->routes);
        $data->tags = $this->tags;
        return $data;
    }

    /**
     * Compile the routes into a displayable format.
     *
     * @return array
     */
    public function getRoutesStructure($routes)
    {
        $results = [];
        // gather all unique routes
        foreach ($routes as $route) {
            $uri = '/'.$route->getUri();
            $methods = $route->methods();
            if( !isset($results[$uri]) ){
                $results[$uri] = [];
            }
            foreach($methods as $method){
                $method = strtolower($method);
                if( !isset($results[$uri][$method]) ){
                    $results[$uri][$method] = [];
                }

                // append this route and method
                // pass in uri because we added leading slash

                $results[$uri][$method] = $this->getMethodInformation($uri,$route,$method);
            }
        }

        usort($this->tags,function($a,$b){
                return strcmp($a["name"], $b["name"]);
        });
        return $results;
    }

    public function addTag($name){
        // determine if we already have the tag
        foreach($this->tags as $tagdata){
            if($tagdata['name']==$name){
                return;
            }
        }
        $this->tags[] = [
            'name' => $name,
            'description' => "",
        ];
    }

    /**
     * Get the method information for a given method of this route.
     *
     * @param  string $method
     * @param  Route $route
     * @return array 
     */
    public function getMethodInformation($uri,$route,$method){
        // add all uri's as tags
        $middlewaretag = $this->getMiddleware($route);
        if(!isset($middlewaretag) || $middlewaretag =="" ){
            $middlewaretag = "public";
        }
        $tag = str_replace($this->basePath, '', $uri);
        if(!isset($tag)){
            $tag = "none";
        }
        $this->addTag($tag);

        return [
            'operationId' => $route->getUri().":".$method,
            'summary' => 'route summary??',
            'description' => ""
                . $this->getDescriptionForAction($route->getActionName())."",
                //. "name(" . $route->getName() . ")" 
                //. "middleware(" . $this->getMiddleware($route) . ")",
            'tags' => [$tag],
            'parameters' => $this->getAllParameterInformation($route),
            'responses' => ["200" => ["description" => "need to pull description. . . "]],
            'security' => [],
            'consumes' => [],
            'produces' => [],
        ];
    }


    /**
     * Get the parameter information for a given route.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return array
     */
    public function getAllParameterInformation(Route $route)
    {
        $results = [];
        
        foreach ($route->signatureParameters() as $param) {
            $tmp = $this->getParameterInformation($param,$route);
            // ignore request parameter
            if($tmp["name"] != "request"){
                $results[] = $this->getParameterInformation($param,$route);
            }
        }

        
        return $results;
    }

    /**
     * Get the parameter information for a route and param.
     *
     * @param  ReflectionParameter $param
     * @param  \Illuminate\Routing\Route  $route
     * @return array
     */
    public function getParameterInformation(ReflectionParameter $param, Route $route)
    {

        $type = "string";
        /*
        if($param->hasType()){
            $type = $param->getType();
        }
        */
        $results = [
            "in" => "path",
            "name" => $param->getName(),
            "description" => "",
            "required" => true,
            "type" => $type,
            /*"schema" => [
              "$ref": "#/definitions/Pet"
            ]*/
        ];
        
        return $results;
    }

    /**
     * Display the route information on the console.
     *
     * @param  array  $routes
     * @return void
     */
    public function displayRoutes(array $routes)
    {
        $output = json_encode($routes,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
        echo $output;
        return $output;
    }
    /**
     * Get before filters.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return string
     */
    protected function getMiddleware($route)
    {
        $middlewares = array_values($route->middleware());
        $actionName = $route->getActionName();
        if (! empty($actionName) && $actionName !== 'Closure') {
            $middlewares = array_merge($middlewares, $this->getControllerMiddleware($actionName));
        }
        return implode(',', $middlewares);
    }
    /**
     * Get the description for the given Controller@action name.
     *
     * @param  string  $actionName
     * @return array
     */
    protected function getDescriptionForAction($actionName)
    {

        $segments = explode('@', $actionName);

        if( count($segments) == 2) {
            $method = new \ReflectionMethod($segments[0],$segments[1]);
            $comment = $method->getDocComment();
            $result = array();
            //define the regular expression pattern to use for string matching

            if (preg_match_all('/@(\w+)\s+(.*)\r?\n/m', $comment, $matches)){
              $result = array_combine($matches[1], $matches[2]);
            }

            $result = "```".chr(10).print_r( $result, true ).chr(10)."```";
            return $result;
        }
        else{
            return "??";
        }
    }

    /**
     * Get the controller for the given Controller@action name.
     *
     * @param  string  $actionName
     * @return array
     */
    protected function getControllerForAction($actionName)
    {
        Controller::setRouter($this->router);
        $segments = explode('@', $actionName);
        return App::make($segments[0]);
    }
    /**
     * Get the middleware for the given Controller@action name.
     *
     * @param  string  $actionName
     * @return array
     */
    protected function getControllerMiddleware($actionName)
    {
        Controller::setRouter($this->router);
        $segments = explode('@', $actionName);
        $controller = $this->getControllerForAction($actionName);
        $methodname = $segments[1];
        return $this->getControllerMiddlewareFromInstance(
            $controller, $methodname
        );
    }
    /**
     * Get the middlewares for the given controller instance and method.
     *
     * @param  \Illuminate\Routing\Controller  $controller
     * @param  string  $method
     * @return array
     */
    protected function getControllerMiddlewareFromInstance($controller, $method)
    {
        $middleware = $this->router->getMiddleware();
        $results = [];
        foreach ($controller->getMiddleware() as $name => $options) {
            if (! $this->methodExcludedByOptions($method, $options)) {
                $results[] = Arr::get($middleware, $name, $name);
            }
        }
        return $results;
    }
    /**
     * Determine if the given options exclude a particular method.
     *
     * @param  string  $method
     * @param  array  $options
     * @return bool
     */
    protected function methodExcludedByOptions($method, array $options)
    {
        return (! empty($options['only']) && ! in_array($method, (array) $options['only'])) ||
            (! empty($options['except']) && in_array($method, (array) $options['except']));
    }
    /**
     * Filter the route by URI and / or name.
     *
     * @param  array  $route
     * @return array|null
     */
    protected function filterRoute(array $route)
    {
        if (($this->config['api_name_filter'] && ! Str::contains($route['name'], $this->config['api_name_filter'])) ||
             $this->config['api_path_filter'] && ! Str::contains($route['uri'], $this->config['api_path_filter']) ||
             $this->config['api_method_filter'] && ! Str::contains($route['method'], $this->config['api_method_filter'])){
            return;
        }
        return $route;
    }


    /**
     * Save the swagger documentation to a file.
     * @param string $filename
     * @throws Exception
     */
    public function save()
    {

        if($this->config['use_filesystems_api']){
            $this->saveToFileSystem();
        }
        else{
            $this->saveToPath();
        }
    }


    public function __toString(){
        return $this->generatedFile;
    }


}