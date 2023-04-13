<?php

declare(strict_types=1);

namespace Jtl\Connector\MappingTables;

use Doctrine\DBAL\DBALException;
use Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\ClassAlreadyExistsException;
use PHPUnit\Framework\MockObject\ClassIsFinalException;
use PHPUnit\Framework\MockObject\ClassIsReadonlyException;
use PHPUnit\Framework\MockObject\DuplicateMethodException;
use PHPUnit\Framework\MockObject\InvalidMethodNameException;
use PHPUnit\Framework\MockObject\OriginalConstructorInvocationRequiredException;
use PHPUnit\Framework\MockObject\ReflectionException;
use PHPUnit\Framework\MockObject\RuntimeException;
use PHPUnit\Framework\MockObject\UnknownTypeException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use Throwable;

class TableCollectionTest extends TestCase
{
    /**
     * @var TableCollection
     */
    protected TableCollection $collection;

    /**
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testToArray(): void
    {
        $this->assertInstanceOf(TableStub::class, $this->table);
        $collection = new TableCollection($this->table);
        $tables     = $collection->toArray();
        $this->assertCount(1, $tables);
        $this->assertEquals($this->table, $tables[0]);
    }

    /**
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testSetAndGet(): void
    {
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub  = $this->table;
        $collection = new TableCollection();
        $this->assertCount(0, $collection->toArray());
        $collection->set($tableStub);
        $table = $collection->get(TableStub::TYPE1);
        $this->assertInstanceOf(TableStub::class, $table);
        $this->assertEquals($tableStub, $table);
    }

    /**
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException|\PHPUnit\Framework\Exception
     */
    public function testHas(): void
    {
        $this->assertInstanceOf(TableStub::class, $this->table);
        $collection = new TableCollection($this->table);
        $this->assertTrue($collection->has(TableStub::TYPE1));
    }

    /**
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException|\PHPUnit\Framework\Exception
     */
    public function testHasNot(): void
    {
        $this->assertInstanceOf(TableStub::class, $this->table);
        $collection = new TableCollection($this->table);
        $this->assertFalse($collection->has(9854));
    }

    /**
     * @throws InvalidMethodNameException
     * @throws RuntimeException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws InvalidArgumentException
     * @throws ClassIsFinalException
     * @throws ExpectationFailedException
     * @throws \PHPUnit\Framework\InvalidArgumentException
     * @throws DuplicateMethodException
     * @throws ClassIsReadonlyException
     * @throws ReflectionException
     * @throws UnknownTypeException
     * @throws \PHPUnit\Framework\Exception
     * @throws ClassAlreadyExistsException
     */
    public function testRemoveByType(): void
    {
        $table1 = $this->createStub(TableInterface::class);
        $table1->method('getTypes')->willReturn([1, 2, 3]);

        $table2 = $this->createStub(TableInterface::class);
        $table2->method('getTypes')->willReturn([4]);

        $collection = new TableCollection($table1, $table2);
        $collection->removeByType(4);
        $this->assertCount(1, $collection->toArray());

        $this->assertSame($table1, $collection->toArray()[0]);
    }

    /**
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     * @throws \PHPUnit\Framework\InvalidArgumentException
     * @throws ClassAlreadyExistsException
     * @throws ClassIsFinalException
     * @throws ClassIsReadonlyException
     * @throws DuplicateMethodException
     * @throws InvalidMethodNameException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws ReflectionException
     * @throws RuntimeException
     * @throws UnknownTypeException
     * @throws Exception
     * @throws Exception
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

    /**
     * @return void
     * @throws \Jtl\Connector\MappingTables\MappingTablesException
     * @throws \PHPUnit\Framework\Exception
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */

    public function testGetNotExistingTableWithStrictModeEnabled(): void
    {
        $this->expectException(MappingTablesException::class);
        $this->expectExceptionCode(MappingTablesException::TABLE_FOR_TYPE_NOT_FOUND);
        $this->assertInstanceOf(TableStub::class, $this->table);
        $collection = new TableCollection($this->table);
        $collection->setStrictMode(true);
        $collection->get(12434);
    }

    /**
     * @throws MappingTablesException
     * @throws \PHPUnit\Framework\Exception
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     */
    public function testGetNotExistingTableWithStrictModeDisabled(): void
    {
        $type = 73443534;
        $this->assertInstanceOf(TableStub::class, $this->table);
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
        $this->table = new TableStub($this->getDbManager());
        $this->assertInstanceOf(TableStub::class, $this->table);
        $this->collection = new TableCollection($this->table);
        parent::setUp();
        $this->insertFixtures($this->table, self::getTableStubFixtures());
    }
}
