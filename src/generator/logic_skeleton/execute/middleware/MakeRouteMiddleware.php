<?php


namespace huikedev\huike_generator\generator\logic_skeleton\execute\middleware;


use huikedev\huike_base\app_const\HuikeConfig;
use huikedev\huike_base\utils\UtilsTools;
use huikedev\huike_generator\generator\logic_skeleton\contract\ClassGenerateAbstract;
use think\Exception;
use think\helper\Str;

class MakeRouteMiddleware extends ClassGenerateAbstract
{
    /**
     * @var string
     */
    protected $className;
    protected $file;
    protected $namespacePrefix;
    protected $modulePathName;
    public function __construct()
    {
        $this->path = app()->getRootPath().'huike';
        $this->namespacePrefix = 'huike\\';
    }
    protected function getStub()
    {
        return $this->getBaseStub().DIRECTORY_SEPARATOR.'RouteMiddleware.stub';
    }

    public function getFullClassName(): string
    {
        return UtilsTools::replaceNamespace($this->namespacePrefix.'\common\middlewares\\'.$this->className);
    }

    public function setNamespacePrefix(string $prefix): MakeRouteMiddleware
    {
        $this->namespacePrefix = $prefix;
        return $this;
    }

    public function getFile()
    {
        return $this->file;
    }

    protected function getPath()
    {
        return app()->getRootPath().str_replace(app()->getRootPath(),'',$this->path).DIRECTORY_SEPARATOR.'common'.DIRECTORY_SEPARATOR.'middlewares';
    }

    protected function getNamespace(): string
    {
        return UtilsTools::replaceNamespace($this->namespacePrefix.'\common\middlewares');
    }

    public function handle(string $name)
    {
        $this->modulePathName = Str::snake($name);
        $this->className = Str::studly($name.'RouteMiddleware');
        if (!is_dir($this->getPath())) {
            mkdir($this->getPath(), 0755, true);
        }
        $this->file = $this->getPath().DIRECTORY_SEPARATOR.$this->className.'.php';
        if(file_exists($this->file)){
            throw new Exception($this->file.'已存在');
        }
        file_put_contents($this->file, self::buildClass());
        return $this;
    }

    protected function buildClass()
    {
        $stub = file_get_contents($this->getStub());
        return str_replace(['{%namespace%}','{%className%}','{%modulePathName%}','{%moduleNameSpace%}'],[$this->getNamespace(),$this->className,$this->modulePathName,$this->namespacePrefix],$stub);
    }
}