<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */
namespace jtl\Connector\MappingTables;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;



class AbstractMappingTableTest extends DBTestCase
{
    public function testTableSchema()
    {
        $tableSchema = $this->table->getTableSchema();
        $this->assertTrue($tableSchema->hasColumn(AbstractMappingTable::HOST_ID));
        $this->assertTrue($tableSchema->hasColumn(MappingTableStub::COL_ID1));
        $this->assertTrue($tableSchema->hasColumn(MappingTableStub::COL_ID2));
        $this->assertTrue($tableSchema->hasColumn(MappingTableStub::COL_VAR));
    }

    public function testHostIndex()
    {
        $tableSchema = $this->table->getTableSchema();

        $this->assertTrue($tableSchema->hasIndex(AbstractMappingTable::HOST_INDEX_NAME));
        $hostIndex = $tableSchema->getIndex(AbstractMappingTable::HOST_INDEX_NAME);
        $hostColumns = $hostIndex->getColumns();
        $this->assertCount(1, $hostColumns);
        /** @var Column $hostColumn */
        $hostColumn = reset($hostColumns);
        $this->assertEquals(AbstractMappingTable::HOST_ID, $hostColumn);
    }

    public function testPrimaryIndex()
    {
        $tableSchema = $this->table->getTableSchema();
        $this->assertTrue($tableSchema->hasPrimaryKey());
        $primaryKey = $tableSchema->getPrimaryKey();
        $primaryColumns = $primaryKey->getColumns();
        $this->assertCount(2, $primaryColumns);
        $this->assertEquals(MappingTableStub::COL_ID1, $primaryColumns[0]);
        $this->assertEquals(MappingTableStub::COL_ID2, $primaryColumns[1]);
    }

    public function testEndpointIndex()
    {
        $tableSchema = $this->table->getTableSchema();
        $this->assertTrue($tableSchema->hasIndex(MappingTableStub::ENDPOINT_INDEX_NAME));
        $epIndex = $tableSchema->getIndex(MappingTableStub::ENDPOINT_INDEX_NAME);
        $epColumns = $epIndex->getColumns();
        $this->assertCount(3, $epColumns);
        $this->assertEquals(MappingTableStub::COL_ID1, $epColumns[0]);
        $this->assertEquals(MappingTableStub::COL_ID2, $epColumns[1]);
        $this->assertEquals(MappingTableStub::COL_VAR, $epColumns[2]);
    }

    public function testGetHostId()
    {
        $this->assertEquals(3, $this->table->getHostId('1||1||foo'));
        $this->assertEquals(2, $this->table->getHostId('1||2||bar'));
        $this->assertEquals(5, $this->table->getHostId('4||2||foobar'));
    }

    public function testGetEndpointId()
    {
        $this->assertEquals('1||1||foo', $this->table->getEndpointId(3));
        $this->assertEquals('1||2||bar', $this->table->getEndpointId(2));
        $this->assertEquals('4||2||foobar', $this->table->getEndpointId(5));
    }

    public function testSave()
    {
        $this->table->save('1||45||yolo', 4);
        $this->assertTableRowCount($this->table->getTableName(), 4);
    }

    public function testRemoveByEndpointId()
    {
        $this->assertEquals('1||1||foo', $this->table->getEndpointId(3));
        $this->table->remove('1||1||foo');
        $this->assertTableRowCount($this->table->getTableName(), 2);
        $this->assertEquals(null, $this->table->getEndpointId(3));
    }

    public function testRemoveByHostId()
    {
        $this->assertEquals('1||1||foo', $this->table->getEndpointId(3));
        $this->table->remove(null, 3);
        $this->assertTableRowCount($this->table->getTableName(), 2);
        $this->assertEquals(null, $this->table->getEndpointId(3));
    }

    public function testClear()
    {
        $this->table->clear();
        $this->assertTableRowCount($this->table->getTableName(), 0);
    }

    public function testCount()
    {
        $this->assertTableRowCount($this->table->getTableName(), 3);
        $this->assertEquals(3, $this->table->count());
        $this->table->remove('1||1||foo');
        $this->assertTableRowCount($this->table->getTableName(), 2);
        $this->assertEquals(2, $this->table->count());

    }

    public function testCountWithWhereCondition()
    {
        $where = [MappingTableStub::COL_ID2 . ' = :' . MappingTableStub::COL_ID2];
        $this->assertEquals(0, $this->table->count($where, [MappingTableStub::COL_ID2 => 63]));
        $this->assertEquals(1, $this->table->count($where, [MappingTableStub::COL_ID2 => 1]));
        $this->assertEquals(2, $this->table->count($where, [MappingTableStub::COL_ID2 => 2]));
    }

    public function testFindAllEndpoints()
    {
        $endpoints = $this->table->findAllEndpoints();
        $this->assertCount(3, $endpoints);
        $this->assertEquals('1||1||foo', $endpoints[0]);
        $this->assertEquals('1||2||bar', $endpoints[1]);
        $this->assertEquals('4||2||foobar', $endpoints[2]);
    }

    public function testFindAllEndpointsWithNoData()
    {
        $this->table->clear();
        $endpoints = $this->table->findAllEndpoints();
        $this->assertTrue(is_array($endpoints));
        $this->assertEmpty($endpoints);
    }

    public function testFindNotFetchedEndpoints()
    {
        $endpoints = ['1||1||foo', '1||2||bar', '2||1||yolo', '2||2||dito', '2||3||mito'];
        $notFetched = $this->table->findNotFetchedEndpoints($endpoints);
        $this->assertCount(3, $notFetched);
        $this->assertTrue(in_array('2||1||yolo', $notFetched));
        $this->assertTrue(in_array('2||2||dito', $notFetched));
        $this->assertTrue(in_array('2||3||mito', $notFetched));
    }

    public function testBuildEndpoint()
    {
        $data = ['f','u','c','k'];
        $expected = implode($this->table->getEndpointDelimiter(), $data);
        $endpoint = $this->table->buildEndpoint($data);
        $this->assertEquals($expected, $endpoint);
    }

    public function testExtractEndpoint()
    {
        $endpoint = '3||5||bar';
        $expected = [MappingTableStub::COL_ID1 => 3, MappingTableStub::COL_ID2 => 5, MappingTableStub::COL_VAR => 'bar'];
        $data = $this->table->extractEndpoint($endpoint);
        $this->assertEquals($expected, $data);
    }

    public function testAddColumnType()
    {
        $this->table->addEndpointColumn('test', Type::BINARY);
        $schema = $this->table->getTableSchema();
        $column = $schema->getColumn('test');
        $this->assertEquals(Type::BINARY, $column->getType()->getName());
    }

    public function testAddColumn()
    {
        $this->table->addEndpointColumn('test', Type::DATETIME);
        $schema = $this->table->getTableSchema();
        $primaryKey = $schema->getPrimaryKey();
        $this->assertTrue(in_array('test', $primaryKey->getColumns()));
    }

    public function testAddColumnNotPrimary()
    {
        $this->table->addEndpointColumn('test', Type::STRING, [], false);
        $schema = $this->table->getTableSchema();
        $this->assertTrue($schema->hasColumn('test'));
        $primaryKey = $schema->getPrimaryKey();
        $this->assertFalse(in_array('test', $primaryKey->getColumns()));
    }
}
