<?php


namespace huikedev\huike_generator\migration;


use huikedev\huike_generator\migration\contract\TableActionAbstract;
use huikedev\huike_generator\migration\support\ParseTableFields;
use think\Collection;
use think\facade\Db;
use think\helper\Str;

class TableToMigration extends TableActionAbstract
{
    protected $collection = 'utf8mb4_general_ci';
    protected $engine;
    protected $comment = null;
    protected $phpdoc = null;
    /**
     * @var string
     */
    protected $migrateFile;

    public function __construct()
    {
        $this->path = app()->getRootPath();
    }

    public function getMigrateFile(): string
    {
        return $this->migrateFile;
    }

    public function getVersion(): string
    {
        $filename = pathinfo($this->migrateFile,PATHINFO_FILENAME);
        return current(explode('_',$filename));
    }

    public function handle(string $modelOrTable): TableToMigration
    {
        $this->getTable($modelOrTable);

        $this->parseTableConfig();
        $parseTableFields = (new ParseTableFields())->handle($this->table);
        $this->migrateFile = (new MakeMigration())->setOverwrite(true)->setPath($this->path)->handle(Str::studly($this->table));
        $this->phpdoc .='数据库迁移文件生成于：'.date('Y-m-d H:i:s');
        (new FillMigration())->setEngine($this->engine)
            ->setCollection($this->collection)
            ->setPhpdoc($this->phpdoc)
            ->handle(Str::studly($this->table),$this->migrateFile,$parseTableFields->getMigrationFields(),$this->comment);
        return $this;
    }

    protected function parseTableConfig(): TableToMigration
    {
        $tableStatus = Db::query('show table status');
        $tableStatusCollection = new Collection($tableStatus);
        $tableConfig = $tableStatusCollection->where('Name','=',$this->table)->first();
        if(is_null($tableConfig)){
            return $this;
        }
        if(isset($tableConfig['Engine']) && empty($tableConfig['Engine']) === false){
            $this->engine = $tableConfig['Engine'];
        }
        if(isset($tableConfig['Collation']) && empty($tableConfig['Collation']) === false){
            $this->collection = $tableConfig['Collation'];
        }
        if(isset($tableConfig['Comment']) && empty($tableConfig['Comment']) === false){
            $this->comment = $tableConfig['Comment'];
        }
        if(isset($tableConfig['Create_time']) && empty($tableConfig['Create_time']) === false){
            $this->phpdoc = '原始表创建于：'.$tableConfig['Create_time'].',';
        }
        return $this;
    }
}