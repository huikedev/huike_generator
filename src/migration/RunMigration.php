<?php


namespace huikedev\huike_generator\migration;


use huikedev\huike_base\utils\UtilsTools;
use huikedev\huike_generator\migration\contract\MigrationAbstract;
use Phinx\Db\Adapter\AdapterFactory;
use Phinx\Migration\AbstractMigration;
use Phinx\Migration\MigrationInterface;
use Phinx\Util\Util;

use think\Exception;
use think\facade\Config;
use think\helper\Str;
use think\migration\Migrator;

class RunMigration extends MigrationAbstract
{
    protected $adapter;

    public function __construct()
    {
        $this->path = app()->getRootPath().'database'.DIRECTORY_SEPARATOR.'migrations';
    }

    public function setMigrations(string $migrateFile): RunMigration
    {
        if(file_exists($migrateFile)){
            $migrateFiles = glob($migrateFile, defined('GLOB_BRACE') ? GLOB_BRACE : 0);
        }else{
            throw new Exception($migrateFile . 'is not file or path');
        }
        $this->parseMigration($migrateFiles);
        return $this;
    }

    public function setPath(string $path): RunMigration
    {
        $this->path = UtilsTools::replaceSeparator($path);
        return $this;
    }

    public function handle(string $className=null)
    {
        $class = is_null($className) ? null: Str::studly($className);
        $migrations = $this->getMigrations();
        $versions   = $this->getVersions();
        if (empty($versions) && empty($migrations)) {
            throw new Exception('migrations or versions is empty');
        }
        if(is_null($class) === false){
            if(isset($this->classes[$class])===false || is_numeric($this->classes[$class])===false){
                throw new Exception( '未找到数据库迁移文件');
            }
            if (in_array($this->classes[$class],$versions)) {
                throw new Exception( '数据库迁移记录已存在或有更新的版本');
            }else{
                $this->executeMigration($migrations[$this->classes[$class]], MigrationInterface::UP);
            }
            return $this->classes[$class];
        }else{
            foreach ($migrations as $migration) {
                if (in_array($migration->getVersion(),$versions)) {
                    throw new Exception( '数据库迁移记录已存在或有更新的版本');
                }
                if (!in_array($migration->getVersion(), $versions)) {
                    $this->executeMigration($migration, MigrationInterface::UP);
                }
            }
            return true;
        }
    }



    protected function parseMigration($migrateFiles): RunMigration
    {
        $fileNames = [];
        /** @var Migrator[] $versions */
        $versions = [];

        foreach ($migrateFiles as $filePath) {
            if (Util::isValidMigrationFileName(basename($filePath))) {
                $version = Util::getVersionFromFileName(basename($filePath));

                if (isset($versions[$version])) {
                    throw new \InvalidArgumentException(sprintf('Duplicate migration - "%s" has the same version as "%s"', $filePath, $versions[$version]->getVersion()));
                }

                // convert the filename to a class name
                $class = Util::mapFileNameToClassName(basename($filePath));

                if (isset($fileNames[$class])) {
                    throw new \InvalidArgumentException(sprintf('Migration "%s" has the same name as "%s"', basename($filePath), $fileNames[$class]));
                }

                $fileNames[$class] = basename($filePath);

                // load the migration file
                /** @noinspection PhpIncludeInspection */
                require_once $filePath;
                if (!class_exists($class)) {
                    throw new \InvalidArgumentException(sprintf('Could not find class "%s" in file "%s"', $class, $filePath));
                }

                // instantiate it
                $migration = new $class($version);

                if (!($migration instanceof AbstractMigration)) {
                    throw new \InvalidArgumentException(sprintf('The class "%s" in file "%s" must extend \Phinx\Migration\AbstractMigration', $class, $filePath));
                }

                $versions[$version] = $migration;
            }
        }

        ksort($versions);
        $this->migrations = $versions;
        return $this;
    }

    protected function getCurrentVersion(): int
    {
        $versions = $this->getVersions();
        $version  = 0;

        if (!empty($versions)) {
            $version = end($versions);
        }

        return $version;
    }

    public function getAdapter()
    {
        if (isset($this->adapter)) {
            return $this->adapter;
        }

        $options = $this->getDbConfig();

        $adapter = AdapterFactory::instance()->getAdapter($options['adapter'], $options);

        if ($adapter->hasOption('table_prefix') || $adapter->hasOption('table_suffix')) {
            $adapter = AdapterFactory::instance()->getWrapper('prefix', $adapter);
        }

        $this->adapter = $adapter;

        return $adapter;
    }

    protected function getDbConfig(): array
    {
        $default = Config::get('database.default');

        $config = Config::get("database.connections.{$default}");

        if (0 == $config['deploy']) {
            $dbConfig = [
                'adapter'      => $config['type'],
                'host'         => $config['hostname'],
                'name'         => $config['database'],
                'user'         => $config['username'],
                'pass'         => $config['password'],
                'port'         => $config['hostport'],
                'charset'      => $config['charset'],
                'table_prefix' => $config['prefix'],
            ];
        } else {
            $dbConfig = [
                'adapter'      => explode(',', $config['type'])[0],
                'host'         => explode(',', $config['hostname'])[0],
                'name'         => explode(',', $config['database'])[0],
                'user'         => explode(',', $config['username'])[0],
                'pass'         => explode(',', $config['password'])[0],
                'port'         => explode(',', $config['hostport'])[0],
                'charset'      => explode(',', $config['charset'])[0],
                'table_prefix' => explode(',', $config['prefix'])[0],
            ];
        }

        $table = Config::get('database.migration_table', 'migrations');

        $dbConfig['default_migration_table'] = $dbConfig['table_prefix'] . $table;

        return $dbConfig;
    }
}