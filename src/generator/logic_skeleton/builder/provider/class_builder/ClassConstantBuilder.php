<?php


namespace huikedev\huike_generator\generator\logic_skeleton\builder\provider\class_builder;


use huikedev\huike_generator\generator\logic_skeleton\builder\constract\CodeBuilder;
use think\Collection;

class ClassConstantBuilder implements CodeBuilder
{
    /**
     * @var Collection
     */
    protected $constants;
    protected $fullClassName;
    public function __construct(string $fullClassName)
    {
        $this->fullClassName = $fullClassName;
        $this->constants = new Collection();
    }

    public function all(): Collection
    {
        return $this->constants;
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
        $this->constants->push($value);
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