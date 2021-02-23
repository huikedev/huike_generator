<?php


namespace huikedev\huike_generator\generator\logic_skeleton\builder;


use huikedev\huike_base\utils\UtilsTools;
use huikedev\huike_generator\generator\logic_skeleton\builder\provider\class_builder\ClassConstantBuilder;
use huikedev\huike_generator\generator\logic_skeleton\builder\provider\class_builder\ClassMethodBuilder;
use huikedev\huike_generator\generator\logic_skeleton\builder\provider\class_builder\ClassPropertyBuilder;
use huikedev\huike_generator\generator\logic_skeleton\builder\support\ClassNewMethod;
use ReflectionClass;
use think\Exception;

class ClassBuilder
{
    // 静态属性
    // use 声明使用静态 方便其他逻辑追加结构 ['fullClassName'=>object[]]
    protected static $imports;
    // 结构['fullClassName'=>int[]]
    protected static $unsetLines = [];
    // 结构['fullClassName'=>string[]]
    protected static $sourceArray;
    // 结构[fullClassName=>['method'=>methodBodyLine[] ][] ][]
    protected static $originMethod = [];
    /**
     * @var ReflectionClass
     */
    protected $reflectClass;
    protected $fullClassName;
    protected $file;
    protected $methodBuilder;
    protected $constantBuilder;
    protected $propertyBuilder;
    protected $tokens;
    protected $originContent;
    protected $newSource;
    protected $newFilename;
    // 类的基础属性
    protected $isExist = false;
    protected $classType = 'common';
    protected $extendClass;
    protected $implements = [];

    public function __construct(string $fullClassName,$fullFilename = null)
    {
        if(is_null($fullFilename)){
            $this->file = UtilsTools::replaceSeparator(app()->getRootPath().$fullClassName.'.php');
        }else{
            $fullFilename = UtilsTools::replaceSeparator($fullFilename);
            $fullFilename = str_replace(app()->getRootPath(),'',$fullFilename);
            $this->file = UtilsTools::replaceSeparator(app()->getRootPath().$fullFilename);
        }

        if(class_exists($fullClassName)){
            $this->reflectClass = new ReflectionClass($fullClassName);
        }
        // 初始化$fullClassName use声明
        $this->fullClassName = $fullClassName;
        self::$imports[$this->fullClassName] = [];
        self::$unsetLines[$this->fullClassName] = [];
        $this->methodBuilder = new ClassMethodBuilder($this->fullClassName);
        $this->propertyBuilder = new ClassPropertyBuilder($this->fullClassName);
        $this->constantBuilder = new ClassConstantBuilder($this->fullClassName);
        if(file_exists($this->file)){
            $this->isExist = true;
            self::$sourceArray[$this->fullClassName] = file($this->file);
            $this->originContent = self::$sourceArray[$this->fullClassName];
            $this->tokens = token_get_all(implode(self::$sourceArray[$this->fullClassName]));
            $this->parseClassSource();
        }
    }

    public function getFullClassName():string
    {
        return $this->fullClassName;
    }

    public function setExtendClass(string $class)
    {
        $this->extendClass = class_basename($class);
        self::addImport($this->fullClassName,$class);
    }

    public function setClassType(int $classType)
    {
        if($classType === T_ABSTRACT){
            $this->classType = 'abstract';
        }
        if($classType === T_TRAIT){
            $this->classType = 'trait';
        }
        if($classType === T_INTERFACE){
            $this->classType = 'interface';
        }
    }

    public function setImplements(string $class)
    {
        $this->implements[] = class_basename($class);
        self::addImport($this->fullClassName,$class);
    }

    public function addProperty()
    {
        // todo
        return $this;
    }
    public function addConstant()
    {
        // todo
        return $this;
    }

    public function addMethod(ClassNewMethod $method)
    {
        if($this->reflectClass instanceof ReflectionClass && $this->reflectClass->hasMethod($method->getName())){
            throw new Exception($method->getName().' 已存在于'.$this->fullClassName.'，无法覆盖');
        }
        $this->methodBuilder->addMethod($method);
        return $this;
    }
    protected function getNewSource()
    {
        return $this->newSource;
    }
    public function build(): ClassBuilder
    {
        if($this->isExist){
            $originSource = implode(self::$sourceArray[$this->fullClassName]);
            // 去除class最后的 }
            $originSource = mb_substr($originSource,0,mb_strlen($originSource)-1);
            $this->newSource = $this->editClass($originSource);
        }else{
            $this->newSource = $this->createClass();
        }
        $this->newSource .=$this->methodBuilder->toSource();
        $this->newSource .='}';
        return $this;
    }

    public function toFile($overwrite=false): ClassBuilder
    {
        $pathInfo = pathinfo($this->file);
        if(is_dir($pathInfo['dirname']) === false){
            mkdir($pathInfo['dirname'],0755,true);
        }
        if($this->isExist && $overwrite===false){
            file_put_contents($pathInfo['dirname'].DIRECTORY_SEPARATOR.$pathInfo['filename'].'.bak-'.time(),implode($this->originContent));
        }
        $this->newFilename = $pathInfo['dirname'].$pathInfo['basename'];
        file_put_contents($pathInfo['dirname'].DIRECTORY_SEPARATOR.$pathInfo['basename'],$this->newSource);
        if($this->isExist === false){
            $this->registerClass();
        }
        return $this;
    }

    protected function registerClass():void
    {
        include $this->file;
    }

    protected function editClass($originSource)
    {
        return  str_replace('{%importReplace%}',self::getImportSource($this->fullClassName),$originSource);
    }

    protected function createClass()
    {
        $stub = __DIR__ . DIRECTORY_SEPARATOR .'stub'.DIRECTORY_SEPARATOR.'EmptyClass.stub';
        $array = explode('\\',$this->fullClassName);
        $className = array_pop($array);
        $namespace = implode('\\',$array);
        $classType = $this->classType ==='common' ?'':$this->classType;
        $extend = empty($this->extendClass) ? '' : ' extends '.$this->extendClass;
        return str_replace([
            '{%namespace%}',
            '{%importReplace%}',
            '{%classAnnotation%}',
            '{%classType%}',
            '{%className%}',
            '{%classExtend%}',
            '{%classProperties%}'
        ], [
            $namespace,
            self::getImportSource($this->fullClassName),
            '',// Annotation 预留
            $classType,
            $className,
            $extend,
            ''//property 预留
        ], file_get_contents($stub));
    }


    public static function unsetSourceArray(string $key,int $index)
    {
        if(in_array($index,self::$unsetLines[$key]) === false){
            if(count(self::$unsetLines[$key]) === 0){
                self::$sourceArray[$key][$index] ='{%importReplace%}';
            }else{
                unset(self::$sourceArray[$key][$index]);
            }
            self::$unsetLines[$key][] = $index;
        }

    }

    public static function addImport(string $key,string $class)
    {
        self::$imports[$key][] = $class;
    }

    public static function getImportSource(string $key):string
    {
        if(count(self::$imports[$key]) === 0){
            return '';
        }
        $source = '';
        foreach (array_unique(self::$imports[$key]) as $importClass){
            $source .="use ".$importClass.";\n";
        }
        return $source;
    }


    public function getMethods()
    {
        return $this->methodBuilder->all()->toArray();
    }

    public function getConstant()
    {
        return $this->constantBuilder->all()->toArray();
    }

    public function getProperties()
    {
        return $this->propertyBuilder->all()->toArray();
    }


    public function handle()
    {
        return $this;
    }

    protected function parseClassSource()
    {
        // 原始use声明代码，以分号结尾为一行
        $importLines = [];
        $classType = 'common';

        for($index = 0;isset($this->tokens[$index]);$index++){
            // class 类型
            if($this->tokens[$index][0] === T_ABSTRACT){
                $this->classType = 'abstract';
            }
            if($this->tokens[$index][0] === T_TRAIT){
                $this->classType = 'trait';
            }
            if($this->tokens[$index][0] === T_INTERFACE){
                $this->classType = 'interface';
            }
            // use 声明整理
            $this->parseImports($index);
            //
            if($this->tokens[$index][0]===T_CONST){
                $this->constantBuilder->add($this->tokens[$index + 2][1]);
            }
            $this->parseMethodAndProperty($index);
        }
    }

    protected function parseImports(int $start)
    {
        $importLines = [];
        if(isset($this->tokens[$start][0]) && $this->tokens[$start][0]===T_USE){
            if(isset($this->tokens[$start][2])){
                $this->unsetSourceArray($this->fullClassName,$this->tokens[$start][2]-1);
            }
            $importString ='';
            for($useIndex=$start;$this->tokens[$useIndex]!==';';$useIndex++){

                if(isset($this->tokens[$useIndex][2])){
                    $this->unsetSourceArray($this->fullClassName,$this->tokens[$useIndex][2]-1);
                }
                $sr = $this->tokens[$useIndex][0] ?? $this->tokens[$useIndex];
                if(in_array($sr,[T_STRING,T_NS_SEPARATOR,'{','}',','])){
                    $importString .= $this->tokens[$useIndex][1] ?? $this->tokens[$useIndex];
                }
            }
            if(isset($this->tokens[$useIndex+1][2]) ){
                $this->unsetSourceArray($this->fullClassName,$this->tokens[$useIndex+1][2]-1);
            }
            if(empty($importString) === false){
                $importLines[] = $importString;
            }
        }
        foreach ($importLines as $importLine){
            preg_match('/(.*)\{(.*)\}/',$importLine,$matches);
            if(isset($matches[1]) && isset($matches[2])){
                foreach (explode(',',$matches[2]) as $object){
                    self::addImport($this->fullClassName,trim($matches[1]).trim($object));
                }
            }else{
                foreach (explode(',',$importLine) as $object){
                    self::addImport($this->fullClassName,trim($object));
                }
            }
        }

    }

    protected function parseMethodAndProperty(int $start)
    {
        $accessFilters = [T_PUBLIC,T_PROTECTED,T_PRIVATE];
        //public static $a
        //public static function a()
        //static public $a;
        //static public function a();
        if(isset($this->tokens[$start][0]) && in_array($this->tokens[$start][0],$accessFilters)){
            if($this->tokens[$start + 2][0] === T_FUNCTION){
                // static public function
                if(isset($tokens[$start - 2][0]) === T_STATIC){
                    $method['name'] = $this->tokens[$start + 4][1];
                    $method['static'] = true;
                    $method['access'] = $this->tokens[$start][1];
                }else{
                    // public function
                    $method['name'] = $this->tokens[$start + 4][1];
                    $method['static'] = false;
                    $method['access'] = $this->tokens[$start][0];
                }
                $this->methodBuilder->add($method);
            }
            // public $a
            if($this->tokens[$start + 2][0] === T_VARIABLE){
                $property['name'] = ltrim($this->tokens[$start + 2][1],'$');
                $property['static'] = false;
                $property['access'] = $this->tokens[$start][0];
                $this->propertyBuilder->add($property);
            }
            // public static $a || public static function name()
            if($this->tokens[$start + 2][0] === T_STATIC){
                if($this->tokens[$start + 4][0] ===T_VARIABLE){
                    $property['name'] = ltrim($this->tokens[$start + 4][1],'$');
                    $property['static'] = true;
                    $property['access'] = $this->tokens[$start][0];
                    $this->propertyBuilder->add($property);
                }else{
                    $method['name'] = $this->tokens[$start + 6][1];
                    $method['static'] = true;
                    $method['access'] = $this->tokens[$start][0];
                    $this->methodBuilder->add($method);
                }
            }
        }
    }

}