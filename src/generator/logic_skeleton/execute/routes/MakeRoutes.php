<?php


namespace huikedev\huike_generator\generator\logic_skeleton\execute\routes;


use huikedev\dev_admin\common\model\huike\HuikeActions;
use huikedev\dev_admin\common\model\huike\HuikeControllers;
use huikedev\dev_admin\common\model\huike\HuikeModules;
use huikedev\huike_base\app_const\RequestMethods;
use huikedev\huike_base\utils\UtilsTools;
use huikedev\huike_generator\generator\logic_skeleton\contract\MakeClassAbstract;
use think\Exception;

class MakeRoutes extends MakeClassAbstract
{
    protected $routes = [];
    protected $overwrite = false;
    /**
     * @var HuikeModules
     */
    protected $module;
    /**
     * @var array
     */
    protected $routeCode = [];
    protected function getStub()
    {
        // TODO: Implement getStub() method.
    }

    public function setOverwrite(bool $overwrite): MakeRoutes
    {
        $this->overwrite = $overwrite;
        return $this;
    }

    public function handle(int $moduleId)
    {

        $this->module = HuikeModules::where('id','=',$moduleId)->findOrEmpty();
        $controllers = HuikeControllers::with('path')->where('module_id','=',$moduleId)->where('path_id','>',0)->select();
        $controllerIds = $controllers->column('id');
        $actions = HuikeActions::where('controller_id','in',$controllerIds)->select();
        foreach ($actions as $action){
            $nameArray = [];
            $groupName = [];
            /**
             * @var HuikeActions $action
             */
            $controller = $controllers->where('id','=',$action->controller_id)->first();
            $groupName[] = $controller->route_name;
            if(isset($controller->path->controller_name) && $controller->path->controller_name !== '/'){
                if($controller->path->controller_name !== '/' && empty($controller->path->route_name) === false){
                    $groupName[] = $controller->path->route_name;
                }
                $nameArray[] = $controller->path->controller_name;
            }
            $nameArray[] = $this->module->module_name;
            $groupNameString = UtilsTools::replaceSeparator(implode('/',array_reverse($groupName)),'/');
            if(count($nameArray) > 0){
                $prefix = implode('.',array_reverse($nameArray)).'.'.$controller->controller_name.'/';
            }else{
                $prefix = $controller->controller_name.'/';
            }
            $method =isset(RequestMethods::METHODS[$action->request_method]) ? strtolower(RequestMethods::METHODS[$action->request_method]) : 'any';
            if(isset($this->routes[$groupNameString]['prefix'])===false){
                $this->routes[$groupNameString]['prefix'] = $prefix;
                $this->routes[$groupNameString]['remark'] = $this->module->module_title .' - '.$controller->controller_title;
            }
            $actionRemark = empty($action->remark) ? $action->action_title : $action->remark;
            $controllerRemark = empty($controller->remark) ? $controller->controller_title : $controller->remark;
            $this->routes[$groupNameString]['rules'][] = [
                'route'=>$action->action_name,
                'rule'=>$action->route_name,
                'method'=>$method,
                'remark'=>$controllerRemark .' - '.$actionRemark
            ];
        }
        $this->buildRoute();
        $routeFile = app()->getRootPath().'huike'.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.$this->module->module_name.'.php';
        $dir = pathinfo($routeFile,PATHINFO_DIRNAME);
        if(is_dir($dir) === false){
            mkdir($dir,0755,true);
        }
        if(is_writable($dir) === false){
            throw new Exception($dir.' 不可写');
        }
        if(file_exists($routeFile)){
            if($this->overwrite === false){
                $originRoute = file_get_contents($routeFile);
                file_put_contents($routeFile.'.bak-'.time(),$originRoute);
            }
            unlink($routeFile);
        }
        $newRoute = "<?php \n";
        $newRoute .="\n";
        $newRoute .= 'use think\facade\Route;'."\n";
        $newRoute .="\n";
        $newRoute .="\n";
        $newRoute .=implode("\n",$this->routeCode);
        file_put_contents($routeFile,$newRoute);
        return true;
    }

    protected function buildRoute()
    {
        if(empty($this->module->bind_domain)){
            $this->routeCode[] = 'Route::group(\''.$this->module->route_name.'\',function(){';
        }else{
            $domain = '[\'';
            $domain .=implode('\',',$this->module->bind_domain);
            $domain .='\']';
            $this->routeCode[] = 'Route::domain('.$domain.',function(){';
        }
        // 按控制器分组路由
        foreach ($this->routes as $groupName=>$groupConfig){
            $this->routeCode[] = '';
            $this->routeCode[] = "\t".'//======================'.$groupConfig['remark'].'======================';
            if(empty($groupName)){
                $this->routeCode[] = "\t".'Route::group(function(){';
            }else{
                $this->routeCode[] = "\t".'Route::group(\''.$groupName.'\',function(){';
            }
            foreach ($groupConfig['rules'] as $rule){
                $this->routeCode[] = "\t\t//".$rule['remark'];
                $this->routeCode[] = "\t\t".'Route::'.$rule['method'].'(\''.$rule['rule'].'\', \''.$rule['route'].'\');';
            }
            $this->routeCode[] = "\t".'})->prefix(\''.$groupConfig['prefix'].'\');';
        }
        if(empty($this->module->route_middleware)){
            $this->routeCode[] = '});';
        }else{
            $middleware = '[\'';
            $middleware .=implode('\',',$this->module->route_middleware);
            $middleware .='\']';
            $this->routeCode[] = '})->middleware('.$middleware.');';
        }

    }
}