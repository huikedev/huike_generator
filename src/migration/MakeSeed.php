<?php


namespace huikedev\huike_generator\migration;


use huikedev\huike_base\utils\UtilsTools;
use think\Exception;
use think\helper\Str;

class MakeSeed
{
    protected $path;
    public function __construct()
    {
        $this->path = app()->getRootPath();
    }
    public function setPath(string $path):self
    {
        $this->path = $path;
        return $this;
    }

    protected function getPath()
    {
        return UtilsTools::replaceSeparator($this->path.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'seeds');
    }

    public function handle(string $tableName)
    {
        $className = Str::studly($tableName);
        if (!is_dir($this->getPath())) {
            mkdir($this->getPath(), 0755, true);
        }
        $filePath = $this->getPath() .DIRECTORY_SEPARATOR. $className . '.php';
        if(file_exists($filePath)){
            unlink($filePath);
        }
        // Load the alternative template if it is defined.
        $contents = file_get_contents($this->getTemplate());

        // inject the class names appropriate to this migration
        $contents = strtr($contents, [
            'SeederClass' => $className,
        ]);

        if (false === file_put_contents($filePath, $contents)) {
            throw new Exception(sprintf('The file "%s" could not be written to', $this->path));
        }

        return $filePath;
    }

    protected function getTemplate(): string
    {
        return __DIR__ . '/stub/seed.stub';
    }
}