<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */
namespace Jtl\Connector\MappingTables;



class MappingTablesManagerTest extends DbTestCase
{
    /**
     * @var MappingTableStub
     */
    protected $table;

    /**
     * @var MappingTablesManager
     */
    protected $mtm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mtm = new MappingTablesManager([$this->table]);
    }

    public function testGetMappingTable()
    {
        $this->assertInstanceOf(MappingTableStub::class, $this->mtm->getTable($this->table->getType()));
    }

    public function testGetHostId()
    {
        $expected = 5;
        $actual = $this->mtm->getHostId($this->table->getType(), "4||2||foobar");
        $this->assertEquals($expected, $actual);
    }

    public function testGetEndpointId()
    {
        $expected = "1||2||bar";
        $actual = $this->mtm->getEndpointId($this->table->getType(), 2);
        $this->assertEquals($expected, $actual);
    }

    public function testSave()
    {
        $endpoint = '1||8||asdf';
        $hostId = 9;
        $this->mtm->save($this->table->getType(), $endpoint, $hostId);
        $this->assertEquals($hostId, $this->mtm->getHostId($this->table->getType(), $endpoint));
        $this->assertEquals($endpoint, $this->mtm->getEndpointId($this->table->getType(), $hostId));
    }

    public function testDeleteByEndpointId()
    {
        $endpoint = "1||2||bar";
        $this->mtm->delete($this->table->getType(), $endpoint);
        $this->assertNull($this->mtm->getHostId($this->table->getType(), $endpoint));
    }

    public function testDeleteByHostId()
    {
        $hostId = 3;
        $this->mtm->delete($this->table->getType(), null, $hostId);
        $this->assertNull($this->mtm->getEndpointId($this->table->getType(), $hostId));
    }

    public function testFindAllEndpoints()
    {
        $this->assertCount(3, $this->mtm->findAllEndpoints($this->table->getType()));
    }

    public function testFindNotFetchedEndpoints()
    {
        $endpoints = ['1||1||foo', '1||2||bar', '2||1||yo', '2||2||lo', '2||3||so'];
        $notFetched = $this->mtm->findNotFetchedEndpoints($this->table->getType(), $endpoints);
        $this->assertCount(3, $notFetched);
        $this->assertTrue(in_array('2||1||yo', $notFetched));
        $this->assertTrue(in_array('2||2||lo', $notFetched));
        $this->assertTrue(in_array('2||3||so', $notFetched));
    }

    public function testClear()
    {
        $this->assertTableRowCount($this->table->getTableName(), 3);
        $this->assertTrue($this->mtm->clear());
        $this->assertTableRowCount($this->table->getTableName(), 0);
    }

    public function testCount()
    {
        $this->assertEquals(3, $this->mtm->count($this->table->getType()));
    }

    public function testGetNotExistingTableWithStrictModeDisabled()
    {
        $this->mtm->setStrictMode(false);
        $type = 234234236;
        $table = $this->mtm->getTable($type);
        $this->assertInstanceOf(DummyTable::class, $table);
    }

    public function testGetNotExistingTableWithStrictModeEnabled()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(RuntimeException::TABLE_TYPE_NOT_FOUND);
        $this->mtm->getTable(7217641241);
    }
}
