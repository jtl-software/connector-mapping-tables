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
        $this->assertEquals(2, $this->table->getHostId(sprintf('1||2||bar||%s', TableStub::TYPE1)));
        $this->assertEquals(5, $this->table->getHostId(sprintf('4||2||foobar||%s', TableStub::TYPE1)));
    }

    public function testGetEndpointId()
    {
        $this->assertEquals(sprintf('1||1||foo||%s', TableStub::TYPE1), $this->table->getEndpoint(TableStub::TYPE1, 3));
        $this->assertEquals(sprintf('1||2||bar||%s', TableStub::TYPE1), $this->table->getEndpoint(TableStub::TYPE1, 2));
        $this->assertEquals(sprintf('4||2||foobar||%s', TableStub::TYPE1), $this->table->getEndpoint(TableStub::TYPE1, 5));
    }

    public function testSave()
    {
        $this->table->save(sprintf('1||45||yolo||%s', TableStub::TYPE1), 4);
        $this->assertTableRowCount($this->table->getTableName(), 4);
    }

    public function testRemoveByEndpointId()
    {
        $this->assertEquals(sprintf('1||1||foo||%s', TableStub::TYPE1), $this->table->getEndpoint(TableStub::TYPE1, 3));
        $this->table->delete(TableStub::TYPE1, sprintf('1||1||foo||%s', TableStub::TYPE1));
        $this->assertTableRowCount($this->table->getTableName(), 2);
        $this->assertEquals(null, $this->table->getEndpoint(TableStub::TYPE1, 3));
    }

    public function testRemoveByHostId()
    {
        $this->assertEquals(sprintf('1||1||foo||%s', TableStub::TYPE1), $this->table->getEndpoint(TableStub::TYPE1, 3));
        $this->table->delete(TableStub::TYPE1, null, 3);
        $this->assertTableRowCount($this->table->getTableName(), 2);
        $this->assertEquals(null, $this->table->getEndpoint(TableStub::TYPE1, 3));
    }

    public function testClear()
    {
        $this->table->clear(TableStub::TYPE1);
        $this->assertTableRowCount($this->table->getTableName(), 0);
    }

    public function testCount()
    {
        $this->assertTableRowCount($this->table->getTableName(), 3);
        $this->assertEquals(3, $this->table->count(TableStub::TYPE1));
        $this->table->delete(TableStub::TYPE1, sprintf('1||1||foo||%s', TableStub::TYPE1));
        $this->assertTableRowCount($this->table->getTableName(), 2);
        $this->assertEquals(2, $this->table->count(TableStub::TYPE1));

    }

    public function testCountWithWhereCondition()
    {
        $where = [TableStub::COL_ID2 . ' = :' . TableStub::COL_ID2];
        $this->assertEquals(0, $this->table->count(TableStub::TYPE1, $where, [TableStub::COL_ID2 => 63]));
        $this->assertEquals(1, $this->table->count(TableStub::TYPE1, $where, [TableStub::COL_ID2 => 1]));
        $this->assertEquals(2, $this->table->count(TableStub::TYPE1, $where, [TableStub::COL_ID2 => 2]));
    }

    public function testFindAllEndpoints()
    {
        $endpoints = $this->table->findEndpoints(TableStub::TYPE1);
        $this->assertCount(3, $endpoints);
        $this->assertEquals(sprintf('1||1||foo||%s', TableStub::TYPE1), $endpoints[0]);
        $this->assertEquals(sprintf('1||2||bar||%s', TableStub::TYPE1), $endpoints[1]);
        $this->assertEquals(sprintf('4||2||foobar||%s', TableStub::TYPE1), $endpoints[2]);
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
            sprintf('1||2||bar||%s', TableStub::TYPE1),
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

    public function testAddColumnType()
    {
        $this->table->addEndpointColumn('test', Types::BINARY);
        $schema = $this->table->getTableSchema();
        $column = $schema->getColumn('test');
        $this->assertEquals(Types::BINARY, $column->getType()->getName());
    }

    public function testAddColumn()
    {
        $this->table->addEndpointColumn('test', Types::DATETIME_IMMUTABLE);
        $schema = $this->table->getTableSchema();
        $primaryKey = $schema->getPrimaryKey();
        $this->assertTrue(in_array('test', $primaryKey->getColumns()));
    }

    public function testAddColumnNotPrimary()
    {
        $this->table->addEndpointColumn('test', Types::STRING, [], false);
        $schema = $this->table->getTableSchema();
        $this->assertTrue($schema->hasColumn('test'));
        $primaryKey = $schema->getPrimaryKey();
        $this->assertFalse(in_array('test', $primaryKey->getColumns()));
    }

    public function testEmptyTypes()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(RuntimeException::TYPES_ARRAY_EMPTY);
        new class($this->dbManager) extends TableStub {
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
        new class($this->dbManager, $types) extends TableStub {
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
