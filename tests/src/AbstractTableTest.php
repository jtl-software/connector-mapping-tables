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
use ReflectionException;
use Throwable;

class AbstractTableTest extends TestCase
{
    protected \Jtl\Connector\Dbc\AbstractTable $table;

    /**
     * @throws DBALException
     */
    public function testTableSchema(): void
    {
        $tableSchema = $this->table->getTableSchema();
        $this->assertTrue($tableSchema->hasColumn(AbstractTable::HOST_ID));
        $this->assertTrue($tableSchema->hasColumn(TableStub::COL_ID1));
        $this->assertTrue($tableSchema->hasColumn(TableStub::COL_ID2));
        $this->assertTrue($tableSchema->hasColumn(TableStub::COL_VAR));
    }

    /**
     * @throws DBALException
     * @throws SchemaException
     */
    public function testHostIndex(): void
    {
        $tableSchema = $this->table->getTableSchema();
        $this->assertTrue($tableSchema->hasIndex($this->table->createIndexName(AbstractTable::HOST_INDEX_NAME)));
        $hostIndex   = $tableSchema->getIndex($this->table->createIndexName(AbstractTable::HOST_INDEX_NAME));
        $hostColumns = $hostIndex->getColumns();
        $this->assertCount(1, $hostColumns);
        /** @var Column $hostColumn */
        $hostColumn = \reset($hostColumns);
        $this->assertEquals(AbstractTable::HOST_ID, $hostColumn);
    }

    /**
     * @throws DBALException
     */
    public function testPrimaryIndex(): void
    {
        $tableSchema = $this->table->getTableSchema();
        $this->assertTrue($tableSchema->hasPrimaryKey());
        $primaryKey     = $tableSchema->getPrimaryKey();
        $primaryColumns = $primaryKey->getColumns();
        $this->assertCount(3, $primaryColumns);
        $this->assertEquals(TableStub::COL_ID1, $primaryColumns[0]);
        $this->assertEquals(TableStub::COL_ID2, $primaryColumns[1]);
        $this->assertEquals(AbstractTable::IDENTITY_TYPE, $primaryColumns[2]);
    }

    /**
     * @throws SchemaException
     * @throws DBALException
     */
    public function testEndpointIndex(): void
    {
        $tableSchema = $this->table->getTableSchema();
        $this->assertTrue($tableSchema->hasIndex($this->table->createIndexName(AbstractTable::ENDPOINT_INDEX_NAME)));
        $epIndex   = $tableSchema->getIndex($this->table->createIndexName(AbstractTable::ENDPOINT_INDEX_NAME));
        $epColumns = $epIndex->getColumns();
        $this->assertCount(4, $epColumns);
        $this->assertEquals(TableStub::COL_ID1, $epColumns[0]);
        $this->assertEquals(TableStub::COL_ID2, $epColumns[1]);
        $this->assertEquals(TableStub::COL_VAR, $epColumns[2]);
        $this->assertEquals(AbstractTable::IDENTITY_TYPE, $epColumns[3]);
    }

    /**
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws MappingTablesException
     */
    public function testGetHostId(): void
    {
        $this->assertEquals(3, $this->table->getHostId(\sprintf('1||1||foo||%s', TableStub::TYPE1)));
        $this->assertEquals(2, $this->table->getHostId(\sprintf('1||2||bar||%s', TableStub::TYPE2)));
        $this->assertEquals(5, $this->table->getHostId(\sprintf('4||2||foobar||%s', TableStub::TYPE1)));
    }

    /**
     * @throws MappingTablesException
     * @throws Exception
     */
    public function testGetEndpointId(): void
    {
        $this->assertEquals(
            \sprintf('1||1||foo||%s', TableStub::TYPE1),
            $this->table->getEndpoint(3, TableStub::TYPE1)
        );
        $this->assertEquals(
            \sprintf('1||2||bar||%s', TableStub::TYPE2),
            $this->table->getEndpoint(2, TableStub::TYPE2)
        );
        $this->assertEquals(
            \sprintf('4||2||foobar||%s', TableStub::TYPE1),
            $this->table->getEndpoint(5, TableStub::TYPE1)
        );
    }

    /**
     * @throws MappingTablesException
     * @throws DBALException
     */
    public function testSave(): void
    {
        $this->table->save(\sprintf('1||45||yolo||%s', TableStub::TYPE1), 4);
        $this->assertEquals(5, $this->countRows($this->table->getTableName()));
    }

    /**
     * @throws MappingTablesException
     * @throws DBALException
     * @throws Exception
     */
    public function testDeleteByEndpointId(): void
    {
        $this->assertEquals(
            \sprintf('1||1||foo||%s', TableStub::TYPE1),
            $this->table->getEndpoint(3, TableStub::TYPE1)
        );
        $this->table->remove(\sprintf('1||1||foo||%s', TableStub::TYPE1), null, TableStub::TYPE1);
        $this->assertEquals(3, $this->countRows($this->table->getTableName()));
        $this->assertEquals(null, $this->table->getEndpoint(3, TableStub::TYPE1));
    }

    /**
     * @throws MappingTablesException
     * @throws DBALException
     * @throws Exception
     */
    public function testDeleteByHostId(): void
    {
        $this->assertEquals(
            \sprintf('1||1||foo||%s', TableStub::TYPE1),
            $this->table->getEndpoint(3, TableStub::TYPE1)
        );
        $this->table->remove(null, 3, TableStub::TYPE1);
        $this->assertEquals(3, $this->countRows($this->table->getTableName()));
        $this->assertEquals(null, $this->table->getEndpoint(3, TableStub::TYPE1));
    }

    /**
     * @dataProvider deleteByHostIdMultipleEntriesProvider
     *
     * @param string|null $endpoint
     *
     * @throws DBALException
     * @throws MappingTablesException
     * @throws Exception|\Doctrine\DBAL\Driver\Exception
     */
    public function testDeleteByHostIdMultipleEntries(?string $endpoint): void
    {
        $relatedHostId   = 5;
        $anotherEndpoint = \sprintf('5||7||wat||%s', TableStub::TYPE1);
        $this->table->save($anotherEndpoint, $relatedHostId);
        $this->assertEquals(5, $this->countRows($this->table->getTableName()));
        $this->assertEquals($relatedHostId, $this->table->getHostId($anotherEndpoint));
        $this->table->remove($endpoint, $relatedHostId, TableStub::TYPE1);
        $this->assertEquals(2, $this->countRows($this->table->getTableName()));
        $this->assertNull($this->table->getEndpoint($relatedHostId, TableStub::TYPE1));
        $this->assertNull($this->table->getHostId($anotherEndpoint));
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
     * @throws MappingTablesException
     * @throws DBALException
     * @throws Exception
     */
    public function testClearDifferentTypes(): void
    {
        $this->table->clear(TableStub::TYPE1);
        $this->assertEquals(1, $this->countRows($this->table->getTableName()));
        $this->table->clear(TableStub::TYPE2);
        $this->assertEquals(0, $this->countRows($this->table->getTableName()));
    }

    /**
     * @throws MappingTablesException
     * @throws DBALException
     * @throws Exception
     */
    public function testClearAll(): void
    {
        $this->assertEquals(4, $this->countRows($this->table->getTableName()));
        $this->table->clear();
        $this->assertEquals(0, $this->countRows($this->table->getTableName()));
    }

    /**
     * @throws Exception
     */
    public function testClearUnknownType(): void
    {
        $this->expectException(MappingTablesException::class);
        $this->expectExceptionCode(MappingTablesException::TABLE_NOT_RESPONSIBLE_FOR_TYPE);
        $this->table->clear(44232);
    }

    /**
     * @throws DBALException
     * @throws MappingTablesException|\Doctrine\DBAL\Driver\Exception
     */
    public function testCount(): void
    {
        $this->assertEquals(4, $this->countRows($this->table->getTableName()));
        $this->assertEquals(3, $this->table->count([], [], [], null, null, TableStub::TYPE1));
        $this->assertEquals(1, $this->table->count([], [], [], null, null, TableStub::TYPE2));
        $this->table->remove(\sprintf('1||1||foo||%s', TableStub::TYPE1), null, TableStub::TYPE1);
        $this->assertEquals(3, $this->countRows($this->table->getTableName()));
        $this->assertEquals(2, $this->table->count([], [], [], null, null, TableStub::TYPE1));
    }

    /**
     * @throws MappingTablesException
     * @throws DBALException|\Doctrine\DBAL\Driver\Exception
     */
    public function testCountWithWhereCondition(): void
    {
        $where = [TableStub::COL_ID2 . ' = :' . TableStub::COL_ID2];
        $this->assertEquals(
            0,
            $this->table->count($where, [TableStub::COL_ID2 => 63], [], null, null, TableStub::TYPE1)
        );
        $this->assertEquals(
            1,
            $this->table->count($where, [TableStub::COL_ID2 => 1], [], null, null, TableStub::TYPE1)
        );
        $this->assertEquals(
            1,
            $this->table->count($where, [TableStub::COL_ID2 => 2], [], null, null, TableStub::TYPE1)
        );
    }

    /**
     * @throws MappingTablesException
     * @throws DBALException
     */
    public function testFindEndpointsByType(): void
    {
        $endpoints = $this->table->findEndpoints([], [], [], null, null, TableStub::TYPE1);
        $this->assertCount(3, $endpoints);
        $this->assertEquals(\sprintf('1||1||foo||%s', TableStub::TYPE1), $endpoints[0]);
        $this->assertEquals(\sprintf('4||2||foobar||%s', TableStub::TYPE1), $endpoints[1]);
        $endpoints = $this->table->findEndpoints([], [], [], null, null, TableStub::TYPE2);
        $this->assertCount(1, $endpoints);
        $this->assertEquals(\sprintf('1||2||bar||%s', TableStub::TYPE2), $endpoints[0]);
    }

    /**
     * @throws MappingTablesException
     * @throws DBALException
     * @throws Exception
     */
    public function testFindAllEndpointsWithNoData(): void
    {
        $this->table->clear(TableStub::TYPE1);
        $endpoints = $this->table->findEndpoints([], [], [], null, null, TableStub::TYPE1);
        $this->assertIsArray($endpoints);
        $this->assertEmpty($endpoints);
    }

    /**
     * @throws MappingTablesException
     * @throws DBALException
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

        $notMappedActual = $this->table->filterMappedEndpoints($endpoints);
        $this->assertCount(3, $notMappedActual);
        $this->assertEquals($notMappedExpected, $notMappedActual);
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateEndpointData(): void
    {
        $endpointData = ['5', '7', 'foobar', TableStub::TYPE1];
        $data         = $this->invokeMethodFromObject($this->table, 'createEndpointData', $endpointData);
        $this->assertCount(4, $data);
        $this->assertArrayHasKey('id1', $data);
        $this->assertIsInt($data['id1']);
        $this->assertArrayHasKey('id2', $data);
        $this->assertIsInt($data['id2']);
        $this->assertArrayHasKey('strg', $data);
        $this->assertIsString($data['strg']);
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateEndpointDataFailsTooMuchData(): void
    {
        $this->expectException(MappingTablesException::class);
        $this->expectExceptionCode(MappingTablesException::WRONG_ENDPOINT_PARTS_AMOUNT);
        $endpointData = ['foo', 'bar', '123', '21', '1.3'];
        $this->invokeMethodFromObject($this->table, 'createEndpointData', $endpointData);
    }

    /**
     * @throws ReflectionException
     */
    public function testCreateEndpointDataFailsNotEnoughData(): void
    {
        $this->expectException(MappingTablesException::class);
        $this->expectExceptionCode(MappingTablesException::WRONG_ENDPOINT_PARTS_AMOUNT);
        $endpointData = ['foo', 'bar'];
        $this->invokeMethodFromObject($this->table, 'createEndpointData', $endpointData);
    }

    public function testBuildEndpoint(): void
    {
        $data     = ['f', 'u', 'c', 'k'];
        $expected = \implode($this->table->getEndpointDelimiter(), $data);
        $endpoint = $this->table->buildEndpoint($data);
        $this->assertEquals($expected, $endpoint);
    }

    /**
     * @dataProvider endpointWithColumnKeysProvider
     *
     * @param array  $endpointData
     * @param array  $endpointColumnNames
     * @param string $expectedEndpoint
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
     * @return array
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
     * @throws MappingTablesException
     * @throws DBALException
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
        $data     = $this->table->extractEndpoint($endpoint);
        $this->assertEquals($expected, $data);
    }

    /**
     * @throws MappingTablesException
     */
    public function testExplodeEndpoint(): void
    {
        $delimiter = '>=<';
        $this->table->setEndpointDelimiter($delimiter);
        $exptected1 = 'foo';
        $exptected2 = 'bar';
        $endpoint   = \sprintf('%s%s%s', $exptected1, $delimiter, $exptected2);
        $exploded   = $this->table->explodeEndpoint($endpoint);
        $this->assertCount(2, $exploded);
        $this->assertEquals($exptected1, $exploded[0]);
        $this->assertEquals($exptected2, $exploded[1]);
    }

    public function testExplodeEndpointWithEmptyString(): void
    {
        $this->expectException(MappingTablesException::class);
        $this->expectExceptionCode(MappingTablesException::EMPTY_ENDPOINT_ID);
        $this->table->explodeEndpoint('');
    }

    /**
     * @throws DBALException
     */
    public function testExtractEndpointUnknownType(): void
    {
        $this->expectException(MappingTablesException::class);
        $this->expectExceptionCode(MappingTablesException::TABLE_NOT_RESPONSIBLE_FOR_TYPE);
        $endpoint = \sprintf('3||5||bar||%s', 3244);
        $this->table->extractEndpoint($endpoint);
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
        $this->assertTrue(\in_array('test', $primaryKey->getColumns()));
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
        $this->assertNotContains('test', $primaryKey->getColumns());
    }

    /**
     * @throws DBALException
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
     */
    public function testExtractValueFromEndpoint(string $field, string $endpoint, $expectedValue): void
    {
        $actualValue = $this->table->extractValueFromEndpoint($field, $endpoint);
        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * @return array[]
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
     */
    public function testWrongTypes(array $types): void
    {
        $this->expectException(MappingTablesException::class);
        $this->expectExceptionCode(MappingTablesException::TYPES_WRONG_DATA_TYPE);
        new class ($this->getDBManager(), $types) extends TableStub {
            protected array $types = [];

            public function __construct(DbManager $dbManager, array $types)
            {
                $this->types = $types;
                parent::__construct($dbManager);
            }

            public function getTypes(): array
            {
                return $this->types;
            }
        };
    }

    /**
     * @return mixed[]
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
