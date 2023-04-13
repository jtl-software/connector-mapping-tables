<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc;

use Doctrine\DBAL\DBALException;
use Exception;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use Throwable;

class TableCollectionTest extends TestCase
{
    /**
     * @var TableCollection
     */
    protected TableCollection $collection;

    /**
     * @throws DBALException
     * @throws Exception
     */
    public function testSet(): void
    {
        $this->collection->set(new Table2Stub($this->getDBManager()));
        $this->assertCount(2, $this->collection->toArray());
    }

    /**
     * @throws Exception
     */
    public function testRemoveByInstance(): void
    {
        $this->assertCount(1, $this->collection->toArray());
        $this->assertTrue($this->collection->removeByInstance($this->table));
        $this->assertCount(0, $this->collection->toArray());
    }

    /**
     * @throws DBALException
     * @throws Exception
     * @throws Exception
     */
    public function testRemoveByInstanceNotFound(): void
    {
        $table = new TableStub($this->getDBManager());
        $this->assertCount(1, $this->collection->toArray());
        $this->assertFalse($this->collection->removeByInstance($table));
        $this->assertCount(1, $this->collection->toArray());
    }

    /**
     * @throws ExpectationFailedException
     * @throws DbcRuntimeException
     * @throws \PHPUnit\Framework\Exception
     * @throws InvalidArgumentException
     */
    public function testRemoveByName(): void
    {
        $this->assertCount(1, $this->collection->toArray());
        $this->assertTrue($this->collection->removeByName($this->table->getTableName()));
        $this->assertCount(0, $this->collection->toArray());
    }

    /**
     * @throws ExpectationFailedException
     * @throws \PHPUnit\Framework\Exception
     * @throws InvalidArgumentException
     */
    public function testRemoveByNameNotFound(): void
    {
        $this->assertCount(1, $this->collection->toArray());
        $this->assertFalse($this->collection->removeByName('yolooo!'));
        $this->assertCount(1, $this->collection->toArray());
    }

    /**
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws DbcRuntimeException
     */
    public function testHas(): void
    {
        $this->assertTrue($this->collection->has($this->table->getTableName()));
    }

    /**
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testHasNot(): void
    {
        $this->assertFalse($this->collection->has('foo'));
    }

    /**
     * @throws Exception
     */
    public function testGetSanchezful(): void
    {
        $table = $this->collection->get($this->table->getTableName());
        $this->assertEquals($this->table, $table);
    }

    /**
     * @throws Exception
     */
    public function testGetButNotFound(): void
    {
        $this->expectException(DbcRuntimeException::class);
        $this->expectExceptionCode(DbcRuntimeException::TABLE_NOT_FOUND);
        $this->collection->get('foobar');
    }

    /**
     * @throws DBALException
     * @throws Exception
     */
    public function testFilterByInstanceClass(): void
    {
        $tables[] = $this->table;
        $tables[] = new class ($this->getDBManager()) extends TableStub {
            public function getName(): string
            {
                return 'tableX';
            }
        };
        $tables[] = new Table2Stub($this->getDBManager());

        $collection = new TableCollection($tables);
        $filtered   = $collection->filterByInstanceClass(TableStub::class);

        $this->assertInstanceOf(TableCollection::class, $filtered);
        $this->assertNotEquals($collection, $filtered);
        $this->assertCount(2, $filtered->toArray());
    }

    public function testFilterByInstanceClassNotFound(): void
    {
        $this->expectException(DbcRuntimeException::class);
        $this->expectExceptionCode(DbcRuntimeException::CLASS_NOT_FOUND);
        $this->collection->filterByInstanceClass('notexistent');
    }

    public function testFilterByInstanceClassNotAChildOfAbstractTable(): void
    {
        $this->expectException(DbcRuntimeException::class);
        $this->expectExceptionCode(DbcRuntimeException::CLASS_NOT_A_TABLE);
        $this->collection->filterByInstanceClass(\ArrayIterator::class);
    }

    /**
     * @throws DBALException
     * @throws Exception
     */
    public function testFilterOneByInstanceClass(): void
    {
        $t2Stub = new Table2Stub($this->getDBManager());
        $this->collection->set($t2Stub);

        $expected = $this->table;
        $actual   = $this->collection->filterOneByInstanceClass(AbstractTable::class);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws InvalidArgumentException
     * @throws DbcRuntimeException
     * @throws ExpectationFailedException
     */
    public function testFilterOneByInstanceClassReturnNull(): void
    {
        $actual = $this->collection->filterOneByInstanceClass(Table2Stub::class);
        $this->assertNull($actual);
    }

    /**
     * @throws DBALException
     * @throws Throwable
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->table = new TableStub($this->getDBManager());
        parent::setUp();
        $this->collection = new TableCollection([$this->table]);
        $this->insertFixtures($this->table, self::getTableStubFixtures());
    }
}
