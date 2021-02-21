<?php


namespace huikedev\huike_generator\migration;


use huikedev\huike_base\utils\UtilsTools;
use Phinx\Util\Util;
use think\Exception;
use think\helper\Str;

class MakeMigration
{
    protected $migrationPath;
    protected $version;
    /**
     * @var array
     */
    protected $classToFile = [];
    /**
     * @var bool
     */
    protected $isOverwrite = false;
    public function __construct()
    {
        $this->migrationPath = app()->getRootPath();
        $this->version = intval(microtime(true)*10000);
    }
    public function setPath(string $path):self
    {
        $this->migrationPath = UtilsTools::replaceSeparator($path);
        return $this;
    }
    public function setOverwrite(bool $isOverwrite): MakeMigration
    {
        $this->isOverwrite = $isOverwrite;
        return $this;
    }
    public function setVersion(int $version): MakeMigration
    {
        $this->version = $version;
        return $this;
    }
    protected function getPath(): string
    {
        return UtilsTools::replaceSeparator($this->migrationPath.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR);
    }
    public function handle(string $name): string
    {
        $name = Str::studly($name);
        $path = $this->ensureDirectory();
        if (!Util::isValidPhinxClassName($name)) {
            throw new Exception(sprintf('The migration class name "%s" is invalid. Please use CamelCase format.', $name));
        }
        $this->getExistingMigrations($path);
        if (isset($this->classToFile[$name])) {
            if($this->isOverwrite === false){
                throw new Exception(sprintf('The migration class name "%s" already exists', $name));
            }else{
                unlink($this->classToFile[$name]);
            }

        }

        // Compute the file path
        $filePath = $path . DIRECTORY_SEPARATOR . $this->getFileName($name);

        if (file_exists($filePath)) {
            if($this->isOverwrite === false){
                throw new Exception(sprintf('The file "%s" already exists', $filePath));
            }
            unlink($filePath);
        }

        // Verify that the template creation class (or the aliased class) exists and that it implements the required interface.
        $aliasedClassName = null;

        // Load the alternative template if it is defined.
        $contents = file_get_contents($this->getTemplate());

        // inject the class names appropriate to this migration
        $contents = strtr($contents, [
            'MigratorClass' => $name,
        ]);

        if (false === file_put_contents($filePath, $contents)) {
            throw new Exception(sprintf('The file "%s" could not be written to', $path));
        }

        return $filePath;
    }

    protected function getExistingMigrations(string $path)
    {
        $phpFiles = glob($path . DIRECTORY_SEPARATOR . '*.php');
        foreach ($phpFiles as $filePath) {
            if (preg_match('/([0-9]+)_([_a-z0-9]*).php/', basename($filePath))) {
                $this->classToFile[Util::mapFileNameToClassName(basename($filePath))] =$filePath;
            }
        }

    }

    protected function ensureDirectory(): string
    {
        if (!is_dir($this->getPath()) && !mkdir($this->getPath(), 0755, true)) {
            throw new Exception(sprintf('directory "%s" does not exist', $this->getPath()));
        }

        if (!is_writable($this->getPath())) {
            throw new Exception(sprintf('directory "%s" is not writable', $this->getPath()));
        }

        return $this->getPath();
    }

    protected function getFileName(string $name): string
    {
        return $this->version.'_'.Str::snake($name).'.php';
    }

    protected function getTemplate(): string
    {
        return __DIR__ . '/stub/migrate.stub';
    }
}