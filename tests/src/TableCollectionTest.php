<?php

declare(strict_types=1);

namespace Jtl\Connector\MappingTables;

use Doctrine\DBAL\DBALException;
use Exception;
use Throwable;

class TableCollectionTest extends TestCase
{
    /**
     * @var TableStub
     */
    protected \Jtl\Connector\Dbc\AbstractTable $table;

    /**
     * @var TableCollection
     */
    protected TableCollection $collection;

    public function testToArray(): void
    {
        $collection = new TableCollection($this->table);
        $tables     = $collection->toArray();
        $this->assertCount(1, $tables);
        $this->assertEquals($this->table, $tables[0]);
    }

    /**
     * @throws MappingTablesException
     */
    public function testSetAndGet(): void
    {
        $collection = new TableCollection();
        $this->assertCount(0, $collection->toArray());
        $collection->set($this->table);
        $table = $collection->get(TableStub::TYPE1);
        $this->assertInstanceOf(TableStub::class, $table);
        $this->assertEquals($this->table, $table);
    }

    public function testHas(): void
    {
        $collection = new TableCollection($this->table);
        $this->assertTrue($collection->has(TableStub::TYPE1));
    }

    public function testHasNot(): void
    {
        $collection = new TableCollection($this->table);
        $this->assertFalse($collection->has(9854));
    }

    public function testRemoveByType(): void
    {
        $table1 = $this->createStub(TableInterface::class);
        $table1->method('getTypes')->willReturn([1, 2, 3]);

        $table2 = $this->createStub(TableInterface::class);
        $table2->method('getTypes')->willReturn([4]);

        $collection = new TableCollection($table1, $table2);
        $collection->removeByType(4);
        $this->assertCount(1, $collection->toArray());

        $this->assertTrue($table1 === $collection->toArray()[0]);
    }

    /**
     * @throws MappingTablesException
     */
    public function testRemoveByInstance(): void
    {
        $table = $this->createStub(TableInterface::class);
        $table->method('getTypes')->willReturn([1, 2, 3, 4, 5, 6, 7, 8, 9]);

        $collection = new TableCollection($table);
        $this->assertEquals($table, $collection->get(\random_int(1, 9)));
        $collection->removeByInstance($table);
        $this->assertFalse($collection->has(\random_int(1, 9)));
    }

    public function testGetNotExistingTableWithStrictModeEnabled(): void
    {
        $this->expectException(MappingTablesException::class);
        $this->expectExceptionCode(MappingTablesException::TABLE_FOR_TYPE_NOT_FOUND);
        $collection = new TableCollection($this->table);
        $collection->setStrictMode(true);
        $collection->get(12434);
    }

    /**
     * @throws MappingTablesException
     */
    public function testGetNotExistingTableWithStrictModeDisabled(): void
    {
        $type       = 73443534;
        $collection = new TableCollection($this->table);
        $collection->setStrictMode(false);
        $this->assertFalse($collection->has($type));
        $table = $collection->get($type);
        $this->assertInstanceOf(TableDummy::class, $table);
    }

    /**
     * @throws DBALException
     * @throws Throwable
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->table      = new TableStub($this->getDbManager());
        $this->collection = new TableCollection($this->table);
        parent::setUp();
        $this->insertFixtures($this->table, self::getTableStubFixtures());
    }
}
