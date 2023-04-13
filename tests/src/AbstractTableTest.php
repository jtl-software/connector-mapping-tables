<?php

/** @noinspection PhpHierarchyChecksInspection */

declare(strict_types=1);

namespace Jtl\Connector\MappingTables;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Types;
use Jtl\Connector\Dbc\DbManager;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\ClassAlreadyExistsException;
use PHPUnit\Framework\MockObject\ClassIsFinalException;
use PHPUnit\Framework\MockObject\ClassIsReadonlyException;
use PHPUnit\Framework\MockObject\DuplicateMethodException;
use PHPUnit\Framework\MockObject\IncompatibleReturnValueException;
use PHPUnit\Framework\MockObject\InvalidMethodNameException;
use PHPUnit\Framework\MockObject\MethodCannotBeConfiguredException;
use PHPUnit\Framework\MockObject\MethodNameAlreadyConfiguredException;
use PHPUnit\Framework\MockObject\OriginalConstructorInvocationRequiredException;
use PHPUnit\Framework\MockObject\RuntimeException;
use PHPUnit\Framework\MockObject\UnknownTypeException;
use ReflectionException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use Throwable;

class AbstractTableTest extends TestCase
{
    /**
     * @throws DBALException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws \Jtl\Connector\Dbc\DbcRuntimeException
     * @throws \RuntimeException
     */
    public function testTableSchema(): void
    {
        /** @var TableStub $tableStub */
        $tableStub   = $this->table;
        $tableSchema = $tableStub->getTableSchema();
        $this->assertTrue($tableSchema->hasColumn(AbstractTable::HOST_ID));
        $this->assertTrue($tableSchema->hasColumn(TableStub::COL_ID1));
        $this->assertTrue($tableSchema->hasColumn(TableStub::COL_ID2));
        $this->assertTrue($tableSchema->hasColumn(TableStub::COL_VAR));
    }

    /**
     * @throws DBALException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws SchemaException
     * @throws \Jtl\Connector\Dbc\DbcRuntimeException
     * @throws \PHPUnit\Framework\Exception
     * @throws \RuntimeException
     */
    public function testHostIndex(): void
    {
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $table */
        $table       = $this->table;
        $tableSchema = $table->getTableSchema();
        $this->assertTrue($tableSchema->hasIndex($table->createIndexName(AbstractTable::HOST_INDEX_NAME)));
        $hostIndex   = $tableSchema->getIndex($table->createIndexName(AbstractTable::HOST_INDEX_NAME));
        $hostColumns = $hostIndex->getColumns();
        $this->assertCount(1, $hostColumns);
        /** @var Column $hostColumn */
        $hostColumn = \reset($hostColumns);
        $this->assertEquals(AbstractTable::HOST_ID, $hostColumn);
    }

    /**
     * @throws DBALException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws SchemaException
     * @throws \Jtl\Connector\Dbc\DbcRuntimeException
     * @throws \PHPUnit\Framework\Exception
     * @throws \RuntimeException
     */
    public function testPrimaryIndex(): void
    {
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub   = $this->table;
        $tableSchema = $tableStub->getTableSchema();
        $this->assertTrue($tableSchema->hasPrimaryKey());
        $primaryKey = $tableSchema->getPrimaryKey();
        $this->assertNotNull($primaryKey);
        $primaryColumns = $primaryKey->getColumns();
        $this->assertCount(3, $primaryColumns);
        $this->assertEquals(TableStub::COL_ID1, $primaryColumns[0]);
        $this->assertEquals(TableStub::COL_ID2, $primaryColumns[1]);
        $this->assertEquals(AbstractTable::IDENTITY_TYPE, $primaryColumns[2]);
    }

    /**
     * @throws DBALException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws SchemaException
     * @throws \Jtl\Connector\Dbc\DbcRuntimeException
     * @throws \PHPUnit\Framework\Exception
     * @throws \RuntimeException
     */
    public function testEndpointIndex(): void
    {
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub   = $this->table;
        $tableSchema = $tableStub->getTableSchema();
        $this->assertTrue($tableSchema->hasIndex($tableStub->createIndexName(AbstractTable::ENDPOINT_INDEX_NAME)));
        $epIndex   = $tableSchema->getIndex($tableStub->createIndexName(AbstractTable::ENDPOINT_INDEX_NAME));
        $epColumns = $epIndex->getColumns();
        $this->assertCount(4, $epColumns);
        $this->assertEquals(TableStub::COL_ID1, $epColumns[0]);
        $this->assertEquals(TableStub::COL_ID2, $epColumns[1]);
        $this->assertEquals(TableStub::COL_VAR, $epColumns[2]);
        $this->assertEquals(AbstractTable::IDENTITY_TYPE, $epColumns[3]);
    }

    /**
     * @throws DBALException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \RuntimeException
     */
    public function testGetHostId(): void
    {
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub = $this->table;
        $this->assertEquals(3, $tableStub->getHostId(\sprintf('1||1||foo||%s', TableStub::TYPE1)));
        $this->assertEquals(2, $tableStub->getHostId(\sprintf('1||2||bar||%s', TableStub::TYPE2)));
        $this->assertEquals(5, $tableStub->getHostId(\sprintf('4||2||foobar||%s', TableStub::TYPE1)));
    }

    /**
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     * @throws \Jtl\Connector\Dbc\DbcRuntimeException
     * @throws \RuntimeException
     */
    public function testGetEndpointId(): void
    {
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub = $this->table;
        $this->assertEquals(
            \sprintf('1||1||foo||%s', TableStub::TYPE1),
            $tableStub->getEndpoint(3, TableStub::TYPE1)
        );
        $this->assertEquals(
            \sprintf('1||2||bar||%s', TableStub::TYPE2),
            $tableStub->getEndpoint(2, TableStub::TYPE2)
        );
        $this->assertEquals(
            \sprintf('4||2||foobar||%s', TableStub::TYPE1),
            $tableStub->getEndpoint(5, TableStub::TYPE1)
        );
    }

    /**
     * @throws DBALException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     * @throws \Jtl\Connector\Dbc\DbcRuntimeException
     * @throws \RuntimeException
     */
    public function testSave(): void
    {
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub = $this->table;
        $tableStub->save(\sprintf('1||45||yolo||%s', TableStub::TYPE1), 4);
        $this->assertEquals(5, $this->countRows($tableStub->getTableName()));
    }

    /**
     * @throws DBALException
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     * @throws \Jtl\Connector\Dbc\DbcRuntimeException
     * @throws \RuntimeException
     */
    public function testDeleteByEndpointId(): void
    {
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub = $this->table;
        $this->assertEquals(
            \sprintf('1||1||foo||%s', TableStub::TYPE1),
            $tableStub->getEndpoint(3, TableStub::TYPE1)
        );
        $tableStub->remove(\sprintf('1||1||foo||%s', TableStub::TYPE1), null, TableStub::TYPE1);
        $this->assertEquals(3, $this->countRows($tableStub->getTableName()));
        $this->assertEquals(null, $tableStub->getEndpoint(3, TableStub::TYPE1));
    }

    /**
     * @throws DBALException
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     * @throws \Jtl\Connector\Dbc\DbcRuntimeException
     * @throws \RuntimeException
     */
    public function testDeleteByHostId(): void
    {
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub = $this->table;
        $this->assertEquals(
            \sprintf('1||1||foo||%s', TableStub::TYPE1),
            $tableStub->getEndpoint(3, TableStub::TYPE1)
        );
        $tableStub->remove(null, 3, TableStub::TYPE1);
        $this->assertEquals(3, $this->countRows($tableStub->getTableName()));
        $this->assertEquals(null, $tableStub->getEndpoint(3, TableStub::TYPE1));
    }

    /**
     * @dataProvider deleteByHostIdMultipleEntriesProvider
     *
     * @param string|null $endpoint
     *
     * @throws DBALException
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Jtl\Connector\Dbc\DbcRuntimeException
     * @throws \RuntimeException
     */
    public function testDeleteByHostIdMultipleEntries(?string $endpoint): void
    {
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub       = $this->table;
        $relatedHostId   = 5;
        $anotherEndpoint = \sprintf('5||7||wat||%s', TableStub::TYPE1);
        $tableStub->save($anotherEndpoint, $relatedHostId);
        $this->assertEquals(5, $this->countRows($tableStub->getTableName()));
        $this->assertEquals($relatedHostId, $tableStub->getHostId($anotherEndpoint));
        $tableStub->remove($endpoint, $relatedHostId, TableStub::TYPE1);
        $this->assertEquals(2, $this->countRows($tableStub->getTableName()));
        $this->assertNull($tableStub->getEndpoint($relatedHostId, TableStub::TYPE1));
        $this->assertNull($tableStub->getHostId($anotherEndpoint));
    }

    /**
     * @return array{0: array{null}, 1: array{string}}
     */
    public function deleteByHostIdMultipleEntriesProvider(): array
    {
        return [
            [null],
            [\sprintf('1||1||foo||%s', TableStub::TYPE1)],
        ];
    }

    /**
     * @throws DBALException
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     * @throws \Jtl\Connector\Dbc\DbcRuntimeException
     * @throws \RuntimeException
     */
    public function testClearDifferentTypes(): void
    {
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub = $this->table;
        $tableStub->clear(TableStub::TYPE1);
        $this->assertEquals(1, $this->countRows($tableStub->getTableName()));
        $tableStub->clear(TableStub::TYPE2);
        $this->assertEquals(0, $this->countRows($tableStub->getTableName()));
    }

    /**
     * @throws DBALException
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     * @throws \Jtl\Connector\Dbc\DbcRuntimeException
     * @throws \RuntimeException
     */
    public function testClearAll(): void
    {
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub = $this->table;
        $this->assertEquals(4, $this->countRows($tableStub->getTableName()));
        $tableStub->clear();
        $this->assertEquals(0, $this->countRows($tableStub->getTableName()));
    }

    /**
     * @throws Exception
     * @throws MappingTablesException
     * @throws \Jtl\Connector\Dbc\DbcRuntimeException
     * @throws \RuntimeException|\SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testClearUnknownType(): void
    {
        $this->expectException(MappingTablesException::class);
        $this->expectExceptionCode(MappingTablesException::TABLE_NOT_RESPONSIBLE_FOR_TYPE);
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub = $this->table;
        $tableStub->clear(44232);
    }

    /**
     * @throws DBALException
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Jtl\Connector\Dbc\DbcRuntimeException
     * @throws \RuntimeException
     */
    public function testCount(): void
    {
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub = $this->table;
        $this->assertEquals(4, $this->countRows($tableStub->getTableName()));
        $this->assertEquals(3, $tableStub->count([], [], [], null, null, TableStub::TYPE1));
        $this->assertEquals(1, $tableStub->count([], [], [], null, null, TableStub::TYPE2));
        $tableStub->remove(\sprintf('1||1||foo||%s', TableStub::TYPE1), null, TableStub::TYPE1);
        $this->assertEquals(3, $this->countRows($tableStub->getTableName()));
        $this->assertEquals(2, $tableStub->count([], [], [], null, null, TableStub::TYPE1));
    }

    /**
     * @throws DBALException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \RuntimeException
     */
    public function testCountWithWhereCondition(): void
    {
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub = $this->table;
        $where     = [TableStub::COL_ID2 . ' = :' . TableStub::COL_ID2];
        $this->assertEquals(
            0,
            $tableStub->count($where, [TableStub::COL_ID2 => 63], [], null, null, TableStub::TYPE1)
        );
        $this->assertEquals(
            1,
            $tableStub->count($where, [TableStub::COL_ID2 => 1], [], null, null, TableStub::TYPE1)
        );
        $this->assertEquals(
            1,
            $tableStub->count($where, [TableStub::COL_ID2 => 2], [], null, null, TableStub::TYPE1)
        );
    }

    /**
     * @throws DBALException
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     * @throws \PHPUnit\Framework\Exception
     * @throws \RuntimeException
     */
    public function testFindEndpointsByType(): void
    {
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub = $this->table;
        $endpoints = $tableStub->findEndpoints([], [], [], null, null, TableStub::TYPE1);
        $this->assertCount(3, $endpoints);
        $this->assertEquals(\sprintf('1||1||foo||%s', TableStub::TYPE1), $endpoints[0]);
        $this->assertEquals(\sprintf('4||2||foobar||%s', TableStub::TYPE1), $endpoints[1]);
        $endpoints = $tableStub->findEndpoints([], [], [], null, null, TableStub::TYPE2);
        $this->assertCount(1, $endpoints);
        $this->assertEquals(\sprintf('1||2||bar||%s', TableStub::TYPE2), $endpoints[0]);
    }

    /**
     * @throws DBALException
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     * @throws \Jtl\Connector\Dbc\DbcRuntimeException
     * @throws \RuntimeException
     */
    public function testFindAllEndpointsWithNoData(): void
    {
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub = $this->table;
        $tableStub->clear(TableStub::TYPE1);
        $endpoints = $tableStub->findEndpoints([], [], [], null, null, TableStub::TYPE1);
        $this->assertIsArray($endpoints);
        $this->assertEmpty($endpoints);
    }

    /**
     * @throws DBALException
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     * @throws \Jtl\Connector\Dbc\DbcRuntimeException
     * @throws \PHPUnit\Framework\Exception
     * @throws \RuntimeException
     */
    public function testFilterMappedEndpoints(): void
    {
        $mapped = [
            \sprintf('1||1||foo||%s', TableStub::TYPE1),
            \sprintf('1||2||bar||%s', TableStub::TYPE2),
        ];

        $notMappedExpected = [
            \sprintf('2||1||yolo||%s', TableStub::TYPE1),
            \sprintf('2||2||dito||%s', TableStub::TYPE1),
            \sprintf('2||3||mito||%s', TableStub::TYPE1)
        ];

        $endpoints = \array_merge($mapped, $notMappedExpected);

        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub       = $this->table;
        $notMappedActual = $tableStub->filterMappedEndpoints($endpoints);
        $this->assertCount(3, $notMappedActual);
        $this->assertEquals($notMappedExpected, $notMappedActual);
    }

    /**
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testCreateEndpointData(): void
    {
        $endpointData = ['5', '7', 'foobar', TableStub::TYPE1];
        $this->assertInstanceOf(TableStub::class, $this->table);
        $data = $this->invokeMethodFromObject($this->table, 'createEndpointData', $endpointData);
        $this->assertIsArray($data);
        $this->assertCount(4, $data);
        $this->assertArrayHasKey('id1', $data);
        $this->assertIsInt($data['id1']);
        $this->assertArrayHasKey('id2', $data);
        $this->assertIsInt($data['id2']);
        $this->assertArrayHasKey('strg', $data);
        $this->assertIsString($data['strg']);
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\Exception
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \ReflectionException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testCreateEndpointDataFailsTooMuchData(): void
    {
        $this->expectException(MappingTablesException::class);
        $this->expectExceptionCode(MappingTablesException::WRONG_ENDPOINT_PARTS_AMOUNT);
        $endpointData = ['foo', 'bar', '123', '21', '1.3'];
        $this->assertInstanceOf(TableStub::class, $this->table);
        $this->invokeMethodFromObject($this->table, 'createEndpointData', $endpointData);
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\Exception
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \ReflectionException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testCreateEndpointDataFailsNotEnoughData(): void
    {
        $this->expectException(MappingTablesException::class);
        $this->expectExceptionCode(MappingTablesException::WRONG_ENDPOINT_PARTS_AMOUNT);
        $endpointData = ['foo', 'bar'];
        $this->assertInstanceOf(TableStub::class, $this->table);
        $this->invokeMethodFromObject($this->table, 'createEndpointData', $endpointData);
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\Exception
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testBuildEndpoint(): void
    {
        $data = ['f', 'u', 'c', 'k'];
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub = $this->table;
        $expected  = \implode($tableStub->getEndpointDelimiter(), $data);
        $endpoint  = $tableStub->buildEndpoint($data);
        $this->assertEquals($expected, $endpoint);
    }

    /**
     * @dataProvider endpointWithColumnKeysProvider
     *
     * @param array<string, mixed> $endpointData
     * @param array<int, string>   $endpointColumnNames
     * @param string               $expectedEndpoint
     *
     * @throws ClassAlreadyExistsException
     * @throws ClassIsFinalException
     * @throws ClassIsReadonlyException
     * @throws DuplicateMethodException
     * @throws ExpectationFailedException
     * @throws IncompatibleReturnValueException
     * @throws InvalidArgumentException
     * @throws InvalidMethodNameException
     * @throws MethodCannotBeConfiguredException
     * @throws MethodNameAlreadyConfiguredException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws RuntimeException
     * @throws UnknownTypeException
     * @throws \PHPUnit\Framework\Exception
     * @throws \PHPUnit\Framework\InvalidArgumentException
     * @throws \PHPUnit\Framework\MockObject\ReflectionException
     */
    public function testBuildEndpointWithColumnKeys(
        array  $endpointData,
        array  $endpointColumnNames,
        string $expectedEndpoint
    ): void {
        $tableMock = $this->createPartialMock(TableStub::class, ['getEndpointColumnNames']);

        $tableMock
            ->expects($this->once())
            ->method('getEndpointColumnNames')
            ->willReturn($endpointColumnNames);

        $givenEndpoint = $tableMock->buildEndpoint($endpointData);

        $this->assertEquals($expectedEndpoint, $givenEndpoint);
    }

    /**
     * @return array<int, array<int, array<string, string>|array<int, string>|string>>
     */
    public function endpointWithColumnKeysProvider(): array
    {
        return [
            [
                ['foo' => 'bar', 'everything' => 'nothing', 'yes' => 'no', 'yo' => 'lo'],
                ['everything', 'yo', 'foo', 'yes'],
                'nothing||lo||bar||no'
            ]
        ];
    }

    /**
     * @throws DBALException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     * @throws \Jtl\Connector\Dbc\DbcRuntimeException
     * @throws \RuntimeException
     */
    public function testExtractEndpoint(): void
    {
        $endpoint = \sprintf('3||5||bar||%s', TableStub::TYPE1);
        $expected =
            [
                TableStub::COL_ID1           => 3,
                TableStub::COL_ID2           => 5,
                TableStub::COL_VAR           => 'bar',
                AbstractTable::IDENTITY_TYPE => TableStub::TYPE1
            ];
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub = $this->table;
        $data      = $tableStub->extractEndpoint($endpoint);
        $this->assertEquals($expected, $data);
    }

    /**
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws MappingTablesException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testExplodeEndpoint(): void
    {
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub = $this->table;
        $delimiter = '>=<';
        $tableStub->setEndpointDelimiter($delimiter);
        $exptected1 = 'foo';
        $exptected2 = 'bar';
        $endpoint   = \sprintf('%s%s%s', $exptected1, $delimiter, $exptected2);
        $exploded   = $tableStub->explodeEndpoint($endpoint);
        $this->assertCount(2, $exploded);
        $this->assertEquals($exptected1, $exploded[0]);
        $this->assertEquals($exptected2, $exploded[1]);
    }

    /**
     * @return void
     * @throws \Jtl\Connector\MappingTables\MappingTablesException
     * @throws \PHPUnit\Framework\Exception
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testExplodeEndpointWithEmptyString(): void
    {
        $this->expectException(MappingTablesException::class);
        $this->expectExceptionCode(MappingTablesException::EMPTY_ENDPOINT_ID);
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub = $this->table;
        $tableStub->explodeEndpoint('');
    }

    /**
     * @throws DBALException
     * @throws \Jtl\Connector\Dbc\DbcRuntimeException
     * @throws \RuntimeException|\SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testExtractEndpointUnknownType(): void
    {
        $this->expectException(MappingTablesException::class);
        $this->expectExceptionCode(MappingTablesException::TABLE_NOT_RESPONSIBLE_FOR_TYPE);
        $endpoint = \sprintf('3||5||bar||%s', 3244);
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub = $this->table;
        $tableStub->extractEndpoint($endpoint);
    }

    /**
     * @throws MappingTablesException
     * @throws SchemaException
     * @throws DBALException
     * @throws Exception
     * @throws \Exception
     */
    public function testAddColumnType(): void
    {
        $table = new TableStub($this->getDbManager());
        $table->setEndpointColumn('test', Types::BINARY);
        $schema = $table->getTableSchema();
        $column = $schema->getColumn('test');
        $this->assertEquals(Types::BINARY, $column->getType()->getName());
    }

    /**
     * @throws MappingTablesException
     * @throws DBALException
     * @throws Exception
     * @throws \Exception
     */
    public function testAddColumn(): void
    {
        $table = new TableStub($this->getDbManager());
        $table->setEndpointColumn('test', Types::DATETIME_IMMUTABLE);
        $schema     = $table->getTableSchema();
        $primaryKey = $schema->getPrimaryKey();
        $this->assertNotNull($primaryKey);
        $this->assertContains('test', $primaryKey->getColumns());
    }

    /**
     * @throws MappingTablesException
     * @throws DBALException
     * @throws Exception
     * @throws \Exception
     */
    public function testAddColumnNotPrimary(): void
    {
        $table = new TableStub($this->getDbManager());
        $table->setEndpointColumn('test', Types::STRING, false);
        $schema = $table->getTableSchema();
        $this->assertTrue($schema->hasColumn('test'));
        $primaryKey = $schema->getPrimaryKey();
        $this->assertNotNull($primaryKey);
        $this->assertNotContains('test', $primaryKey->getColumns());
    }

    /**
     * @throws DBALException
     * @throws \RuntimeException
     */
    public function testEmptyTypes(): void
    {
        $this->expectException(MappingTablesException::class);
        $this->expectExceptionCode(MappingTablesException::TYPES_ARRAY_EMPTY);
        new class ($this->getDBManager()) extends TableStub {
            public function getTypes(): array
            {
                return [];
            }
        };
    }

    /**
     * @dataProvider extractValueFromEndpointProvider
     *
     * @param string     $field
     * @param string     $endpoint
     * @param int|string $expectedValue
     *
     * @throws DBALException
     * @throws MappingTablesException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws \Jtl\Connector\Dbc\DbcRuntimeException
     * @throws \RuntimeException
     */
    public function testExtractValueFromEndpoint(string $field, string $endpoint, $expectedValue): void
    {
        $this->assertInstanceOf(TableStub::class, $this->table);
        /** @var TableStub $tableStub */
        $tableStub   = $this->table;
        $actualValue = $tableStub->extractValueFromEndpoint($field, $endpoint);
        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * @return array<int, array<int, int|string>>
     */
    public function extractValueFromEndpointProvider(): array
    {
        return [
            [TableStub::COL_ID1, \sprintf('%d||%d||%s||%d', 5, 42, 'strg', TableStub::TYPE1), 5],
            [TableStub::COL_ID2, \sprintf('%d||%d||%s||%d', 5, 42, 'strg', TableStub::TYPE2), 42],
            [TableStub::COL_VAR, \sprintf('%d||%d||%s||%d', 5, 42, 'strg', TableStub::TYPE1), 'strg'],
        ];
    }

    /**
     * @dataProvider wrongTypesProvider
     *
     * @param mixed[] $types
     *
     * @throws DBALException
     * @throws \RuntimeException
     */
    public function testWrongTypes(array $types): void
    {
        $this->expectException(MappingTablesException::class);
        $this->expectExceptionCode(MappingTablesException::TYPES_WRONG_DATA_TYPE);
        new class ($this->getDBManager(), $types) extends TableStub {
            /** @var mixed[] */
            protected array $types = [];

            /**
             * @param DbManager $dbManager
             * @param mixed[]   $types
             */
            public function __construct(DbManager $dbManager, array $types)
            {
                $this->types = $types;
                parent::__construct($dbManager);
            }

            /**
             * @return mixed[]
             */
            public function getTypes(): array
            {
                return $this->types;
            }
        };
    }

    /**
     * @return array<int, array<int, array<int, string|float|bool|\stdClass>>>
     */
    public function wrongTypesProvider(): array
    {
        return [
            [['string']],
            [[0.1]],
            [[false]],
            [[new \stdClass()]],
        ];
    }

    /**
     * @throws DBALException
     * @throws \Exception
     * @throws Throwable
     */
    protected function setUp(): void
    {
        $this->table = new TableStub($this->getDbManager());
        parent::setUp();
        $this->insertFixtures($this->table, self::getTableStubFixtures());
    }
}
