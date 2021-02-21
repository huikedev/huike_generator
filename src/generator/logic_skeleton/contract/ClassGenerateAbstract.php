<?php


namespace huikedev\huike_generator\generator\logic_skeleton\contract;


use huikedev\huike_base\utils\UtilsTools;

abstract class ClassGenerateAbstract
{
    protected $msg;
    protected $status;
    protected $actionType;
    protected $fullClassName;
    protected $location;
    protected $file;
    protected $isSpeed = false;
    protected $path;

    protected function setResult(string $status,string $location,string $actionType,string $msg)
    {
        $this->msg = $msg;
        $this->status = $status;
        $this->actionType = $actionType;
        $this->location = $location;
    }

    public function getResult(): array
    {
        return [
            'msg'=>$this->msg,
            'status'=>$this->status,
            'actionType'=>$this->actionType,
            'class'=>$this->fullClassName,
            'location'=>$this->location
        ];
    }

    public function setPath(string $path):self
    {
        $this->path = UtilsTools::replaceSeparator($path);
        return $this;
    }

    protected function getBaseStub(): string
    {
        $dir = __DIR__;
        $dir = str_replace('\\',DIRECTORY_SEPARATOR,$dir);
        $relateDir = explode(DIRECTORY_SEPARATOR,$dir);
        array_pop($relateDir);
        return implode(DIRECTORY_SEPARATOR,$relateDir).DIRECTORY_SEPARATOR.'stub';
    }
}