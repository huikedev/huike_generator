<?php


namespace huikedev\huike_generator\generator\logic_skeleton\execute\service;


use huikedev\dev_admin\common\model\huike\HuikeActions;
use huikedev\huike_base\log\HuikeLog;
use huikedev\huike_base\utils\UtilsTools;
use huikedev\huike_generator\generator\logic_skeleton\builder\ClassBuilder;
use huikedev\huike_generator\generator\logic_skeleton\builder\support\ClassNewMethod;
use huikedev\huike_generator\generator\logic_skeleton\contract\ClassGenerateAbstract;
use huikedev\huike_generator\generator\logic_skeleton\execute\facade\MakeFacade;
use think\helper\Str;

class MakeService extends ClassGenerateAbstract
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


    public function handle(HuikeActions $action,$isSpeed = false)
    {
        $this->isSpeed = $isSpeed;
        try {
            $this->action = $action;
            $this->fullClassName = $action->controller->service_class;
            $this->file = UtilsTools::replaceSeparator(app()->getRootPath().$action->controller->service_file);

            $isExist = file_exists($this->file);
            $this->getClassBuilder();
            $this->buildMethod();
            $this->classBuilder->addMethod($this->newMethod)->build()->toFile($this->isSpeed === true);
            if($isExist){
                $this->setResult('success','service','add','服务层：追加方法成功');
            }else{
                $this->setResult('success','service','create','服务层：创建服务类成功');
            }
            if(boolval($action->controller->is_static_service) === false){
                (new MakeServiceFacade())->handle($this->fullClassName,0,true);
            }
        }catch (\Exception $e){
            HuikeLog::error($e);
            $this->setResult('fail','service','unknown','服务层：'.$e->getMessage());
        }
        return $this;
    }
    protected function getClassBuilder()
    {
        $this->classBuilder = new ClassBuilder($this->fullClassName,$this->file);
    }
    protected function buildMethod()
    {
        $this->newMethod = new ClassNewMethod($this->fullClassName);
        $this->newMethod->setName($this->action->action_name);
        // 静态代理模式
        if($this->action->controller->is_static_service){
            $this->newMethod->setStatic(true);
        }
        $this->newMethod->setReturnType($this->action->service_return_type);
        $this->newMethod->setBody($this->parseMethodBody());
        $desc = $this->action->action_title;
        if(is_null($this->action->remark) === false){
            $desc .=' '.$this->action->remark;
        }
        $this->newMethod->addPhpdoc('desc',$desc);
        $this->newMethod->addPhpdoc('huike','service');
    }
    protected function parseMethodBody():array
    {
        $handler = UtilsTools::replaceNamespace($this->action->controller->provider_namespace.DIRECTORY_SEPARATOR.Str::studly($this->action->action_name));
        ClassBuilder::addImport($this->fullClassName,$handler);
        return [
            'return app('.Str::studly($this->action->action_name).'::class,[],true)->handle();'
        ];
    }
}