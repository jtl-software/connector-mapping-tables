<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2018 JTL-Software GmbH
 */

namespace Jtl\Connector\MappingTables;

use PHPUnit\Framework\TestCase;

class DummyTableTest extends TestCase
{
    /**
     * @var DummyTable
     */
    protected $table;

    protected function setUp(): void
    {
        parent::setUp();
        $this->table = new DummyTable();
    }

    public function testClear()
    {
        $expected = 0;
        $actual = $this->table->clear();
        $this->assertEquals($expected, $actual);
    }

    public function testFindAllEndpoints()
    {
        $this->assertEquals([], $this->table->findEndpoints());
    }

    public function testGetTypeDefault()
    {
        $this->assertEquals(0, $this->table->getType());
    }

    public function testGetTypeChanged()
    {
        $type = 123;
        $this->table->setType($type);
        $this->assertEquals($type, $this->table->getType());
    }

    public function testRemove()
    {
        $this->assertTrue($this->table->remove());
        $this->assertTrue($this->table->remove('irgendwas'));
        $this->assertTrue($this->table->remove(null, 1234));
    }

    public function testGetHostId()
    {
        $this->assertEquals(0, $this->table->getHostId('wtf'));
        $this->assertEquals(0, $this->table->getHostId(''));
        $this->assertEquals(0, $this->table->getHostId('caskdjasd'));
    }

    public function testCount()
    {
        $this->assertEquals(0, $this->table->count());
    }

    public function testGetEndpointId()
    {
        $this->assertEquals('', $this->table->getEndpoint(12321));
        $this->assertEquals('', $this->table->getEndpoint(3333));
        $this->assertEquals('', $this->table->getEndpoint(0));
    }

    public function testSetType()
    {
        $this->table->setType(444);
        $this->assertEquals(444, $this->table->getType());
        $this->table->setType(555);
        $this->assertEquals(555, $this->table->getType());
    }

    public function testSave()
    {
        $this->assertTrue($this->table->save('foo', 125));
        $this->assertTrue($this->table->save('bar', 444));
        $this->assertTrue($this->table->save('something', 444));
    }

    public function testFindNotFetchedEndpoints()
    {
        $endpoints = ['a', 'b', 'c', 'd'];
        $this->table->save('a', 333);
        $this->table->save('b', 9714);
        $notFetched = $this->table->findNotFetchedEndpoints($endpoints);
        $this->assertEquals($endpoints, $notFetched);
    }
}
