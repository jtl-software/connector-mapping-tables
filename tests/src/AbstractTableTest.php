<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */

namespace Jtl\Connector\MappingTables;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Types;
use Jtl\Connector\Dbc\DbManager;

class AbstractTableTest extends DbTestCase
{
    public function testTableSchema()
    {
        $tableSchema = $this->table->getTableSchema();
        $this->assertTrue($tableSchema->hasColumn(AbstractTable::HOST_ID));
        $this->assertTrue($tableSchema->hasColumn(TableStub::COL_ID1));
        $this->assertTrue($tableSchema->hasColumn(TableStub::COL_ID2));
        $this->assertTrue($tableSchema->hasColumn(TableStub::COL_VAR));
    }

    public function testHostIndex()
    {
        $tableSchema = $this->table->getTableSchema();
        $this->assertTrue($tableSchema->hasIndex($this->table->createIndexName(AbstractTable::HOST_INDEX_NAME)));
        $hostIndex = $tableSchema->getIndex($this->table->createIndexName(AbstractTable::HOST_INDEX_NAME));
        $hostColumns = $hostIndex->getColumns();
        $this->assertCount(1, $hostColumns);
        /** @var Column $hostColumn */
        $hostColumn = reset($hostColumns);
        $this->assertEquals(AbstractTable::HOST_ID, $hostColumn);
    }

    public function testPrimaryIndex()
    {
        $tableSchema = $this->table->getTableSchema();
        $this->assertTrue($tableSchema->hasPrimaryKey());
        $primaryKey = $tableSchema->getPrimaryKey();
        $primaryColumns = $primaryKey->getColumns();
        $this->assertCount(3, $primaryColumns);
        $this->assertEquals(TableStub::COL_ID1, $primaryColumns[0]);
        $this->assertEquals(TableStub::COL_ID2, $primaryColumns[1]);
        $this->assertEquals(TableStub::IDENTITY_TYPE, $primaryColumns[2]);
    }

    public function testEndpointIndex()
    {
        $tableSchema = $this->table->getTableSchema();
        $this->assertTrue($tableSchema->hasIndex($this->table->createIndexName(AbstractTable::ENDPOINT_INDEX_NAME)));
        $epIndex = $tableSchema->getIndex($this->table->createIndexName(AbstractTable::ENDPOINT_INDEX_NAME));
        $epColumns = $epIndex->getColumns();
        $this->assertCount(4, $epColumns);
        $this->assertEquals(TableStub::COL_ID1, $epColumns[0]);
        $this->assertEquals(TableStub::COL_ID2, $epColumns[1]);
        $this->assertEquals(TableStub::COL_VAR, $epColumns[2]);
        $this->assertEquals(TableStub::IDENTITY_TYPE, $epColumns[3]);
    }

    public function testGetHostId()
    {
        $this->assertEquals(3, $this->table->getHostId(sprintf('1||1||foo||%s', TableStub::TYPE1)));
        $this->assertEquals(2, $this->table->getHostId(sprintf('1||2||bar||%s', TableStub::TYPE2)));
        $this->assertEquals(5, $this->table->getHostId(sprintf('4||2||foobar||%s', TableStub::TYPE1)));
    }

    public function testGetEndpointId()
    {
        $this->assertEquals(sprintf('1||1||foo||%s', TableStub::TYPE1), $this->table->getEndpoint(TableStub::TYPE1, 3));
        $this->assertEquals(sprintf('1||2||bar||%s', TableStub::TYPE2), $this->table->getEndpoint(TableStub::TYPE2, 2));
        $this->assertEquals(sprintf('4||2||foobar||%s', TableStub::TYPE1), $this->table->getEndpoint(TableStub::TYPE1, 5));
    }

    public function testSave()
    {
        $this->table->save(sprintf('1||45||yolo||%s', TableStub::TYPE1), 4);
        $this->assertEquals(5, $this->countRows($this->table->getTableName()));
    }

    public function testDeleteByEndpointId()
    {
        $this->assertEquals(sprintf('1||1||foo||%s', TableStub::TYPE1), $this->table->getEndpoint(TableStub::TYPE1, 3));
        $this->table->remove(TableStub::TYPE1, sprintf('1||1||foo||%s', TableStub::TYPE1));
        $this->assertEquals(3, $this->countRows($this->table->getTableName()));
        $this->assertEquals(null, $this->table->getEndpoint(TableStub::TYPE1, 3));
    }

    public function testDeleteByHostId()
    {
        $this->assertEquals(sprintf('1||1||foo||%s', TableStub::TYPE1), $this->table->getEndpoint(TableStub::TYPE1, 3));
        $this->table->remove(TableStub::TYPE1, null, 3);
        $this->assertEquals(3, $this->countRows($this->table->getTableName()));
        $this->assertEquals(null, $this->table->getEndpoint(TableStub::TYPE1, 3));
    }

    public function testDeleteByHostIdMultipleEntries()
    {
        $this->assertEquals(4, $this->countRows($this->table->getTableName()));
        $this->table->remove(TableStub::TYPE1, null, 5);
        $this->assertEquals(2, $this->countRows($this->table->getTableName()));
        $this->assertEquals(null, $this->table->getEndpoint(TableStub::TYPE1, 5));
    }

    public function testClearDifferentTypes()
    {
        $this->table->clear(TableStub::TYPE1);
        $this->assertEquals(1, $this->countRows($this->table->getTableName()));
        $this->table->clear(TableStub::TYPE2);
        $this->assertEquals(0, $this->countRows($this->table->getTableName()));
    }

    public function testClearAll()
    {
        $this->assertEquals(4, $this->countRows($this->table->getTableName()));
        $this->table->clear();
        $this->assertEquals(0, $this->countRows($this->table->getTableName()));
    }

    public function testClearUnknownType()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(RuntimeException::UNKNOWN_TYPE);
        $this->table->clear(44232);
    }

    public function testCount()
    {
        $this->assertEquals(4, $this->countRows($this->table->getTableName()));
        $this->assertEquals(3, $this->table->count(TableStub::TYPE1));
        $this->assertEquals(1, $this->table->count(TableStub::TYPE2));
        $this->table->remove(TableStub::TYPE1, sprintf('1||1||foo||%s', TableStub::TYPE1));
        $this->assertEquals(3, $this->countRows($this->table->getTableName()));
        $this->assertEquals(2, $this->table->count(TableStub::TYPE1));
    }

    public function testCountWithWhereCondition()
    {
        $where = [TableStub::COL_ID2 . ' = :' . TableStub::COL_ID2];
        $this->assertEquals(0, $this->table->count(TableStub::TYPE1, $where, [TableStub::COL_ID2 => 63]));
        $this->assertEquals(1, $this->table->count(TableStub::TYPE1, $where, [TableStub::COL_ID2 => 1]));
        $this->assertEquals(1, $this->table->count(TableStub::TYPE1, $where, [TableStub::COL_ID2 => 2]));
    }

    public function testFindEndpointsByType()
    {
        $endpoints = $this->table->findEndpoints(TableStub::TYPE1);
        $this->assertCount(3, $endpoints);
        $this->assertEquals(sprintf('1||1||foo||%s', TableStub::TYPE1), $endpoints[0]);
        $this->assertEquals(sprintf('4||2||foobar||%s', TableStub::TYPE1), $endpoints[1]);
        $endpoints = $this->table->findEndpoints(TableStub::TYPE2);
        $this->assertCount(1, $endpoints);
        $this->assertEquals(sprintf('1||2||bar||%s', TableStub::TYPE2), $endpoints[0]);
    }

    public function testFindAllEndpointsWithNoData()
    {
        $this->table->clear(TableStub::TYPE1);
        $endpoints = $this->table->findEndpoints(TableStub::TYPE1);
        $this->assertTrue(is_array($endpoints));
        $this->assertEmpty($endpoints);
    }

    public function testFilterMappedEndpoints()
    {
        $mapped = [
            sprintf('1||1||foo||%s', TableStub::TYPE1),
            sprintf('1||2||bar||%s', TableStub::TYPE2),
        ];

        $notMappedExpected = [
            sprintf('2||1||yolo||%s', TableStub::TYPE1),
            sprintf('2||2||dito||%s', TableStub::TYPE1),
            sprintf('2||3||mito||%s', TableStub::TYPE1)
        ];

        $endpoints = array_merge($mapped, $notMappedExpected);

        $notMappedActual = $this->table->filterMappedEndpoints($endpoints);
        $this->assertCount(3, $notMappedActual);
        $this->assertEquals($notMappedExpected, $notMappedActual);
    }

    public function testBuildEndpoint()
    {
        $data = ['f', 'u', 'c', 'k'];
        $expected = implode($this->table->getEndpointDelimiter(), $data);
        $endpoint = $this->table->buildEndpoint($data);
        $this->assertEquals($expected, $endpoint);
    }

    public function testExtractEndpoint()
    {
        $endpoint = sprintf('3||5||bar||%s', TableStub::TYPE1);
        $expected = [TableStub::COL_ID1 => 3, TableStub::COL_ID2 => 5, TableStub::COL_VAR => 'bar', TableStub::IDENTITY_TYPE => TableStub::TYPE1];
        $data = $this->table->extractEndpoint($endpoint);
        $this->assertEquals($expected, $data);
    }

    public function testExplodeEndpoint()
    {
        $delimiter = '>=<';
        $this->table->setEndpointDelimiter($delimiter);
        $exptected1 = 'foo';
        $exptected2 = 'bar';
        $endpoint = sprintf('%s%s%s', $exptected1, $delimiter, $exptected2);
        $exploded = $this->table->explodeEndpoint($endpoint);
        $this->assertCount(2, $exploded);
        $this->assertEquals($exptected1, $exploded[0]);
        $this->assertEquals($exptected2, $exploded[1]);
    }

    public function testExplodeEndpointWithEmptyString()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(RuntimeException::EMPTY_ENDPOINT_ID);
        $this->table->explodeEndpoint('');
    }

    public function testExtractEndpointUnknownType()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(RuntimeException::UNKNOWN_TYPE);
        $endpoint = sprintf('3||5||bar||%s', 3244);
        $this->table->extractEndpoint($endpoint);
    }

    public function testAddColumnType()
    {
        $table = new TableStub($this->getDbManager());
        $table->addEndpointColumn('test', Types::BINARY);
        $schema = $table->getTableSchema();
        $column = $schema->getColumn('test');
        $this->assertEquals(Types::BINARY, $column->getType()->getName());
    }

    public function testAddColumn()
    {
        $table = new TableStub($this->getDbManager());
        $table->addEndpointColumn('test', Types::DATETIME_IMMUTABLE);
        $schema = $table->getTableSchema();
        $primaryKey = $schema->getPrimaryKey();
        $this->assertTrue(in_array('test', $primaryKey->getColumns()));
    }

    public function testAddColumnNotPrimary()
    {
        $table = new TableStub($this->getDbManager());
        $table->addEndpointColumn('test', Types::STRING, [], false);
        $schema = $table->getTableSchema();
        $this->assertTrue($schema->hasColumn('test'));
        $primaryKey = $schema->getPrimaryKey();
        $this->assertFalse(in_array('test', $primaryKey->getColumns()));
    }

    public function testEmptyTypes()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(RuntimeException::TYPES_ARRAY_EMPTY);
        new class($this->getDBManager()) extends TableStub {
            public function getTypes(): array
            {
                return [];
            }
        };
    }

    /**
     * @dataProvider wrongTypesProvider
     *
     * @param mixed[] $types
     */
    public function testWrongTypes(array $types)
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(RuntimeException::TYPES_WRONG_DATA_TYPE);
        new class($this->getDBManager(), $types) extends TableStub {
            protected $types = [];
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
}
