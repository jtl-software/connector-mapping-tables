<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc;

class TableCollection
{
    /**
     * @var AbstractTable[]
     */
    protected array $tables = [];

    /**
     * MappingTableCollection constructor.
     *
     * @param array<AbstractTable> $tables
     *
     * @throws DbcRuntimeException
     */
    public function __construct(array $tables = [])
    {
        foreach ($tables as $table) {
            $this->set($table);
        }
    }

    /**
     * @param AbstractTable $table
     *
     * @return TableCollection
     * @throws DbcRuntimeException
     * @throws DbcRuntimeException
     */
    public function set(AbstractTable $table): TableCollection
    {
        $this->tables[$table->getTableName()] = $table;
        return $this;
    }

    /**
     * @param AbstractTable $table
     *
     * @return boolean
     * @throws \Exception
     */
    public function removeByInstance(AbstractTable $table): bool
    {
        $name = $table->getTableName();
        if ($this->has($name) && ($this->get($name) === $table)) {
            return $this->removeByName($name);
        }
        return false;
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    public function has(string $name): bool
    {
        return isset($this->tables[$name]) && $this->tables[$name] instanceof AbstractTable;
    }

    /**
     * @param string $name
     *
     * @return AbstractTable
     * @throws DbcRuntimeException
     */
    public function get(string $name): AbstractTable
    {
        if (!$this->has($name)) {
            throw DbcRuntimeException::tableNotFound($name);
        }
        return $this->tables[$name];
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    public function removeByName(string $name): bool
    {
        if ($this->has($name)) {
            unset($this->tables[$name]);
            return true;
        }
        return false;
    }

    /**
     * @param string $className
     *
     * @return TableCollection
     * @throws DbcRuntimeException
     */
    public function filterByInstanceClass(string $className): TableCollection
    {
        return new self($this->filterArrayByInstanceClass($className));
    }

    /**
     * @param string $className
     *
     * @return array<AbstractTable>
     * @throws DbcRuntimeException
     */
    protected function filterArrayByInstanceClass(string $className): array
    {
        if (!\class_exists($className)) {
            throw DbcRuntimeException::classNotFound($className);
        }

        if (!\is_a($className, AbstractTable::class, true)) {
            throw DbcRuntimeException::classNotChildOfTable($className);
        }

        return \array_filter($this->toArray(), static function (AbstractTable $table) use ($className) {
            return $table instanceof $className;
        });
    }

    /**
     * @return AbstractTable[]
     */
    public function toArray(): array
    {
        return \array_values($this->tables);
    }

    /**
     * @param string $className
     *
     * @return AbstractTable|null
     * @throws DbcRuntimeException
     */
    public function filterOneByInstanceClass(string $className): ?AbstractTable
    {
        $tables = $this->filterArrayByInstanceClass($className);
        return \count($tables) > 0 ? \reset($tables) : null;
    }
}
