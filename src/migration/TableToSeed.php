<?php


namespace huikedev\huike_generator\migration;


use huikedev\huike_base\utils\UtilsTools;
use huikedev\huike_generator\generator\logic_skeleton\builder\CodeBuilder;
use huikedev\huike_generator\migration\contract\TableActionAbstract;
use think\facade\Db;
use think\helper\Str;

class TableToSeed extends TableActionAbstract
{
    protected $content;
    protected $fileEnd = "}\n}";
    protected $file;
    public function __construct()
    {
        $this->path = app()->getRootPath();
    }

    public function getFilename()
    {
        return $this->file;
    }


    public function handle(string $modelOrTable)
    {
        $this->getTable($modelOrTable);
        $this->getSeedContent();
        $this->content .="\n";
        $tableFields = Db::table($this->table)->getFields();
        $jsonFields = [];
        foreach ($tableFields as $tableField){
            if($tableField['type'] ==='json'){
                $jsonFields[] =$tableField['name'];
            }
        }
        Db::table($this->table)->chunk(100,function ($rows)use($jsonFields,$tableFields){
            foreach ($rows as $index => $row){
                foreach ($row as $field => $value){
                    if(in_array($field,$jsonFields)){
                        $row[$field] = json_decode($value);
                    }
                    if($tableFields[$field]['default']==$value){
                        unset($row[$field]);
                    }
                }
                $firstKey = is_null(array_key_first($row)) ? 'undefined' : array_key_first($row);
                $firstValue = is_null(array_key_first($row)) ? 'undefined' : $row[array_key_first($row)];
                $this->content .="\t\t".'// '.$firstKey.' = '.$firstValue."\n";
                $this->content .="\t\t".'think\facade\Db::table(\''.$this->table.'\')';
                if(count($jsonFields) > 0){
                    $this->content .='->json('.CodeBuilder::parseArray($jsonFields,0,false).')';
                }
                $this->content .='->insert(';
                $this->content .=CodeBuilder::parseArray($row,0,false);
                $this->content .=');'."\n";
            }
        });
        $this->content .=$this->fileEnd;
        file_put_contents($this->file,$this->content);
        return $this;
    }

    protected function getSeedContent(): TableToSeed
    {
        $this->file = (new MakeSeed())->setPath($this->path)->handle($this->table.'_'.Str::random(6,3));
        $this->content = file_get_contents($this->file);
        return $this;
    }
}