<?php


namespace huikedev\huike_generator\generator\logic_skeleton\builder\support;


use huikedev\huike_base\app_const\ServiceReturnType;
use huikedev\huike_generator\generator\logic_skeleton\builder\ClassBuilder;
use think\helper\Str;

class ClassNewMethod
{
    protected $name;
    protected $access = T_PUBLIC;
    protected $isStatic = false;
    protected $body = [];
    protected $returnType = 'mixed';
    protected $phpdoc = [];
    protected $fullClassName;
    protected $hiddenDoc = false;

    public function __construct(string $fullClassName)
    {
        $this->fullClassName = $fullClassName;
    }

    public function getName():string
    {
        return $this->name;
    }

    public function getAccess():int
    {
        return $this->access;
    }

    public function getIsHiddenDoc():bool
    {
        return $this->hiddenDoc;
    }

    public function getIsStatic():bool
    {
        return $this->isStatic;
    }

    public function getBody():array
    {
        return $this->body;
    }

    public function getReturnType():string
    {
        return $this->returnType;
    }

    public function getPhpdoc():array
    {
        return $this->phpdoc;
    }

    public function setName(string $value)
    {
        $this->name = $value;
    }

    public function setAccess(int $access)
    {
        $this->access = $access;
    }

    public function setStatic(bool $static=true)
    {
        $this->isStatic = $static;
    }

    public function setBody(array $codeLines)
    {
        $this->body = $codeLines;
    }

    public function setReturnType($value)
    {
        if(class_exists($value)){
            ClassBuilder::addImport($this->fullClassName,$value);
            $this->returnType = pathinfo($value,PATHINFO_BASENAME);
        }else{
            if(isset(ServiceReturnType::TP_DEFAULT[$value])){
                ClassBuilder::addImport($this->fullClassName,ServiceReturnType::TP_DEFAULT[$value]);
                $this->returnType = Str::studly($value);
            }else{
                $this->returnType = $value;
            }

        }
    }

    public function addPhpdoc($name,$value)
    {
        if(class_exists($value)){
            ClassBuilder::addImport($this->fullClassName,$value);
            $this->phpdoc[$name] = pathinfo($value,PATHINFO_BASENAME);
        }else{
            $this->phpdoc[$name] = $value;
        }
    }

    public function setHiddenDoc(bool $isHidden)
    {
        $this->hiddenDoc = $isHidden;
    }
}