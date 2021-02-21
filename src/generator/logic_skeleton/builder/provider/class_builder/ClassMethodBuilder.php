<?php


namespace huikedev\huike_generator\generator\logic_skeleton\builder\provider\class_builder;


use huikedev\huike_generator\generator\logic_skeleton\builder\constract\CodeBuilder;
use huikedev\huike_generator\generator\logic_skeleton\builder\support\ClassNewMethod;
use think\Collection;

class ClassMethodBuilder implements CodeBuilder
{
    /**
     * @var Collection
     */
    protected $methods;
    protected $addMethods = [];
    protected $fullClassName;


    public function __construct(string $fullClassName)
    {
        $this->fullClassName = $fullClassName;
        $this->methods = new Collection();
    }

    public function addMethod(ClassNewMethod $method)
    {
        $this->addMethods[] =$method;
    }

    public function all():Collection
    {
        return $this->methods;
    }

    public function add($value):void
    {
        $this->methods->push($value);
    }

    public function toSource(): string
    {
        if(count($this->addMethods) === 0){
            return '';
        }
        $source = "\n";
        foreach ($this->addMethods as $method){
            $source .=$this->parseAddMethodsToSource($method);
        }
        return $source;
    }

    protected function parseAddMethodsToSource(ClassNewMethod $method): string
    {
        $source ='';
        if($method->getIsHiddenDoc() === false){
            $source = $this->parsePhpDoc($method->getPhpdoc(),$method->getReturnType());
        }
        $source .="\t";
        switch ($method->getAccess()){
            case T_PRIVATE:
                $source .='private ';
                break;
            case T_PROTECTED:
                $source .='protected ';
                break;
            default:
                $source .='public ';
        }
        if($method->getIsStatic()){
            $source .="static ";
        }
        $source .="function ".$method->getName()."()";
        if($method->getReturnType() === 'mixed'){
            $source .="\n";
        }else{
            $source .=":".$method->getReturnType()."\n";
        }
        $source .="\t{\n";
        if(count($method->getBody()) > 0){
            foreach ($method->getBody() as $codeLine){
                $source .="\t\t".$codeLine."\n";
            }
        }else{
            $source.="\t\t\n";
        }
        $source.="\t}\n\n";
        return $source;
    }


    protected function parsePhpDoc(array $phpdoc,string $returnTypeString):string
    {
        if(isset($phpdoc['desc']) === false){
            $phpdoc['desc'] = ' ';
        }
        if(isset($phpdoc['return'])===false){
            $phpdoc['return'] =  $returnTypeString;
        }
        ksort($phpdoc);
        $doc = "\t /**\n";
        foreach ($phpdoc as $key => $value){
            $doc .="\t * @".$key." ".$value."\n";
        }
        $doc .="\t */\n";
        return $doc;
    }

    public function toArray(): array
    {
        // TODO: Implement toArray() method.
    }


    public function remove(array $filter): void
    {
        // TODO: Implement remove() method.
    }

    public function find($filter)
    {
        // TODO: Implement find() method.
    }

    public function has($filter): bool
    {
        // TODO: Implement has() method.
    }




}