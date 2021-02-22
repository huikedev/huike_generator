<?php


namespace huikedev\huike_generator\migration;

use huikedev\huike_generator\migration\support\HuikeModelFields;
use think\Collection;
use think\helper\Str;

class FillMigration
{
    protected $migrationCode = [];
    protected $indexFields = [];
    protected $jsonFields = [];
    protected $collection = 'utf8mb4_general_ci';
    protected $engine='InnoDB';
    protected $phpdoc;

    public function setCollection(string $collection): FillMigration
    {
        $this->collection = $collection;
        return $this;
    }

    public function setEngine(string $engine): FillMigration
    {
        $this->engine = $engine;
        return $this;
    }

    public function setPhpdoc(string $phpdoc): FillMigration
    {
        $this->phpdoc = $phpdoc;
        return $this;
    }

    public function handle(string $modelName,string $migrationPath,Collection $modelFieldsCollection,?string $remark=null)
    {
        $modelName = Str::studly($modelName);
        $migrationFile = file_get_contents($migrationPath);
        $this->getTableCode($modelName,$remark);
        $this->getFieldsCode($modelFieldsCollection);
        $migrationFile .= implode(PHP_EOL,$this->migrationCode);
        $migrationFile .= "\t}\n}\n";
        file_put_contents($migrationPath,$migrationFile);
        return $this;
    }


    // $table = $this->table('huike_facade_list')->setCollation('utf8mb4_general_ci')->setComment('门面列表');
    protected function getTableCode(string $modelName,?string $remark=null)
    {
        // $this->table('huike_facade_list')
        $tableCode = '';
        if(is_null($this->phpdoc) === false){
            $tableCode .="\t//".$this->phpdoc."\n";
        }
        $tableCode .= "\t\t\$table = \$this->table('";
        $tableCode.=Str::snake($modelName).'\')';
        $tableCode.="->setCollation('".$this->collection."')";
        $tableCode .="->setEngine('".$this->engine."')";
        if(empty($remark)===false){
            $tableCode.="->setComment('".$remark."');";
        }else{
            $tableCode.=';';
        }
        $this->migrationCode[] = $tableCode;
    }

    protected function getFieldsCode(Collection $modelCollection)
    {
        foreach ($modelCollection as $modelField){
            if($modelField->field_index ==='primary'){
                $this->migrationCode[] = $this->setPrimary($modelField->field_name);
            }else{
                $this->migrationCode[] = $this->getFieldCode($modelField);
            }
        }
        $this->getIndexFields();
        $this->migrationCode[] = "\t\t\t".'->create();'.PHP_EOL;

    }
    // 主键ID
    protected function setPrimary(string $primaryName='id'): string
    {
        return "\t\t".'$table->setId(\''.$primaryName.'\')';
    }
    // 数据表字段
    protected function getFieldCode(HuikeModelFields $modelField)
    {
        if(strtolower($modelField->field_type) === 'json'){
            $this->jsonFields[] = $modelField->field_name;
        }
        $code = "\t\t\t".'->addColumn(';
        // 字段长度
        $code .= $this->specifyLength($modelField);

        // 索引
        if($modelField->field_index!=='none' ){
            $this->indexFields[$modelField->field_name] = $modelField->field_index;
        }
        // 默认值
        if(in_array($modelField->field_type,['json','text','mediumText','longText']) === false && $modelField->default_value['type']!=='undefined'){
            $code .=$this->getDefaultValue($modelField->default_value['value']);
        }
        // 注释
        if(is_null($modelField->field_remark) === false){
            $code .='->setComment(\''.$modelField->field_remark.'\')';
        }
        // 可选属性
        if(is_countable($modelField->field_options) && count($modelField->field_options) > 0){
            $code .=$this->getOptions($modelField->field_options);
        }
        $code .=')';
        return $code;

    }
    // 可指定长度的字段
    protected function specifyLength(HuikeModelFields $modelField)
    {
        switch (true){
            case $modelField->field_type ==='string' | $modelField->field_type==='char':
                return 'Column::'.$modelField->field_type.'(\''.$modelField->field_name.'\','.$modelField->field_length.')';
            case $modelField->field_type ==='decimal':
                return 'Column::'.$modelField->field_type.'(\''.$modelField->field_name.'\','.$modelField->field_length.','.$modelField->field_scale.')';
            default:
                return 'Column::'.$modelField->field_type.'(\''.$modelField->field_name.'\')';
        }
    }

    // 默认值
    protected function getDefaultValue($defaultValue)
    {
        if(trim($defaultValue)==='EMPTY STRING'){
            return '->setDefault(\'\')';
        }
        if(is_numeric($defaultValue)){
            return '->setDefault('.$defaultValue.')';
        }
        if(is_string($defaultValue)){
            return '->setDefault(\''.$defaultValue.'\')';
        }
        return '->setDefault('.$defaultValue.')';
    }

    // 附加选项 可为空 非负
    protected function getOptions(array $options)
    {
        $code = '';
        if(in_array('nullable',$options)){
            $code .='->setNullable()';
        }
        if(in_array('unsigned',$options)){
            $code .='->setUnsigned()';
        }
        return $code;
    }

    protected function getIndexFields()
    {
        foreach ($this->indexFields as $field => $indexType){
            switch (true){
                case $indexType==='none':
                    break;
                case $indexType==='unique':
                    $this->migrationCode[] = "\t\t\t".'->addIndex([\''.$field.'\'],[\'unique\'=>true])';
                    break;
                case $indexType==='fulltext':
                    $this->migrationCode[] = "\t\t\t".'->addIndex([\''.$field.'\'],[\'fulltext\'=>true])';
                    break;
                default:
                    $this->migrationCode[] = "\t\t\t".'->addIndex([\''.$field.'\'])';
            }
        }
        return $this;
    }
}