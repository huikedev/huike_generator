<?php


namespace huikedev\huike_generator\generator\logic_skeleton\execute\logic;


use huikedev\dev_admin\common\model\huike\HuikeActions;
use huikedev\huike_base\app_const\NoticeType;
use huikedev\huike_base\app_const\RequestMethods;
use huikedev\huike_base\app_const\response\AppResponseType;
use huikedev\huike_base\base\BaseLogic;
use huikedev\huike_base\exceptions\AppLogicException;
use huikedev\huike_base\exceptions\AppServiceException;
use huikedev\huike_base\log\HuikeLog;
use huikedev\huike_base\utils\UtilsTools;
use huikedev\huike_generator\generator\logic_skeleton\builder\ClassBuilder;
use huikedev\huike_generator\generator\logic_skeleton\builder\support\ClassNewMethod;
use huikedev\huike_generator\generator\logic_skeleton\contract\ClassGenerateAbstract;

class MakeLogicClass extends ClassGenerateAbstract
{
    /**
     * @var HuikeActions
     */
    protected $action;
    protected $isExist =false;
    protected $facadeClass;
    /**
     * @var ClassBuilder
     */
    protected $classBuilder;
    /**
     * @var ClassNewMethod
     */
    protected $newMethod;


    public function handle(HuikeActions $action,$isSpeed = false)
    {
        $this->isSpeed = $isSpeed;
        try {
            $this->action = $action;
            // eg:huike\logic\controller\dev\generate\Facade
            $this->fullClassName = $action->controller->logic_class;
            $this->facadeClass =$action->controller->facade_class;
            $this->file = UtilsTools::replaceSeparator(app()->getRootPath().$action->controller->logic_file);
            $this->isExist = file_exists($this->file);
            $this->getClassBuilder();
            $this->buildMethod();
            $this->classBuilder->addMethod($this->newMethod)->build()->toFile($this->isSpeed===true);
            if($this->isExist){
                $this->setResult('success','logic','add','逻辑层：追加方法成功');
            }else{
                $this->setResult('success','logic','create','逻辑层：创建成功');
            }

        }catch (\Exception $e){
            HuikeLog::error($e);
            $this->setResult('fail','logic','create','逻辑层：'.$e->getMessage());
        }
        return $this;
    }

    protected function getClassBuilder():void
    {
        $this->classBuilder = new ClassBuilder($this->fullClassName,$this->file);
        if($this->isExist === false){
            $this->parseExtendClass();
        }
    }

    protected function parseExtendClass():void
    {
        if( $this->action->controller->module->extend_module_id > 0 && empty($this->action->controller->module->extend_module->root_base_logic) === false){
            $extendClass = UtilsTools::replaceNamespace($this->action->controller->module->extend_module->root_base_logic);
            if(class_exists($extendClass)){
                $this->classBuilder->setExtendClass($extendClass);
            }
        }else{
            $this->classBuilder->setExtendClass(BaseLogic::class);
        }
    }


    protected function buildMethod():void
    {
        $this->newMethod = new ClassNewMethod($this->classBuilder->getFullClassName());
        $this->newMethod->setName($this->action->action_name);
        $this->parseMethodBody();
        $this->newMethod->setReturnType('self');
        $desc = $this->action->action_title;
        if(is_null($this->action->remark) === false){
            $desc .=' '.$this->action->remark;
        }
        $this->newMethod->addPhpdoc('desc',$desc);
        $this->newMethod->addPhpdoc('huike','logic');
        $this->newMethod->addPhpdoc('throws','AppLogicException');
    }

    protected function parseMethodBody():void
    {
        $methodBody = [
            'try{'
        ];
        if($this->action->controller->is_static_service){
            ClassBuilder::addImport($this->fullClassName,$this->action->controller->service_class);
        }else{
            ClassBuilder::addImport($this->fullClassName,$this->facadeClass);
        }

        $facade = class_basename($this->facadeClass);
        if($this->action->request_type === RequestMethods::GET || $this->action->request_type === RequestMethods::ANY ){
            $methodBody[] = "\t".'$this->data = '.$facade."::".$this->action->action_name."();";
        }else{
            if($this->action->service_return_type ==='bool'){
                $methodBody[] = "\t".$facade."::".$this->action->action_name."();";
            }else{
                $methodBody[] = "\t".'$this->data = '.$facade."::".$this->action->action_name."();";
            }
        }
        if($this->action->notice_type !==0){
            ClassBuilder::addImport($this->fullClassName,NoticeType::class);
            $methodBody[] = "\t".'$this->noticeType = NoticeType::'.strtoupper(NoticeType::ALL[$this->action->notice_type]).";";
        }
        if(is_null($this->action->remind_msg) === false){
            $methodBody[] = "\t".'$this->msg = \''.$this->action->remind_msg."';";
        }
        if($this->action->response_type !== 1){
            ClassBuilder::addImport($this->fullClassName,AppResponseType::class);
            $methodBody[] = "\t".'$this->returnType = AppResponseType::'.AppResponseType::NAMES[$this->action->response_type].';';
        }
        ClassBuilder::addImport($this->fullClassName,AppServiceException::class);
        ClassBuilder::addImport($this->fullClassName,AppLogicException::class);
        $methodBody[] = "}catch (AppServiceException ". '$serviceException){';
        $methodBody[] ="\t".'throw new AppLogicException($serviceException);';
        $methodBody[] = '}';
        $methodBody[] = 'return $this;';
        $this->newMethod->setBody($methodBody);
    }
}