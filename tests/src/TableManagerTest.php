<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */
namespace Jtl\Connector\MappingTables;

class TableManagerTest extends DbTestCase
{
    /**
     * @var TableStub
     */
    protected $table;

    /**
     * @var TableManager
     */
    protected $mtm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mtm = new TableManager($this->table);
    }

    public function testGetMappingTable()
    {
        $this->assertInstanceOf(TableStub::class, $this->mtm->getTableByType(TableStub::TYPE1));
        $this->assertInstanceOf(TableStub::class, $this->mtm->getTableByType(TableStub::TYPE2));
    }

    public function testGetHostId()
    {
        $expected = 5;
        $endpoint = sprintf('4||2||foobar||%s', TableStub::TYPE1);
        $actual = $this->mtm->getHostId(TableStub::TYPE1, $endpoint);
        $this->assertEquals($expected, $actual);
    }

    public function testGetEndpointId()
    {
        $expected = sprintf('1||2||bar||%s', TableStub::TYPE2);
        $actual = $this->mtm->getEndpointId(TableStub::TYPE2, 2);
        $this->assertEquals($expected, $actual);
    }

    public function testSave()
    {
        $endpoint = sprintf('1||8||asdf||%s', TableStub::TYPE1);
        $hostId = 9;
        $this->mtm->save(TableStub::TYPE1, $endpoint, $hostId);
        $this->assertEquals($hostId, $this->mtm->getHostId(TableStub::TYPE1, $endpoint));
        $this->assertEquals($endpoint, $this->mtm->getEndpointId(TableStub::TYPE1, $hostId));
    }

    public function testDeleteByEndpointId()
    {
        $endpoint = sprintf('1||2||bar||%s', TableStub::TYPE2);
        $this->mtm->delete(TableStub::TYPE2, $endpoint);
        $this->assertNull($this->mtm->getHostId(TableStub::TYPE2, $endpoint));
    }

    public function testDeleteByHostId()
    {
        $hostId = 3;
        $this->mtm->delete(TableStub::TYPE1, null, $hostId);
        $this->assertNull($this->mtm->getEndpointId(TableStub::TYPE1, $hostId));
    }

    public function testFindAllEndpointsIds()
    {
        $this->assertCount(3, $this->mtm->findAllEndpointIds(TableStub::TYPE1));
        $this->assertCount(1, $this->mtm->findAllEndpointIds(TableStub::TYPE2));
    }

    public function testFindNotFetchedEndpoints()
    {
        $fetched = [
            sprintf('1||1||foo||%s', TableStub::TYPE1),
            sprintf('1||2||bar||%s', TableStub::TYPE2),
        ];

        $notFetchedExpected = [
            sprintf('2||1||yo||%s', TableStub::TYPE1),
            sprintf('2||2||lo||%s', TableStub::TYPE1),
            sprintf('2||3||so||%s', TableStub::TYPE1),
        ];

        $endpoints = array_merge($fetched, $notFetchedExpected);

        $notFetchedActual = $this->mtm->filterMappedEndpointIds(TableStub::TYPE1, $endpoints);
        $this->assertCount(3, $notFetchedActual);
        $this->assertEquals($notFetchedExpected, $notFetchedActual);
    }

    public function testClear()
    {
        $this->assertEquals(4, $this->countRows($this->table->getTableName()));
        $this->assertTrue($this->mtm->clear());
        $this->assertEquals(0, $this->countRows($this->table->getTableName()));
    }

    public function testClearByType()
    {
        $this->assertEquals(4, $this->countRows($this->table->getTableName()));
        $this->assertTrue($this->mtm->clear(TableStub::TYPE1));
        $this->assertEquals(1, $this->countRows($this->table->getTableName()));
        $this->assertTrue($this->mtm->clear(TableStub::TYPE1));
        $this->assertEquals(1, $this->countRows($this->table->getTableName()));
        $this->assertTrue($this->mtm->clear(TableStub::TYPE2));
        $this->assertEquals(0, $this->countRows($this->table->getTableName()));
    }

    public function testCount()
    {
        $this->assertEquals(3, $this->mtm->count(TableStub::TYPE1));
    }

    public function testGetNotExistingTableWithStrictModeDisabled()
    {
        $this->mtm->setStrictMode(false);
        $type = 234234236;
        $table = $this->mtm->getTableByType($type);
        $this->assertInstanceOf(TableDummy::class, $table);
    }

    public function testGetNotExistingTableWithStrictModeEnabled()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(RuntimeException::TABLE_TYPE_NOT_FOUND);
        $this->mtm->setStrictMode(true);
        $this->mtm->getTableByType(7217641241);
    }
}
