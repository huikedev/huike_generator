<?php


namespace huikedev\huike_generator\generator\logic_skeleton\execute\service;


use huikedev\dev_admin\common\model\huike\HuikeActions;
use huikedev\huike_base\exceptions\AppServiceException;
use huikedev\huike_base\log\HuikeLog;
use huikedev\huike_base\utils\UtilsTools;
use huikedev\huike_generator\generator\logic_skeleton\builder\ClassBuilder;
use huikedev\huike_generator\generator\logic_skeleton\builder\support\ClassNewMethod;
use huikedev\huike_generator\generator\logic_skeleton\contract\ClassGenerateAbstract;


class MakeServiceException extends ClassGenerateAbstract
{
    /**
     * @var HuikeActions
     */
    protected $action;
    // 类名 包含前缀
    protected $fullClassName;

    /**
     * @var ClassBuilder
     */
    protected $classBuilder;
    /**
     * @var ClassNewMethod
     */
    protected $newMethod;
    public function handle(HuikeActions $action)
    {
        $this->action = $action;
        $this->fullClassName = $action->controller->exception_class;
        $this->file = UtilsTools::replaceSeparator(app()->getRootPath().$action->controller->exception_file);

        if(file_exists($this->file)){
            $this->setResult('success','exception','none','服务异常类：已存在，无需创建或追加方法');
            return $this;
        }
        try {
            $this->getClassBuilder();
            $this->buildMethod();
            $this->classBuilder->addMethod($this->newMethod)->build()->toFile();
            $this->setResult('success','exception','create','服务异常类：创建成功');
        }catch (\Exception $e){
            HuikeLog::error($e);
            $this->setResult('fail','exception','create','服务异常类：'.$e->getMessage());
        }
        return $this;
    }

    protected function getClassBuilder():void
    {
        $this->classBuilder = new ClassBuilder($this->fullClassName,$this->file);
        $this->parseExtendClass();

    }

    protected function buildMethod():void
    {
        ClassBuilder::addImport($this->fullClassName,AppServiceException::class);
        $this->newMethod = new ClassNewMethod($this->classBuilder->getFullClassName());
        $this->newMethod->setName('setExceptionKey');
        $methodBody = [
            '$this->exceptionKey = \''.$this->action->controller->exception_key.'\';'
        ];
        $this->newMethod->setBody($methodBody);
        $this->newMethod->setHiddenDoc(true);
    }

    protected function parseExtendClass():void
    {
        if( $this->action->controller->module->extend_module_id > 0 && empty($this->action->controller->module->extend_module->root_base_exception) === false){
            $extendClass = UtilsTools::replaceNamespace($this->action->controller->module->extend_module->root_base_exception);
            if(class_exists($extendClass)){
                $this->classBuilder->setExtendClass($extendClass);
            }
        }else{
            $this->classBuilder->setExtendClass(AppServiceException::class);
        }
    }

}