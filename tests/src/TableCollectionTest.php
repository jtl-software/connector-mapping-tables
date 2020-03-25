<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */
namespace Jtl\Connector\MappingTables;

class TableCollectionTest extends DbTestCase
{
    /**
     * @var TableCollection
     */
    protected $collection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = new TableCollection($this->table);
    }

    public function toArray()
    {
        $collection = new TableCollection($this->table);
        $tables = $collection->toArray();
        $this->assertCount(1, $tables);
        $this->assertEquals($this->table, $tables[0]);
    }

    public function testSetAndGet()
    {
        $collection = new TableCollection();
        $this->assertCount(0, $collection->toArray());
        $collection->set($this->table);
        $table = $collection->get(TableStub::TYPE1);
        $this->assertInstanceOf(TableStub::class, $table);
        $this->assertEquals($this->table, $table);
    }

    public function testHas()
    {
        $collection = new TableCollection($this->table);
        $this->assertTrue($collection->has(TableStub::TYPE1));
    }

    public function testHasNot()
    {
        $collection = new TableCollection($this->table);
        $this->assertFalse($collection->has(9854));
    }

    public function testRemoveByType()
    {
        $collection = new TableCollection($this->table);
        $this->assertEquals($this->table, $collection->get(TableStub::TYPE1));
        $collection->removeByType(TableStub::TYPE1);
        $this->assertCount(0, $collection->toArray());
    }

    public function testRemoveByInstance()
    {
        $collection = new TableCollection($this->table);
        $this->assertEquals($this->table, $collection->get(TableStub::TYPE1));
        $collection->removeByInstance($this->table);
        $this->assertCount(0, $collection->toArray());
    }

    public function testGetNotExistingTableWithStrictModeEnabled()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(RuntimeException::TABLE_TYPE_NOT_FOUND);
        $collection = new TableCollection($this->table);
        $collection->setStrictMode(true);
        $collection->get(12434);
    }

    public function testGetNotExistingTableWithStrictModeDisabled()
    {
        $type = 73443534;
        $collection = new TableCollection($this->table);
        $collection->setStrictMode(false);
        $this->assertFalse($collection->has($type));
        $table = $collection->get($type);
        $this->assertInstanceOf(TableDummy::class, $table);
    }
}
