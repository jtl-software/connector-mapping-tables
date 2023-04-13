<?php

declare(strict_types=1);

namespace Jtl\Connector\MappingTables;

use Doctrine\DBAL\DBALException;
use Exception;
use Jtl\Connector\Dbc\DbcRuntimeException;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use Throwable;

class TableManagerTest extends TestCase
{
    /**
     * @var TableManager
     */
    protected TableManager $mtm;

    /**
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testGetMappingTable(): void
    {
        $this->assertInstanceOf(TableStub::class, $this->mtm->getTableByType(TableStub::TYPE1));
        $this->assertInstanceOf(TableStub::class, $this->mtm->getTableByType(TableStub::TYPE2));
    }

    /**
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     */
    public function testGetHostId(): void
    {
        $expected = 5;
        $endpoint = \sprintf('4||2||foobar||%s', TableStub::TYPE1);
        $actual   = $this->mtm->getHostId(TableStub::TYPE1, $endpoint);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     */
    public function testGetEndpointId(): void
    {
        $expected = \sprintf('1||2||bar||%s', TableStub::TYPE2);
        $actual   = $this->mtm->getEndpointId(TableStub::TYPE2, 2);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     */
    public function testSave(): void
    {
        $endpoint = \sprintf('1||8||asdf||%s', TableStub::TYPE1);
        $hostId   = 9;
        $this->mtm->save(TableStub::TYPE1, $endpoint, $hostId);
        $this->assertEquals($hostId, $this->mtm->getHostId(TableStub::TYPE1, $endpoint));
        $this->assertEquals($endpoint, $this->mtm->getEndpointId(TableStub::TYPE1, $hostId));
    }

    /**
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     */
    public function testDeleteByEndpointId(): void
    {
        $endpoint = \sprintf('1||2||bar||%s', TableStub::TYPE2);
        $this->mtm->delete(TableStub::TYPE2, $endpoint);
        $this->assertNull($this->mtm->getHostId(TableStub::TYPE2, $endpoint));
    }

    /**
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     */
    public function testDeleteByHostId(): void
    {
        $hostId = 3;
        $this->mtm->delete(TableStub::TYPE1, null, $hostId);
        $this->assertNull($this->mtm->getEndpointId(TableStub::TYPE1, $hostId));
    }

    /**
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testFindAllEndpointsIds(): void
    {
        $this->assertCount(3, $this->mtm->findAllEndpointIds(TableStub::TYPE1));
        $this->assertCount(1, $this->mtm->findAllEndpointIds(TableStub::TYPE2));
    }

    /**
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testFindNotFetchedEndpoints(): void
    {
        $fetched = [
            \sprintf('1||1||foo||%s', TableStub::TYPE1),
            \sprintf('1||2||bar||%s', TableStub::TYPE2),
        ];

        $notFetchedExpected = [
            \sprintf('2||1||yo||%s', TableStub::TYPE1),
            \sprintf('2||2||lo||%s', TableStub::TYPE1),
            \sprintf('2||3||so||%s', TableStub::TYPE1),
        ];

        $endpoints = \array_merge($fetched, $notFetchedExpected);

        $notFetchedActual = $this->mtm->filterMappedEndpointIds(TableStub::TYPE1, $endpoints);
        $this->assertCount(3, $notFetchedActual);
        $this->assertEquals($notFetchedExpected, $notFetchedActual);
    }

    /**
     * @throws DBALException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     * @throws DbcRuntimeException
     * @throws \RuntimeException
     */
    public function testClear(): void
    {
        $this->assertEquals(4, $this->countRows($this->table->getTableName()));
        $this->assertTrue($this->mtm->clear());
        $this->assertEquals(0, $this->countRows($this->table->getTableName()));
    }

    /**
     * @throws DBALException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     * @throws DbcRuntimeException
     * @throws \RuntimeException
     */
    public function testClearByType(): void
    {
        $this->assertEquals(4, $this->countRows($this->table->getTableName()));
        $this->assertTrue($this->mtm->clear(TableStub::TYPE1));
        $this->assertEquals(1, $this->countRows($this->table->getTableName()));
        $this->assertTrue($this->mtm->clear(TableStub::TYPE1));
        $this->assertEquals(1, $this->countRows($this->table->getTableName()));
        $this->assertTrue($this->mtm->clear(TableStub::TYPE2));
        $this->assertEquals(0, $this->countRows($this->table->getTableName()));
    }

    /**
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     */
    public function testCount(): void
    {
        $this->assertEquals(3, $this->mtm->count(TableStub::TYPE1));
    }

    /**
     * @throws MappingTablesException
     * @throws \PHPUnit\Framework\Exception
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     */
    public function testGetNotExistingTableWithStrictModeDisabled(): void
    {
        $this->mtm->setStrictMode(false);
        $type  = 234234236;
        $table = $this->mtm->getTableByType($type);
        $this->assertInstanceOf(TableDummy::class, $table);
    }

    public function testGetNotExistingTableWithStrictModeEnabled(): void
    {
        $this->expectException(MappingTablesException::class);
        $this->expectExceptionCode(MappingTablesException::TABLE_FOR_TYPE_NOT_FOUND);
        $this->mtm->setStrictMode(true);
        $this->mtm->getTableByType(7217641241);
    }

    /**
     * @throws DBALException
     * @throws Throwable
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->table = new TableStub($this->getDbManager());
        $this->mtm   = new TableManager($this->table);
        parent::setUp();
        $this->insertFixtures($this->table, self::getTableStubFixtures());
    }
}
