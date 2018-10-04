<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */
namespace jtl\Connector\MappingTables;


class MappingTablesCollectionTest extends DBTestCase
{
    /**
     * @var MappingTablesCollection
     */
    protected $collection;

    protected function setUp()
    {
        parent::setUp();
        $this->collection = new MappingTablesCollection([$this->table]);
    }

    public function toArray()
    {
        $collection = new MappingTablesCollection([$this->table]);
        $tables = $collection->toArray();
        $this->assertCount(1, $tables);
        $this->assertEquals($this->table, $tables[0]);
    }

    public function testSetAndGet()
    {
        $collection = new MappingTablesCollection();
        $this->assertCount(0, $collection->toArray());
        $collection->set($this->table);
        $table = $collection->get($this->table->getType());
        $this->assertInstanceOf(MappingTableStub::class, $table);
        $this->assertEquals($this->table, $table);
    }

    public function testHas()
    {
        $collection = new MappingTablesCollection([$this->table]);
        $this->assertTrue($collection->has($this->table->getType()));
    }

    public function testHasNot()
    {
        $collection = new MappingTablesCollection([$this->table]);
        $this->assertFalse($collection->has('whatever'));
    }

    public function testGetNotFound()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(RuntimeException::TABLE_TYPE_NOT_FOUND);
        (new MappingTablesCollection([$this->table]))->get('yeeeha');
    }

    public function testRemoveByType()
    {
        $collection = new MappingTablesCollection([$this->table]);
        $this->assertEquals($this->table, $collection->get($this->table->getType()));
        $collection->removeByType($this->table->getType());
        $this->assertCount(0, $collection->toArray());
    }

    public function testRemoveByInstance()
    {
        $collection = new MappingTablesCollection([$this->table]);
        $this->assertEquals($this->table, $collection->get($this->table->getType()));
        $collection->removeByInstance($this->table);
        $this->assertCount(0, $collection->toArray());
    }

    public function testGetNotExistingTableWithStrictModeDisabled()
    {
        $type = 73443534;
        $collection = new MappingTablesCollection([$this->table], false);
        $this->assertFalse($collection->has($type));
        $table = $collection->get($type);
        $this->assertInstanceOf(DummyTable::class, $table);
    }
}
