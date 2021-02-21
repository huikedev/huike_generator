<?php


namespace huikedev\huike_generator\generator\logic_skeleton\execute\controller;


use huikedev\dev_admin\common\model\huike\HuikeActions;
use huikedev\huike_base\exceptions\response\ResponseException;
use huikedev\huike_base\log\HuikeLog;
use huikedev\huike_base\response\AppResponse;
use huikedev\huike_base\utils\UtilsTools;
use huikedev\huike_generator\generator\logic_skeleton\builder\ClassBuilder;
use huikedev\huike_generator\generator\logic_skeleton\builder\support\ClassNewMethod;
use huikedev\huike_generator\generator\logic_skeleton\contract\ClassGenerateAbstract;

class MakeController extends ClassGenerateAbstract
{
    /**
     * @var HuikeActions
     */
    protected $action;
    protected $isExist;
    /**
     * @var ClassBuilder
     */
    protected $classBuilder;
    /**
     * @var ClassNewMethod
     */
    protected $newMethod;
    protected $isExtend = false;
    protected $logicNamespace;
    public function handle(HuikeActions $action,$isSpeed = false)
    {
        $this->isSpeed = $isSpeed;
        try{
            $this->action = $action;
            $this->fullClassName = $action->controller->controller_class;
            $this->file = UtilsTools::replaceSeparator(app()->getRootPath().$this->fullClassName.'.php');
            $this->isExist = file_exists($this->file);
            $this->getClassBuilder();
            $this->buildMethod();
            $this->classBuilder->addMethod($this->newMethod)->build()->toFile($this->isSpeed ===true);
            if($this->isExist){
                $this->setResult('success','controller','add','控制器：追加方法成功');
            }else{
                $this->setResult('success','controller','create','控制器：创建成功');
            }
        }catch (\Exception $e){
            HuikeLog::error($e);
            $this->setResult('fail','controller','create','控制器：'.$e->getMessage());
        }
        return $this;
    }

    protected function getClassBuilder():void
    {
        $this->classBuilder = new ClassBuilder($this->fullClassName);
        $this->parseExtendClass();
    }

    protected function parseExtendClass():void
    {
        if( $this->action->controller->module->extend_module_id > 0 && empty($this->action->controller->module->extend_module->root_base_controller) === false){
            $extendClass = UtilsTools::replaceNamespace($this->action->controller->module->extend_module->root_base_controller);
            if(class_exists($extendClass)){
                $this->classBuilder->setExtendClass($extendClass);
                $this->isExtend = true;
            }
        }
    }

    protected function buildMethod()
    {
        if($this->isExtend === false){
            ClassBuilder::addImport($this->fullClassName,AppResponse::class);
            $methodBody = [
                'return (new AppResponse())->render();'
            ];
        }else{
            $methodBody = [
                'return $this->response->render();'
            ];
        }

        ClassBuilder::addImport($this->fullClassName,ResponseException::class);
        $this->newMethod = new ClassNewMethod($this->classBuilder->getFullClassName());
        $this->newMethod->setName($this->action->action_name);

        $this->newMethod->setBody($methodBody);
        $desc = $this->action->action_title;
        if(is_null($this->action->remark) === false){
            $desc .=' '.$this->action->remark;
        }
        $this->newMethod->addPhpdoc('desc',$desc);
        $this->newMethod->addPhpdoc('throws','ResponseException');
        $this->newMethod->addPhpdoc('huike','controller');
    }

}