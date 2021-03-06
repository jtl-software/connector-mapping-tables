<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2018 JTL-Software GmbH
 */

namespace Jtl\Connector\MappingTables;

use PHPUnit\Framework\TestCase;

class TableDummyTest extends TestCase
{
    /**
     * @var TableDummy
     */
    protected $table;

    protected function setUp(): void
    {
        parent::setUp();
        $this->table = new TableDummy();
    }

    public function testClear()
    {
        $expected = 0;
        $actual = $this->table->clear(12);
        $this->assertEquals($expected, $actual);
    }

    public function testFindEndpoints()
    {
        $this->assertEquals([], $this->table->findEndpoints([], [], [], null, null, 23));
        $this->assertEquals([], $this->table->findEndpoints([], [], [], null, null, 5324));
        $this->assertEquals([], $this->table->findEndpoints([], [], [], null, null, 222));
    }

    public function testGetTypeDefault()
    {
        $this->assertEquals([], $this->table->getTypes());
    }

    public function testRemove()
    {
        $this->assertEquals(0, $this->table->remove(null, null, 9999));
        $this->assertEquals(0, $this->table->remove('irgendwas', null, 421));
        $this->assertEquals(0, $this->table->remove(null, 1234, 007));
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
        $this->assertEquals('', $this->table->getEndpoint(12321, 5));
        $this->assertEquals('', $this->table->getEndpoint(3333, 9));
        $this->assertEquals('', $this->table->getEndpoint(0, 1222));
    }

    public function testSetType()
    {
        $this->table->setType(444);
        $types = $this->table->getTypes();
        $this->assertCount(1, $types);
        $this->table->setType(555);
        $types = $this->table->getTypes();
        $this->assertCount(2, $types);
        $this->assertContains(444, $types);
        $this->assertContains(555, $types);
    }

    public function testSave()
    {
        $this->assertEquals(0, $this->table->save('foo', 125));
        $this->assertEquals(0, $this->table->save('bar', 444));
        $this->assertEquals(0, $this->table->save('something', 444));
    }

    public function testFindNotFetchedEndpoints()
    {
        $endpoints = ['a', 'b', 'c', 'd'];
        $this->table->save('a', 333);
        $this->table->save('b', 9714);
        $notFetched = $this->table->filterMappedEndpoints($endpoints);
        $this->assertEquals($endpoints, $notFetched);
    }
}
