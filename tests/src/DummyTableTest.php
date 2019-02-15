<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2018 JTL-Software GmbH
 */

namespace jtl\Connector\MappingTables;

use PHPUnit\Framework\TestCase;

class DummyTableTest extends TestCase
{
    /**
     * @var DummyTable
     */
    protected $obj;

    protected function setUp()
    {
        parent::setUp();
        $this->obj = new DummyTable();
    }


    public function testClear()
    {
        $this->assertTrue($this->obj->clear());
    }

    public function testFindAllEndpoints()
    {
        $this->assertEquals([], $this->obj->findAllEndpoints());
    }

    public function testGetTypeDefault()
    {
        $this->assertEquals(0, $this->obj->getType());
    }

    public function testGetTypeChanged()
    {
        $type = 123;
        $this->obj->setType($type);
        $this->assertEquals($type, $this->obj->getType());
    }

    public function testRemove()
    {
        $this->assertTrue($this->obj->remove());
        $this->assertTrue($this->obj->remove('irgendwas'));
        $this->assertTrue($this->obj->remove(null, 1234));
    }

    public function testGetHostId()
    {
        $this->assertEquals(0, $this->obj->getHostId('wtf'));
        $this->assertEquals(0, $this->obj->getHostId(''));
        $this->assertEquals(0, $this->obj->getHostId('caskdjasd'));
    }

    public function testCount()
    {
        $this->assertEquals(0, $this->obj->count());
    }

    public function testGetEndpointId()
    {
        $this->assertEquals('', $this->obj->getEndpoint(12321));
        $this->assertEquals('', $this->obj->getEndpoint(3333));
        $this->assertEquals('', $this->obj->getEndpoint(0));
    }

    public function testSetType()
    {
        $this->obj->setType(444);
        $this->assertEquals(444, $this->obj->getType());
        $this->obj->setType(555);
        $this->assertEquals(555, $this->obj->getType());
    }

    public function testSave()
    {
        $this->assertTrue($this->obj->save('foo', 125));
        $this->assertTrue($this->obj->save('bar', 444));
        $this->assertTrue($this->obj->save('something', 444));
    }

    public function testFindNotFetchedEndpoints()
    {
        $endpoints = ['a', 'b', 'c', 'd'];
        $this->obj->save('a', 333);
        $this->obj->save('b', 9714);
        $notFetched = $this->obj->findNotFetchedEndpoints($endpoints);
        $this->assertEquals($endpoints, $notFetched);
    }
}
