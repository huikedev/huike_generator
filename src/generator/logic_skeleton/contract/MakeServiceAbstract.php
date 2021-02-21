<?php


namespace huikedev\huike_generator\generator\logic_skeleton\contract;


use huikedev\huike_base\utils\UtilsTools;

abstract class MakeServiceAbstract extends MakeClassAbstract
{
    // 类名 不包含前缀
    protected $className;
    // 路径 不包含文件名
    protected $path;
    // 命名空间
    protected $namespace;

    protected $isCreated = false;
    protected $result = [
        'action'=>'',
        'msg'=>'',
        'status'=>''
    ];
    /**
     * @var \ReflectionClass
     */
    protected $reflectClass;

    public function setResult(string $action,string $msg,$status)
    {
        $this->result = [
            'action'=>$action,
            'msg'=>$msg,
            'status'=>$status
        ];
    }

    public function getResult():array
    {
        return $this->result;
    }

    public function getFullClassName():string
    {
        return UtilsTools::replaceNamespace($this->namespace.'\\'.$this->className);
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function getFileName()
    {
        return UtilsTools::replaceSeparator(app()->getRootPath().$this->path.DIRECTORY_SEPARATOR.$this->className.'.php');
    }

    // todo: fixed @php8.0 union type
    public function getReturnType(string $method):array
    {
        $returnType = [
            'string'=>':mixed',
            'import'=>null
        ];
        $reflectReturnType = $this->reflectClass->getMethod($method)->getReturnType();
        if(is_null($reflectReturnType)){
            return $returnType;
        }
        if($reflectReturnType->isBuiltin()){
            $returnType = [
                'string'=> $reflectReturnType->getName(),
                'import'=>null
            ];
            return $returnType;
        }
        $returnType = [
            'string'=>':'.pathinfo($reflectReturnType->getName(),PATHINFO_BASENAME),
            'import'=>$reflectReturnType->getName()
        ];
        return $returnType;
    }
}