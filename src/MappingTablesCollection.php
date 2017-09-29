<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2016 JTL-Software GmbH
 */
namespace jtl\Connector\MappingTables;

class MappingTablesCollection
{
    /**
     * @var MappingTableInterface[]
     */
    protected $tables = [];

    /**
     * MappingTableCollection constructor.
     * @param MappingTableInterface[] $tables
     */
    public function __construct(array $tables = [])
    {
        foreach($tables as $table){
            $this->set($table);
        }
    }


    /**
     * @param MappingTableInterface $table
     * @return MappingTablesCollection
     */
    public function set(MappingTableInterface $table)
    {
        $this->tables[$table->getType()] = $table;
        return $this;
    }

    /**
     * @param MappingTableInterface $table
     * @return boolean
     */
    public function removeByInstance(MappingTableInterface $table)
    {
        $index = $table->getType();
        if(isset($this->tables[$index]) && ($this->tables[$index] === $table)) {
            unset($this->tables[$index]);
            return true;
        }
        return false;
    }

    /**
     * @param integer $type
     * @return boolean
     */
    public function removeByType($type)
    {
        if($this->has($type)) {
            unset($this->tables[$type]);
            return true;
        }
        return false;
    }

    /**
     * @param integer $type
     * @return boolean
     */
    public function has($type)
    {
        return isset($this->tables[$type]) && $this->tables[$type] instanceof MappingTableInterface;
    }

    /**
     * @param integer $type
     * @return MappingTableInterface
     * @throws \Exception
     */
    public function get($type)
    {
        if(!$this->has($type)) {
            throw MappingTableException::tableTypeNotFound($type);
        }
        return $this->tables[$type];
    }

    /**
     * @return MappingTableInterface[]
     */
    public function toArray()
    {
        return array_values($this->tables);
    }
}