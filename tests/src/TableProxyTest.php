<?php
namespace Jtl\Connector\MappingTables;

use PHPUnit\Framework\TestCase;

class TableProxyTest extends DbTestCase
{
    /**
     * @var TableProxy
     */
    protected $proxy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->proxy = new TableProxy(TableStub::TYPE1, $this->table);
    }

    public function testGetHostId()
    {
        $expected = 5;
        $endpoint = sprintf('4||2||foobar||%s', TableStub::TYPE1);
        $actual = $this->proxy->getHostId($endpoint);
        $this->assertEquals($expected, $actual);
    }

    public function testGetHostIdFromNotSelectedType()
    {
        $expected = 2;
        $endpoint = sprintf('1||2||bar||%s', TableStub::TYPE2);
        $actual = $this->proxy->getHostId($endpoint);
        $this->assertEquals($expected, $actual);
    }

    public function testGetHostIdWhichNotExists()
    {
        $expected = null;
        $endpoint = sprintf('1||4||3344||%s', TableStub::TYPE1);
        $actual = $this->proxy->getHostId($endpoint);
        $this->assertEquals($expected, $actual);
    }

    public function testCountAndClear()
    {
        $this->assertEquals(3, $this->proxy->count());
        $this->proxy->clear();
        $this->assertEquals(0, $this->proxy->count());
    }

    public function testGetEndpoint()
    {
        $expected = sprintf('4||2||foobar||%s', TableStub::TYPE1);
        $hostId = 5;
        $actual = $this->proxy->getEndpoint($hostId);
        $this->assertEquals($expected, $actual);
    }

    public function testGetEndpointFromNotSelectedType()
    {
        //$expected = sprintf('1||2||bar||%s', TableStub::TYPE2);
        $expected = null;
        $hostId = 2;
        $actual = $this->proxy->getEndpoint($hostId);
        $this->assertEquals($expected, $actual);
    }

    public function testDeleteByHostId()
    {
        $this->assertEquals(3, $this->proxy->count());
        $hostId = 3;
        $this->proxy->delete(null, $hostId);
        $this->assertEquals(2, $this->proxy->count());
    }

    public function testDeleteByHostIdWithMultipleEntries()
    {
        $this->assertEquals(3, $this->proxy->count());
        $hostId = 5;
        $this->proxy->delete(null, $hostId);
        $this->assertEquals(1, $this->proxy->count());
    }

    public function testDeleteByEndpointId()
    {
        $this->assertEquals(3, $this->proxy->count());
        $endpoint = sprintf('1||1||foo||%s', TableStub::TYPE1);
        $this->proxy->delete($endpoint);
        $this->assertEquals(2, $this->proxy->count());
    }

    public function testGetAndSetType()
    {
        $this->assertEquals(TableStub::TYPE1, $this->proxy->getType());
        $this->proxy->setType(TableStub::TYPE2);
        $this->assertEquals(TableStub::TYPE2, $this->proxy->getType());
    }

    public function testSetWrongType()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(RuntimeException::TYPE_NOT_FOUND);
        $this->proxy->setType(99999);
    }

    public function testSave()
    {
        $hostId = 999;
        $endpoint = sprintf('44||11||juhuu||%s', TableStub::TYPE1);
        $this->assertEquals(3, $this->proxy->count());
        $this->assertTableRowCount($this->table->getTableName(), 4);
        $this->proxy->save($endpoint, $hostId);
        $this->assertEquals(4, $this->proxy->count());
        $this->assertTableRowCount($this->table->getTableName(), 5);
    }

    public function testFindEndpoints()
    {
        $this->assertCount(3, $this->proxy->findEndpoints());
    }

    public function testGetTable()
    {
        $this->assertInstanceOf(TableStub::class, $this->proxy->getTable());
    }
}
