<?php


namespace huikedev\huike_generator\generator\logic_skeleton\execute\model;


use huikedev\huike_base\base\GetterSetter;
use huikedev\huike_base\utils\UtilsTools;
use huikedev\huike_generator\generator\logic_skeleton\contract\MakeClassAbstract;
use think\Exception;

/**
 * Class MakeModel
 * @package huikedev\huike_generator\generator\logic_skeleton\execute\model
 * @method MakeModel setModelFullName(string $fullName)
 * @method MakeModel setExtendModel(string $model)
 * @method MakeModel setAddonFields(array $addonFields)
 * @method MakeModel setJsonFields(array $jsonFields)
 * @method MakeModel setIsJsonAssoc(bool $isJsonAssoc)
 * @method MakeModel setModelConnection(string $modelConnection)
 * @method MakeModel setModelPk(string $modelPk)
 * @method MakeModel setModelTable(string $modelTable)
 * @method MakeModel setModelFile(string $modelFile)
 */
class MakeModel extends MakeClassAbstract
{
    use GetterSetter;
    protected $defaultNamespace = 'huike\common\model';
    protected $importClass;
    protected $extendModel;
    protected $modelFullName;
    protected $modelName;
    protected $modelProperty;
    protected $modelFunctions;
    protected $namespacePrefix;
    protected $realExtend;
    protected $modelPath;
    protected $addonFields;
    protected $jsonFields;
    protected $isJsonAssoc;
    protected $modelConnection;
    protected $modelPk;
    protected $modelTable;
    protected $modelFile;
    public function getModelPath()
    {
        return $this->modelPath;
    }
    protected function getStub()
    {
        return $this->getBaseStub().DIRECTORY_SEPARATOR.'Model.stub';
    }

    public function handle()
    {
        $this->modelFile = UtilsTools::replaceSeparator(app()->getRootPath().str_replace(app()->getRootPath(),'',UtilsTools::replaceSeparator($this->modelFile)));
        if(file_exists($this->modelFile)){
            throw new Exception('MakeModel:'.$this->modelFullName.'已存在');
        }
        $this->parseRealExtend();
        $this->parseModelProperty();
        $pathInfo = pathinfo($this->modelFile);
        $namespaceInfo = pathinfo($this->modelFullName);
        $this->defaultNamespace = $namespaceInfo['dirname'];
        $this->modelName = $pathInfo['filename'];
        if (!is_dir($pathInfo['dirname'])) {
            mkdir($pathInfo['dirname'], 0755, true);
        }
        file_put_contents($this->modelFile, self::buildClass());
        return $this;
    }

    protected function buildClass()
    {
        $stub = file_get_contents($this->getStub());
        if(empty($this->namespacePrefix)){
            $namespace = $this->defaultNamespace;
        }else{
            $namespace = $this->defaultNamespace.'\\'.$this->namespacePrefix;
        }

        if(is_array($this->importClass)&&count($this->importClass)>0){
            $importClass = implode(';'.PHP_EOL,$this->getUseImportClass());
            $importClass .=';'.PHP_EOL.PHP_EOL;
        }else{
            $importClass = '';
        }
        if(is_array($this->modelProperty)&&count($this->modelProperty)>0){
            $modelProperty = "\t";
            $modelProperty .= implode(PHP_EOL."\t",$this->modelProperty);
        }else{
            $modelProperty = '';
        }

        return str_replace(['{%namespace%}','{%importClass%}','{%className%}', '{%baseModel%}',  '{%modelProperty%}','{%modelFunctions%}'], [
            $namespace,
            $importClass,
            $this->modelName,
            $this->realExtend,
            $modelProperty,
            ''
        ], $stub);

    }

    // 模型基类
    protected function parseRealExtend()
    {
        $extendArray = explode('\\',$this->extendModel);
        $this->realExtend = array_pop($extendArray);
        $this->importClass[] = $this->extendModel;
    }

    protected function parseModelProperty()
    {
        $this->isSoftDelete();
        $this->parseModelPk();
        $this->parseModelTable();
        $this->parseModelConnection();
        $this->isTimestamp();
        $this->parseJsonProperty();
    }

    protected function isSoftDelete()
    {
        if(in_array('delete_time',$this->addonFields)){
            $this->modelProperty[] = 'use SoftDelete;';
            $this->modelProperty[] = 'protected $defaultSoftDelete = 0;';
            $this->importClass[] = 'think\model\concern\SoftDelete';
        }
    }

    protected function isTimestamp()
    {
        if(in_array('update_time',$this->addonFields)===false && in_array('create_time',$this->addonFields) ===false ){
            $this->modelProperty[] = 'protected $autoWriteTimestamp = false;';
        }else{
            $this->modelProperty[] = 'protected $autoWriteTimestamp = true;';
            if(in_array('update_time',$this->addonFields)===false){
                $this->modelProperty[] = 'protected $updateTime = false;';
            }
            if(in_array('create_time',$this->addonFields)===false){
                $this->modelProperty[] = 'protected $createTime = false;';
            }
        }
    }

    protected function parseJsonProperty()
    {
        if($this->isJsonAssoc){
            $this->modelProperty[] = 'protected $jsonAssoc=true;';
        }
        if(is_array($this->jsonFields) && count($this->jsonFields) > 0){
            $code = 'protected $json=[';
            foreach ($this->jsonFields as $jsonField){
                $code .= '\''.$jsonField.'\',';
            }
            $code .='];';
            $this->modelProperty[] = $code;
        }
    }

    protected function parseModelPk()
    {
        if(empty($this->modelPk)===false && strtolower($this->modelPk)!=='id'){
            $this->modelProperty[] = 'protected $pk=\''.$this->modelPk.'\';';
        }
    }

    protected function parseModelTable()
    {
        if(empty($this->modelTable)===false){
            $this->modelProperty[] = 'protected $table=\''.$this->modelTable.'\';';
        }
    }

    protected function parseModelConnection()
    {
        if(empty($this->modelConnection)===false){
            $this->modelProperty[] = 'protected $connection=\''.$this->modelConnection.'\';';
        }
    }
}