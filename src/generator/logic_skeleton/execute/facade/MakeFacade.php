<?php


namespace huikedev\huike_generator\generator\logic_skeleton\execute\facade;

use huikedev\huike_base\utils\UtilsTools;
use huikedev\huike_generator\generator\logic_skeleton\contract\MakeClassAbstract;
use think\Exception;

/**
 * Desc
 * Class MakeFacade
 * @package huikedev\huike_generator\generator\logic_skeleton\execute\facade
 */
class MakeFacade extends MakeClassAbstract
{
    // 原始动态类类名
    protected $fullOriginClassName;
    // 门面类命名空间
    protected $fullFacadeClassName;
    protected $facadeDir;
    protected $path;
    protected $className;
    protected $count = 0;

    protected $annotation = [];
    protected $annotationStrCache;
    protected $isInApplication = true;

    protected function getStub()
    {
        return $this->getBaseStub().DIRECTORY_SEPARATOR.'Facade.stub';
    }

    public function getFacadeClass()
    {
        return  $this->fullFacadeClassName;
    }

    public function getClassName()
    {
        return  $this->className;
    }


    public function getFacadeClassPath(): string
    {
        return $this->facadeDir.DIRECTORY_SEPARATOR.$this->className.'.php';
    }

    public function getCount(): int
    {
        return count($this->annotation);
    }

    public function setPath(string $path)
    {
        $this->path = $path;
    }

    public function handle($fullOriginClassName,?string $fullFacadeClassName=null,bool $overwrite = false)
    {
        $this->fullOriginClassName = UtilsTools::replaceNamespace($fullOriginClassName);
        if(is_null($fullFacadeClassName)===false){
            $this->fullFacadeClassName = UtilsTools::replaceNamespace($fullFacadeClassName);
        }

        try {
            $this->parseClassInfo();
        }catch (\Exception $e){
            throw new Exception($e->getMessage());
        }
        $facadeFile = $this->facadeDir.DIRECTORY_SEPARATOR.$this->className.'.php';
        if (file_exists($facadeFile) && $overwrite===false) {
            throw new Exception($facadeFile.'已存在');
        }
        $this->getClassAnnotation();
        if (!is_dir($this->facadeDir)) {
            mkdir($this->facadeDir, 0755, true);
        }
        file_put_contents($facadeFile, self::buildClass());
        return $this;
    }



    protected function parseClassInfo():self
    {
        $this->className = pathinfo($this->fullOriginClassName,PATHINFO_FILENAME);
        if(is_null($this->fullFacadeClassName)){
            $this->fullFacadeClassName = UtilsTools::replaceNamespace(pathinfo($this->fullOriginClassName,PATHINFO_DIRNAME).'\facade\\'.$this->className);
        }
        if(is_null($this->path)){
            $this->facadeDir = UtilsTools::replaceSeparator(app()->getRootPath().pathinfo($this->fullFacadeClassName,PATHINFO_DIRNAME));
        }else{
            $path = str_replace(app()->getRootPath(),'',UtilsTools::replaceSeparator($this->path));
            $this->facadeDir = app()->getRootPath().$path.DIRECTORY_SEPARATOR;
        }
        return $this;
    }

    protected function buildClass()
    {
        $stub = file_get_contents($this->getStub());

        $namespace = pathinfo($this->fullFacadeClassName,PATHINFO_DIRNAME);
        $className = $this->className;
        if(is_array($this->importClass)&&count($this->importClass)>0){
            $importClass = implode(';'.PHP_EOL,$this->getUseImportClass());
            $importClass .=';'.PHP_EOL.PHP_EOL;
        }else{
            $importClass = '';
        }

        $annotation = '/**'.PHP_EOL;
        $annotation .=' * @see \\'.$this->fullOriginClassName.PHP_EOL;
        $annotation .=' * @mixin \\'.$this->fullOriginClassName.PHP_EOL;
        $annotation .=implode(PHP_EOL,$this->annotation);
        $annotation .=PHP_EOL;
        $annotation .=' */';

        $class = '\\'.$this->fullOriginClassName.'::class';
        if(substr($class,0,1)!=='\\'){
            $class = '\\'.$class;
        };
        return str_replace(['{%namespace%}','{%importClass%}','{%annotation%}', '{%className%}',  '{%class%}'], [
            $namespace,
            $importClass,
            $annotation,
            $className,
            $class
        ], $stub);

    }


    protected function getClassAnnotation()
    {
        if(class_exists($this->fullOriginClassName)===false){
            throw new Exception($this->fullOriginClassName.' not exist!');
        }
        try{
            $reflectionClass = new \ReflectionClass($this->fullOriginClassName);
            foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method){
                $this->getAnnotationOfMethod($method);
            }
        }catch (\Exception $e){
            throw new Exception($e->getMessage());
        }
        if(count($this->annotation)===0){
            throw new Exception('玩我呢，一个public方法都没有要什么Facade');
        }

    }

    protected function getAnnotationOfMethod(\ReflectionMethod $method):void
    {
        if($method->isPublic()&&$method->isConstructor()===false){
            $this->annotationStrCache =' * @method';
            $this->annotationStrCache .=' '.$this->getActionReturnType($method);
            $this->annotationStrCache .=' '.$method->getName();
            $this->annotationStrCache .=$this->getMethodParameters($method);
            $this->annotationStrCache .=' static';
            $this->annotationStrCache .=' '.$this->getMethodDesc($method);
            $this->annotation[] = $this->annotationStrCache;
        }

    }

    protected function getActionReturnType(\ReflectionMethod $method)
    {
        if($method->hasReturnType()){
            return $this->parseType($method->getReturnType());
        }
        return 'mixed';

    }
    protected function getMethodParameters(\ReflectionMethod $method)
    {
        if($method->getNumberOfParameters()===0){
            return '()';
        }
        $paramArr = [];
        foreach ($method->getParameters() as $key => $parameter){

            $paramArr[]=$this->parseMethodParameter($parameter,$key);
        }
        $param = implode(',',$paramArr);
        return '('.$param.')';
    }
    protected function parseType(\ReflectionNamedType $returnType,$parameter=false)
    {
        //php 内置返回类型 int string array float等
        if($returnType->isBuiltin()){
            return $returnType->getName();
        }
        $returnTypeName = $returnType->getName();
        //返回自身
        if($returnTypeName==='self'){
            return '\\'.$this->fullOriginClassName;
        }
        //类的实例
        if(class_exists($returnTypeName)){
            $this->addImportObject($returnTypeName);
            $returnTypeArray =explode('\\',$returnTypeName);
            return array_pop($returnTypeArray);
        }
        return 'mixed';
    }
    protected function getMethodDesc(\ReflectionMethod $method)
    {
        if($method->getDocComment()===false){
            return null;
        }
        $docComment = $method->getDocComment();
        $matches = preg_match('/@(desc|description)(.*)\n/Su',$docComment,$desc);
        if($matches){
            return trim(array_pop($desc));
        }
        return null;
    }
    protected function parseMethodParameter(\ReflectionParameter $parameter,$key)
    {
        $parameterStr = '';
        if($parameter->hasType()){
            $parameterStr .= $key===0?'':' ';
            $parameterStr .=$this->parseType($parameter->getType(),true); ;
        }
        $parameterStr .=' $'.$parameter->getName();
        if($parameter->isDefaultValueAvailable()){
            $parameterStr .='='.$this->parseParameterDefaultValue($parameter->getDefaultValue());
        }
        return $parameterStr;
    }
    protected function parseParameterDefaultValue($defaultValue)
    {
        $valueType = gettype($defaultValue);
        switch (true){
            case $valueType==='integer':
                return (int)$defaultValue;
                break;
            case $valueType==='string':
                return (string)'\''.$defaultValue.'\'';
                break;
            case $valueType==='double':
                return (float)$defaultValue;
                break;
            case $valueType==='array':
                //此处太难处理，暂时只支持空数组
                return '[]';
                break;
            case $valueType==='boolean':
                return $defaultValue?'true':'false';
                break;
            default:
                return 'null';
        }
    }
    protected function addImportObject(string $importObjectNameSpace):void
    {
        $this->importClass[] = $importObjectNameSpace;
    }
}