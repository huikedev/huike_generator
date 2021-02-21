<?php


namespace huikedev\huike_generator\migration\contract;


use huikedev\huike_base\utils\UtilsTools;
use think\Exception;
use think\Model;

abstract class TableActionAbstract
{
    protected $table;
    protected $path;

    public function setPath(string $path):self
    {
        $this->path = UtilsTools::replaceSeparator($path);
        return $this;
    }

    protected function getTable(string $modelOrTable)
    {
        if (class_exists($modelOrTable)){
            $model = new $modelOrTable();
            if($model instanceof Model){
                $this->table = $model->getTable();
            }else{
                throw new Exception($modelOrTable.' is not instance of think\Model');
            }
        }else{
            $this->table = $modelOrTable;
        }
    }
}