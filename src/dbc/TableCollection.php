<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */

namespace Jtl\Connector\Dbc;

class TableCollection
{
    /**
     * @var AbstractTable[]
     */
    protected $tables = [];

    /**
     * MappingTableCollection constructor.
     * @param AbstractTable[] $tables
     */
    public function __construct(array $tables = [])
    {
        foreach ($tables as $table) {
            $this->set($table);
        }
    }

    /**
     * @param AbstractTable $table
     * @return TableCollection
     */
    public function set(AbstractTable $table): TableCollection
    {
        $this->tables[$table->getTableName()] = $table;
        return $this;
    }

    /**
     * @param AbstractTable $table
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
     * @param string $name
     * @return boolean
     */
    public function has(string $name)
    {
        return isset($this->tables[$name]) && $this->tables[$name] instanceof AbstractTable;
    }

    /**
     * @param string $name
     * @return AbstractTable
     * @throws \Exception
     */
    public function get(string $name): AbstractTable
    {
        if (!$this->has($name)) {
            throw RuntimeException::tableNotFound($name);
        }
        return $this->tables[$name];
    }

    /**
     * @param string $className
     * @return TableCollection
     */
    public function filterByInstanceClass(string $className): TableCollection
    {
        return new static($this->filterArrayByInstanceClass($className));
    }

    /**
     * @param string $className
     * @return AbstractTable|null
     */
    public function filterOneByInstanceClass(string $className): ?AbstractTable
    {
        $tables = $this->filterArrayByInstanceClass($className);
        return count($tables) > 0 ? reset($tables) : null;
    }

    /**
     * @return AbstractTable[]
     */
    public function toArray(): array
    {
        return array_values($this->tables);
    }

    /**
     * @param string $className
     * @return AbstractTable[]
     */
    protected function filterArrayByInstanceClass(string $className): array
    {
        if (!class_exists($className)) {
            throw RuntimeException::classNotFound($className);
        }

        if (!is_a($className, AbstractTable::class, true)) {
            throw RuntimeException::classNotChildOfTable($className);
        }

        return array_filter($this->toArray(), function (AbstractTable $table) use ($className) {
            return $table instanceof $className;
        });
    }
}
