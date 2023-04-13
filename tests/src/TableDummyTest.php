<?php

declare(strict_types=1);

namespace Jtl\Connector\MappingTables;

use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

class TableDummyTest extends TestCase
{
    /**
     * @var TableDummy
     */
    protected TableDummy $table;

    /**
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testClear(): void
    {
        $expected = 0;
        $actual   = $this->table->clear(12);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     */
    public function testFindEndpoints(): void
    {
        $this->assertEquals([], $this->table->findEndpoints([], [], [], null, null, 23));
        $this->assertEquals([], $this->table->findEndpoints([], [], [], null, null, 5324));
        $this->assertEquals([], $this->table->findEndpoints([], [], [], null, null, 222));
    }

    /**
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testGetTypeDefault(): void
    {
        $this->assertEquals([], $this->table->getTypes());
    }

    /**
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     */
    public function testRemove(): void
    {
        $this->assertEquals(0, $this->table->remove(null, null, 9999));
        $this->assertEquals(0, $this->table->remove('irgendwas', null, 421));
        $this->assertEquals(0, $this->table->remove(null, 1234, 007));
    }

    /**
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     */
    public function testGetHostId(): void
    {
        $this->assertEquals(0, $this->table->getHostId('wtf'));
        $this->assertEquals(0, $this->table->getHostId(''));
        $this->assertEquals(0, $this->table->getHostId('caskdjasd'));
    }

    /**
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testCount(): void
    {
        $this->assertEquals(0, $this->table->count());
    }

    /**
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     */
    public function testGetEndpointId(): void
    {
        $this->assertEquals('', $this->table->getEndpoint(12321, 5));
        $this->assertEquals('', $this->table->getEndpoint(3333, 9));
        $this->assertEquals('', $this->table->getEndpoint(0, 1222));
    }

    /**
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testSetType(): void
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

    /**
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     */
    public function testSave(): void
    {
        $this->assertEquals(0, $this->table->save('foo', 125));
        $this->assertEquals(0, $this->table->save('bar', 444));
        $this->assertEquals(0, $this->table->save('something', 444));
    }

    /**
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testFindNotFetchedEndpoints(): void
    {
        $endpoints = ['a', 'b', 'c', 'd'];
        $this->table->save('a', 333);
        $this->table->save('b', 9714);
        $notFetched = $this->table->filterMappedEndpoints($endpoints);
        $this->assertEquals($endpoints, $notFetched);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->table = new TableDummy();
    }
}
