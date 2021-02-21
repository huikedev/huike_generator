<?php


namespace huikedev\huike_generator\generator\logic_skeleton\builder\provider\class_builder;


use huikedev\huike_generator\generator\logic_skeleton\builder\constract\CodeBuilder;
use think\Collection;

class ClassPropertyBuilder implements CodeBuilder
{

    protected $methods;
    /**
     * @var Collection
     */
    protected $properties;
    protected $fullClassName;
    public function __construct(string $fullClassName)
    {
        $this->fullClassName = $fullClassName;
        $this->properties = new Collection();
    }

    public function all():Collection
    {
        return $this->properties;
    }

    public function toSource(): string
    {
        // TODO: Implement toSource() method.
    }

    public function toArray(): array
    {
        // TODO: Implement toArray() method.
    }

    public function add($value): void
    {
        $this->properties->push($value);
    }

    public function remove(array $filter): void
    {
        // TODO: Implement remove() method.
    }

    public function find($filter)
    {
        // TODO: Implement find() method.
    }

    public function has($filter): bool
    {
        // TODO: Implement has() method.
    }


}