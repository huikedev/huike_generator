<?php


namespace huikedev\huike_generator\generator\logic_skeleton\contract;

use huikedev\huike_base\utils\UtilsTools;
use think\console\command\Make;
use think\console\Output;
use think\Exception;
use think\facade\Config;

/**
 * Desc
 * Class MakeClassAbstract
 * @package huikedev\huike_generator\generator\logic_skeleton\app_controller\contract
 */
abstract class MakeClassAbstract
{
    /**
     * @var Output | null
     */
    protected $output;
    protected $importClass = [];
    protected $config;
    protected $importMatchKey =[
        'start'=>'// HuikeImportStart',
        'startRegex'=>'\/\/ HuikeImportStart',
        'end'=>'// HuikeImportEnd',
        'endRegex'=>'\/\/ HuikeImportEnd',
    ];
    public function __construct($output=null)
    {
        if($output instanceof Output){
            $this->output = $output;
        }
        $this->config = Config::get('huike');
    }

    abstract protected function getStub();
    protected function getBaseStub()
    {
        $dir = __DIR__;
        $dir = str_replace('\\',DIRECTORY_SEPARATOR,$dir);
        $relateDir = explode(DIRECTORY_SEPARATOR,$dir);
        array_pop($relateDir);
        return implode(DIRECTORY_SEPARATOR,$relateDir).DIRECTORY_SEPARATOR.'stub';
    }

    protected function getLockRecordPath()
    {
        $dir = __DIR__;
        $dir = str_replace('\\',DIRECTORY_SEPARATOR,$dir);
        $relateDir = explode(DIRECTORY_SEPARATOR,$dir);
        array_pop($relateDir);
        return implode(DIRECTORY_SEPARATOR,$relateDir).DIRECTORY_SEPARATOR.'lock_record';
    }

    protected function replaceSeparator(string $path)
    {
        $path =  preg_replace('/\\\\+/',DIRECTORY_SEPARATOR,$path);
        return  preg_replace('/\/+/',DIRECTORY_SEPARATOR,$path);
    }

    protected function replaceNamespace(string $namespace)
    {

        $namespace = preg_replace('/\/+/','\\',$namespace);
        $namespace = preg_replace('/\\\\+/','\\',$namespace);
        if(strpos($namespace,'\\')===0){
            $namespace = ltrim($namespace,'\\');
        }
        return $namespace;
    }

    protected function replaceNameSpaceToPath(string $namespace)
    {
        return str_replace('\\',DIRECTORY_SEPARATOR,$namespace);
    }

    protected function replacePathToNamespace(string $path)
    {
        return str_replace(DIRECTORY_SEPARATOR,'\\',$path);
    }

    protected function success(string $msg)
    {
        return $this->writeln($msg,'success');
    }

    protected function writeln(string $msg,$type='info')
    {
        if($this->output instanceof Output === false){
            if($type!=='success'){
                throw new Exception($msg);
            }
            return $this;
        }
        switch (true){
            case $type==='error':
                $this->output->error($msg);
                break;
            case $type==='comment':
                $this->output->comment($msg);
                break;
            case $type==='highlight':
                $this->output->highlight($msg);
                break;
            case $type==='warning':
                $this->output->warning($msg);
                break;
            default:
                $this->output->info($msg);
        }
        return $this;
    }

    protected function getClassInfo($fullNamespace)
    {
        if(class_exists($fullNamespace)===false){
            throw new Exception('class '.$fullNamespace.' not exist!');
        }
        return pathinfo($fullNamespace);
    }
    protected function parseReturnType($returnType)
    {

        if(class_exists($returnType)){
            $this->importClass[] = $returnType;
            if(strpos($returnType,'\\')!==0){
                $returnType = '\\'.$returnType;
            }
            $returnTypeArray = explode('\\',$returnType);
            $className = array_pop($returnTypeArray);
            $returnType = ':'.$className;
        }else{
            $returnType = ':'.$returnType;
        }
        return $returnType;
    }

    protected function getImport():string
    {
        $importClasses = $this->getUseImportClass();
        $importString = $this->importMatchKey['start']."\n";
        foreach ($importClasses as $importClass){
            $importString .=$importClass.";\n";
        }
        $importString .= $this->importMatchKey['end'];
        return $importString;
    }

    protected function parseImport(string $content): self
    {
        $regPattern = '/'.$this->importMatchKey['startRegex']."(.*)".$this->importMatchKey['endRegex'].'/Us';
        preg_match($regPattern,$content,$matches);
        if(isset($matches[1])===false){
            return $this;
        }
        $imports = explode(';',$matches[1]);
        foreach ($imports as $import){
            preg_match('/.*use\s+(.*)/si',$import,$objectMatches);
            if(isset($objectMatches[1])){
                $this->importClass[] = trim($objectMatches[1]);
            }
        }
        return $this;
    }

    protected function getActionSimplePhpDoc(string $actionName,bool $onlyText=false):string
    {
        $actionConfig = $this->config['controller']['action'][$actionName];
        $doc = '';
        if(isset($actionConfig['remark']) && empty($actionConfig['remark']) === false ){
            if($onlyText){
                $doc = $actionConfig['remark'];
            }else{
                $doc .="\n\t";
                $doc .='/*'.PHP_EOL;
                $doc .="\t";
                $doc .=' * '.$actionConfig['remark'].PHP_EOL;
                $doc .="\t";
                $doc .=' */'.PHP_EOL;
            }
        }
        return $doc;
    }

    protected function getUseImportClass(): array
    {
        $importClass = [];
        foreach ($this->importClass as $import){
            $importClass[] = UtilsTools::replaceNamespace($import);
        }
        if(is_array($importClass)===false || count($importClass)===0){
            return [];
        }

        $importClass =  array_unique($this->importClass);
        $useImport = [];
        foreach ($importClass as $object){
            $useImport[] = 'use '.$object;
        }
        return $useImport;
    }
}