<?php


namespace huikedev\huike_generator\migration;


use huikedev\huike_base\utils\UtilsTools;
use Phinx\Db\Adapter\AdapterFactory;
use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Seed\AbstractSeed;
use Phinx\Seed\SeedInterface;
use Phinx\Util\Util;
use think\Exception;
use think\facade\Config;
use think\helper\Str;
use think\migration\Seeder;

class RunSeed
{
    /**
     * @var AdapterInterface
     */
    protected $adapter;
    protected $filename;
    /**
     * @var array
     */
    protected $seeds;
    protected $path;
    public function __construct()
    {
        $this->path = app()->getRootPath().'database'.DIRECTORY_SEPARATOR.'seeds';
    }
    public function setPath(string $path): RunSeed
    {
        $this->path = UtilsTools::replaceSeparator($path);
        return $this;
    }
    public function handle(string $seed=null)
    {
        $seeds = $this->getSeeds();
        if (null === $seed) {
            // run all seeders
            foreach ($seeds as $seeder) {
                if (array_key_exists($seeder->getName(), $seeds)) {
                    $this->executeSeed($seeder);
                }
            }
        } else {
            $seed = Str::studly($seed);
            // run only one seeder
            if (array_key_exists($seed, $seeds)) {
                $this->executeSeed($seeds[$seed]);
            } else {
                throw new Exception(sprintf('The seed class "%s" does not exist', $seed));
            }
        }
        return true;
    }

    protected function executeSeed(SeedInterface $seed)
    {
        // Execute the seeder and log the time elapsed.
        $seed->setAdapter($this->getAdapter());

        // begin the transaction if the adapter supports it
        if ($this->getAdapter()->hasTransactions()) {
            $this->getAdapter()->beginTransaction();
        }

        // Run the seeder
        if (method_exists($seed, SeedInterface::RUN)) {
            $seed->run();
        }

        // commit the transaction if the adapter supports it
        if ($this->getAdapter()->hasTransactions()) {
            $this->getAdapter()->commitTransaction();
        }
    }

    protected function getSeeds(): array
    {
        if(is_dir($this->path) === false){
            throw new Exception($this->path .' not exist');
        }

        $phpFiles = glob($this->path . DIRECTORY_SEPARATOR . '*.php', defined('GLOB_BRACE') ? GLOB_BRACE : 0);

        $fileNames = [];
        /** @var Seeder[] $seeds */
        $seeds = [];

        foreach ($phpFiles as $filePath) {
            if (Util::isValidSeedFileName(basename($filePath))) {
                // convert the filename to a class name
                $class             = pathinfo($filePath, PATHINFO_FILENAME);
                $fileNames[$class] = basename($filePath);

                // load the seed file
                /** @noinspection PhpIncludeInspection */
                require_once $filePath;
                if (!class_exists($class)) {
                    throw new Exception(sprintf('Could not find class "%s" in file "%s"', $class, $filePath));
                }

                // instantiate it
                $seed = new $class();

                if (!($seed instanceof AbstractSeed)) {
                    throw new Exception(sprintf('The class "%s" in file "%s" must extend \Phinx\Seed\AbstractSeed', $class, $filePath));
                }

                $seeds[$class] = $seed;
            }
        }

        ksort($seeds);
        $this->seeds = $seeds;
        return $this->seeds;
    }

    public function getAdapter(): AdapterInterface
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

    /**
     * 获取数据库配置
     * @return array
     */
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