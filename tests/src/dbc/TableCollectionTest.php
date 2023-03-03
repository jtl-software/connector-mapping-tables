<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2018 JTL-Software GmbH
 */

namespace Jtl\Connector\Dbc;

class TableCollectionTest extends TestCase
{
    /**
     * @var TableCollection
     */
    protected $collection;

    protected function setUp(): void
    {
        $this->table = new TableStub($this->getDBManager());
        parent::setUp();
        $this->collection = new TableCollection([$this->table]);
        $this->insertFixtures($this->table, self::getTableStubFixtures());
    }

    public function testSet()
    {
        $this->collection->set(new Table2Stub($this->getDBManager()));
        $this->assertCount(2, $this->collection->toArray());
    }

    public function testRemoveByInstance()
    {
        $this->assertCount(1, $this->collection->toArray());
        $this->assertTrue($this->collection->removeByInstance($this->table));
        $this->assertCount(0, $this->collection->toArray());
    }

    public function testRemoveByInstanceNotFound()
    {
        $table = new TableStub($this->getDBManager());
        $this->assertCount(1, $this->collection->toArray());
        $this->assertFalse($this->collection->removeByInstance($table));
        $this->assertCount(1, $this->collection->toArray());
    }

    public function testRemoveByName()
    {
        $this->assertCount(1, $this->collection->toArray());
        $this->assertTrue($this->collection->removeByName($this->table->getTableName()));
        $this->assertCount(0, $this->collection->toArray());
    }

    public function testRemoveByNameNotFound()
    {
        $this->assertCount(1, $this->collection->toArray());
        $this->assertFalse($this->collection->removeByName('yolooo!'));
        $this->assertCount(1, $this->collection->toArray());
    }

    public function testHas()
    {
        $this->assertTrue($this->collection->has($this->table->getTableName()));
    }

    public function testHasNot()
    {
        $this->assertFalse($this->collection->has('foo'));
    }

    public function testGetSanchezful()
    {
        $table = $this->collection->get($this->table->getTableName());
        $this->assertEquals($this->table, $table);
    }

    public function testGetButNotFound()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(RuntimeException::TABLE_NOT_FOUND);
        $this->collection->get('foobar');
    }

    public function testFilterByInstanceClass()
    {
        $tables[] = $this->table;
        $tables[] = new class($this->getDBManager()) extends TableStub {
            public function getName(): string
            {
                return 'tableX';
            }
        };
        $tables[] = new Table2Stub($this->getDBManager());

        $collection = new TableCollection($tables);
        $filtered = $collection->filterByInstanceClass(TableStub::class);

        $this->assertInstanceOf(TableCollection::class, $filtered);
        $this->assertNotEquals($collection, $filtered);
        $this->assertCount(2, $filtered->toArray());
    }

    public function testFilterByInstanceClassNotFound()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(RuntimeException::CLASS_NOT_FOUND);
        $this->collection->filterByInstanceClass('notexistent');
    }

    public function testFilterByInstanceClassNotAChildOfAbstractTable()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(RuntimeException::CLASS_NOT_A_TABLE);
        $this->collection->filterByInstanceClass(\ArrayIterator::class);
    }

    public function testFilterOneByInstanceClass()
    {
        $t2Stub = new Table2Stub($this->getDBManager());
        $this->collection->set($t2Stub);

        $expected = $this->table;
        $actual = $this->collection->filterOneByInstanceClass(AbstractTable::class);
        $this->assertEquals($expected, $actual);
    }

    public function testFilterOneByInstanceClassReturnNull()
    {
        $actual = $this->collection->filterOneByInstanceClass(Table2Stub::class);
        $this->assertNull($actual);
    }
}
