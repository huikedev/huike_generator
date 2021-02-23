<?php


namespace huikedev\huike_generator\generator\logic_skeleton\execute\service;

use huikedev\huike_base\utils\UtilsTools;
use huikedev\huike_generator\generator\logic_skeleton\contract\MakeClassAbstract;
use think\Exception;

/**
 * Desc
 * Class MakeFacade
 * @package huikedev\huike_generator\generator\logic_skeleton\execute\facade
 */
class MakeServiceFacade extends MakeClassAbstract
{
    // 原始动态类类名
    protected $originClassName;
    // 门面类命名空间
    protected $facadeNamespace;
    protected $facadeDir;
    // 动态类命名空间
    protected $originNamespace;
    // 门面类完整命名空间 + 类名
    protected $facadeFullClassname;
    // 动态类类完整命名空间 + 类名
    protected $originFullClassname;
    // 动态类的路径
    protected $originClassPath;
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
        return  $this->facadeFullClassname;
    }

    public function getOriginClassPath()
    {
        return $this->originClassPath;
    }

    public function getFacadeClassPath()
    {
        return $this->facadeDir.DIRECTORY_SEPARATOR.$this->originClassName.'.php';
    }

    public function getCount()
    {
        return count($this->annotation);
    }

    public function handle($className,int $dirLevel=0,bool $overwrite = false)
    {
        try {
            $this->parseClassInfo($className,$dirLevel);
        }catch (\Exception $e){
            throw new Exception($e->getMessage());
        }

        $facadeFile = $this->facadeDir.DIRECTORY_SEPARATOR.$this->originClassName.'.php';
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

    protected function parseClassInfo(string $classname,int $dirLevel=0):self
    {
        $this->originFullClassname = UtilsTools::replaceNamespace($classname);
        $rootPath = $this->replaceSeparator(app()->getRootPath());
        try {
            $classInfo = new \ReflectionClass($this->originFullClassname);
        }catch (\Exception $e){
            throw new Exception($this->originFullClassname .' not found');
        }
        if(strpos($classInfo->getFileName(),app()->getRootPath())===false){
            throw new Exception($this->originFullClassname .' must in thinkphp project');
        }
        // 动态类完整命名空间 不包含类名
        $this->originNamespace = $classInfo->getNamespaceName();
        $this->originClassName = pathinfo($classInfo->getFileName(),PATHINFO_FILENAME);
        if($dirLevel < 0){
            $this->facadeNamespace = app()->getNamespace().'\common\facade';
            $this->facadeDir = app()->getAppPath().DIRECTORY_SEPARATOR.'common'.DIRECTORY_SEPARATOR.'facade';
            $this->isInApplication = true;
        }else{
            $tempNamespace = explode('\\',$this->originNamespace);
            for ($i=0;$i<$dirLevel;$i++){
                array_pop($tempNamespace);
            }
            $tempDir = explode(DIRECTORY_SEPARATOR,pathinfo($classInfo->getFileName(),PATHINFO_DIRNAME));
            for ($i=0;$i<$dirLevel;$i++){
                array_pop($tempDir);
            }
            $this->facadeNamespace = implode('\\',$tempNamespace).'\facade';
            $this->facadeDir = implode(DIRECTORY_SEPARATOR,$tempDir).DIRECTORY_SEPARATOR.'facade';
            $this->isInApplication = false;
        }
        return $this;
    }

    protected function buildClass()
    {
        $stub = file_get_contents($this->getStub());

        $namespace = $this->facadeNamespace;
        $className = $this->originClassName;
        if(is_array($this->importClass)&&count($this->importClass)>0){
            $importClass = implode(';'.PHP_EOL,$this->getUseImportClass());
            $importClass .=';'.PHP_EOL.PHP_EOL;
        }else{
            $importClass = '';
        }

        $annotation = '/**'.PHP_EOL;
        $annotation .=' * @see \\'.$this->originFullClassname.PHP_EOL;
        $annotation .=' * @mixin \\'.$this->originFullClassname.PHP_EOL;
        $annotation .=implode(PHP_EOL,$this->annotation);
        $annotation .=PHP_EOL;
        $annotation .=' */';

        $class = '\\'.$this->originFullClassname.'::class';
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
        if(class_exists($this->originFullClassname)===false){
            throw new Exception($this->originFullClassname.' not exist!');
        }
        try{
            $reflectionClass = new \ReflectionClass($this->originFullClassname);
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
            return '\\'.$this->originFullClassname;
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
                //此处太难处理，暂时只支持空数组
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