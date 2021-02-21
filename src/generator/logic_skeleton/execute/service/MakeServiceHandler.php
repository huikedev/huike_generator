<?php


namespace huikedev\huike_generator\generator\logic_skeleton\execute\service;


use huikedev\dev_admin\common\model\huike\HuikeActions;
use huikedev\huike_base\app_const\ServiceReturnType;
use huikedev\huike_base\log\HuikeLog;
use huikedev\huike_base\utils\UtilsTools;
use huikedev\huike_generator\generator\logic_skeleton\builder\ClassBuilder;
use huikedev\huike_generator\generator\logic_skeleton\builder\support\ClassNewMethod;
use huikedev\huike_generator\generator\logic_skeleton\contract\ClassGenerateAbstract;
use think\helper\Str;

class MakeServiceHandler extends ClassGenerateAbstract
{
    /**
     * @var HuikeActions
     */
    protected $action;
    /**
     * @var ClassBuilder
     */
    protected $classBuilder;
    /**
     * @var ClassNewMethod
     */
    protected $newMethod;
    protected $file;


    public function handle(HuikeActions $action)
    {
        $this->action = $action;
        $this->fullClassName = UtilsTools::replaceNamespace($action->controller->provider_namespace.DIRECTORY_SEPARATOR.Str::studly($action->action_name));
        $this->file = UtilsTools::replaceSeparator(app()->getRootPath().$action->controller->provider_path.DIRECTORY_SEPARATOR.Str::studly($action->action_name).'.php');
        if(file_exists($this->file)){
            $this->setResult('fail','handler','none','服务Handler：已存在，无法修改');
            return $this;
        }
        try {
            $this->getClassBuilder();
            $this->buildMethod();
            $this->classBuilder->addMethod($this->newMethod)->build()->toFile();
            $this->setResult('success','handler','create','服务Handler：创建成功');
        }catch (\Exception $e){
            HuikeLog::error($e);
            $this->setResult('fail','handler','create',$e->getMessage());
        }
        return $this;

    }

    protected function getClassBuilder()
    {
        $this->classBuilder = new ClassBuilder($this->fullClassName,$this->file);
    }

    protected function buildMethod()
    {
        $this->newMethod = new ClassNewMethod($this->classBuilder->getFullClassName());
        $this->newMethod->setName('handle');
        $methodBody = [
            '// 你的业务逻辑代码'
        ];
        $this->newMethod->setBody($methodBody);
        if(isset(ServiceReturnType::TP_DEFAULT[$this->action->service_return_type])){
            $this->newMethod->setReturnType(ServiceReturnType::TP_DEFAULT[$this->action->service_return_type]);
        }else{
            $this->newMethod->setReturnType($this->action->service_return_type);
        }
        $desc = $this->action->action_title;
        if(is_null($this->action->remark) === false){
            $desc .=' '.$this->action->remark;
        }
        $this->newMethod->addPhpdoc('desc',$desc);
        $this->newMethod->addPhpdoc('huike','handler');
    }
}