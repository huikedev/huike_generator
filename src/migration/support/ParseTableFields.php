<?php


namespace huikedev\huike_generator\migration\support;


use think\Exception;
use think\facade\Db;
use think\helper\Str;
use think\model\Collection;

class ParseTableFields
{
    /**
     * @var Collection
     */
    protected $tableFields;
    /**
     * @var Collection
     */
    protected $originFieldsConfig;
    protected $modelId;
    public function handle(string $tableName,int $modelId=0)
    {
        try {
            $fields = Db::table($tableName)->getFields();
            $this->modelId = $modelId;
            $this->tableFields = new Collection();
            $originFields = Db::query('SHOW FULL COLUMNS FROM '.$tableName);
            $this->originFieldsConfig = new Collection($originFields);
            if(count($fields) === 0){
                return $this;
            }
            foreach ($fields as $key => $field){
                $this->tableFields->push($this->parseField($key,$field));
            }
            return $this;
        }catch (\Throwable $e){
            throw new Exception($e->getMessage());
        }
    }

    public function getTableFields(): Collection
    {
        return $this->tableFields;
    }

    public function getMigrationFields():Collection
    {
        $tableFields = $this->tableFields;
        foreach ($tableFields as $tableField){
            $tableField->field_type = MysqlFieldType::TYPE[$tableField->field_type] ?? 'string';
        }
        return $tableFields;
    }

    protected function parseField(string $key,array $fieldConfig):HuikeModelFields
    {
        $tableFiledConfig = new HuikeModelFields();
        $tableFiledConfig->model_id = $this->modelId;
        $tableFiledConfig->field_name = $key;
        $tableFiledConfig->field_type = $this->getFieldType($fieldConfig);
        $tableFiledConfig->field_length = $this->getFieldLength($fieldConfig);
        $tableFiledConfig->field_scale = $this->getFieldLength($fieldConfig,true);
        $tableFiledConfig->field_index = $this->getFieldIndex($fieldConfig);
        $tableFiledConfig->default_value = $this->getDefaultValue($fieldConfig);
        $tableFiledConfig->field_options = $this->getFieldOptions($fieldConfig);
        $tableFiledConfig->field_remark = $fieldConfig['comment'] ?? null;
        return $tableFiledConfig;
    }

    /*
     * 获取字段类型
     */
    protected function getFieldType(array $fieldConfig):string
    {
        [$type] = explode(' ',$fieldConfig['type']);
        preg_match('/([a-z]+)/',$type,$match);

        return $match[0] ?? 'undefined';
    }

    /*
     * 获取字段长度及小数点位数
     */
    protected function getFieldLength(array $fieldConfig,bool $isScale = false):int
    {
        preg_match('/\((.*)\)/',$fieldConfig['type'],$match);
        if(isset($match[1]) === false){
            return -1;
        }
        if(Str::contains($match[1],',')){
            [$length,$scale] = explode(',',$match[1]);
        }else{
            $length = $match[1];
            $scale = 0;
        }
        if($isScale){
            return is_numeric($scale) ? $scale : 0;
        }
        return is_numeric($length) ? $length : -1;
    }

    protected function getFieldIndex(array $fieldConfig):string
    {
        $index = $this->originFieldsConfig->where('Field','=',$fieldConfig['name'])->first();
        if(is_null($index)||isset($index['Key']) === false){
            return 'none';
        }
        switch ($index['Key']){
            case 'PRI':
                return 'primary';
            case 'UNI':
                return 'unique';
            case 'MUL':
                return 'normal';
            default:
                return 'none';
        }
    }

    protected function getDefaultValue(array $fieldConfig):array
    {
        $defaultValue = ['type'=>'undefined','value'=>'undefined'];
        if(isset($fieldConfig['default'])===false || is_null($fieldConfig['default'])){
            return $defaultValue;
        }
        if($fieldConfig['default']===''){
            $defaultValue['type'] = 'string';
            $defaultValue['value'] = 'EMPTY STRING';
            return $defaultValue;
        }
        if(is_numeric($fieldConfig['default'])){
            if(Str::contains($fieldConfig['default'],'.')===false){
                $defaultValue['type'] = 'integer';
                $defaultValue['value'] = intval($fieldConfig['default']);
            }else{
                $defaultValue['type'] = 'double';
                $defaultValue['value'] = $fieldConfig['default'];
            }
            return $defaultValue;
        }
        $defaultValue['type'] = gettype($fieldConfig['default']);
        $defaultValue['value'] = $fieldConfig['default'];
        return $defaultValue;
    }

    protected function getFieldOptions(array $fieldConfig):array
    {
        $options = [];
        if(isset($fieldConfig['primary']) && $fieldConfig['primary'] === true){
            $options[] = 'primary';
        }
        if(isset($fieldConfig['type']) && Str::contains($fieldConfig['type'],'unsigned')){
            $options[] = 'unsigned';
        }
        if(isset($fieldConfig['notnull']) && $fieldConfig['notnull'] === false){
            $options[] = 'nullable';
        }
        return $options;
    }
}