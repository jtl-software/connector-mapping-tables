<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */

namespace Jtl\Connector\Dbc\Mapping;

use Doctrine\DBAL\Types\Type;
use Jtl\Connector\Dbc\CoordinatesStub;
use Jtl\Connector\Dbc\TestCase;
use Jtl\Connector\Dbc\RuntimeException;
use Jtl\Connector\Dbc\TableStub;

class TableTest extends TestCase
{
    /**
     * @var CoordinatesStub
     */
    protected $coords;

    protected function setUp(): void
    {
        $this->table = new TableStub($this->getDBManager());
        $this->coords = new CoordinatesStub($this->getDBManager());
        parent::setUp();
        $this->insertFixtures($this->table, self::getTableStubFixtures());
        $this->insertFixtures($this->coords, self::getCoordinatesFixtures());
    }

    public function testGetName()
    {
        $this->assertEquals(CoordinatesStub::TABLE_NAME, $this->coords->getName());
    }

    public function testGetTableName()
    {
        $this->assertEquals(self::TABLE_PREFIX . $this->coords->getName(), $this->coords->getTableName());
    }

    public function testRestrict()
    {
        $this->table->restrict(TableStub::B, 'a string');
        $data = $this->table->findAll();
        $this->assertCount(1, $data);
        $row = reset($data);
        $this->assertEquals(1, $row[TableStub::A]);
        $this->assertEquals('a string', $row[TableStub::B]);
        $this->assertEquals(new \DateTime('@' . strtotime("2017-03-29 00:00:00")), $row[TableStub::C]);
    }

    public function testGetTableSchema()
    {
        $table = $this->coords->getTableSchema();
        $columns = $table->getColumns();
        $this->assertCount(3, $columns);
        $this->assertArrayHasKey(CoordinatesStub::COL_X, $columns);
        $this->assertEquals(CoordinatesStub::COL_X, $columns[CoordinatesStub::COL_X]->getName());
        $this->assertArrayHasKey(CoordinatesStub::COL_Y, $columns);
        $this->assertEquals(CoordinatesStub::COL_Y, $columns[CoordinatesStub::COL_Y]->getName());
        $this->assertArrayHasKey(CoordinatesStub::COL_Z, $columns);
        $this->assertEquals(CoordinatesStub::COL_Z, $columns[CoordinatesStub::COL_Z]->getName());
    }

    public function testGetColumnTypes()
    {
        $columns = $this->coords->getColumnTypes();
        $this->assertCount(3, $columns);
        $this->assertArrayHasKey(CoordinatesStub::COL_X, $columns);
        $this->assertArrayHasKey(CoordinatesStub::COL_Y, $columns);
        $this->assertArrayHasKey(CoordinatesStub::COL_Z, $columns);
        $this->assertEquals(Type::FLOAT, $columns[CoordinatesStub::COL_X]);
        $this->assertEquals(Type::FLOAT, $columns[CoordinatesStub::COL_Y]);
        $this->assertEquals(Type::FLOAT, $columns[CoordinatesStub::COL_Y]);
    }

    public function testGetColumnNames()
    {
        $columns = $this->coords->getColumnNames();
        $this->assertCount(3, $columns);
        $this->assertArrayHasKey(0, $columns);
        $this->assertArrayHasKey(1, $columns);
        $this->assertArrayHasKey(2, $columns);
        $this->assertEquals(CoordinatesStub::COL_X, $columns[0]);
        $this->assertEquals(CoordinatesStub::COL_Y, $columns[1]);
        $this->assertEquals(CoordinatesStub::COL_Z, $columns[2]);
    }

    public function testConvertToPhpValuesAssoc()
    {
        $connection = $this->table->getDbManager()->getConnection();
        $rows = $connection->createQueryBuilder()
            ->select($this->table->getColumnNames())
            ->from($this->table->getTableName())
            ->execute()
            ->fetchAll();

        $this->assertCount(2, $rows);
        $mappedRow = $this->invokeMethodFromObject($this->table, 'convertToPhpValues', $rows[1]);
        $this->assertArrayHasKey(TableStub::ID, $mappedRow);
        $this->assertTrue(is_int($mappedRow[TableStub::ID]));
        $this->assertEquals(3, $mappedRow[TableStub::ID]);
        $this->assertArrayHasKey(TableStub::A, $mappedRow);
        $this->assertTrue(is_int($mappedRow[TableStub::A]));
        $this->assertEquals(4, $mappedRow[TableStub::A]);
        $this->assertArrayHasKey(TableStub::B, $mappedRow);
        $this->assertTrue(is_string($mappedRow[TableStub::B]));
        $this->assertEquals('b string', $mappedRow[TableStub::B]);
        $this->assertArrayHasKey(TableStub::C, $mappedRow);
        $this->assertInstanceOf(\DateTimeImmutable::class, $mappedRow[TableStub::C]);
    }

    public function testConvertToPhpValuesPartiallyAssoc()
    {
        $connection = $this->table->getDbManager()->getConnection();
        $rows = $connection->createQueryBuilder()
            ->select(['a', 'c'])
            ->from($this->table->getTableName())
            ->execute()
            ->fetchAll();

        $this->assertCount(2, $rows);
        $mappedRow = $this->invokeMethodFromObject($this->table, 'convertToPhpValues', $rows[1]);
        $this->assertCount(2, $mappedRow);
        $this->assertArrayHasKey(TableStub::A, $mappedRow);
        $this->assertTrue(is_int($mappedRow[TableStub::A]));
        $this->assertEquals(4, $mappedRow[TableStub::A]);
        $this->assertArrayHasKey(TableStub::C, $mappedRow);
        $this->assertInstanceOf(\DateTimeImmutable::class, $mappedRow[TableStub::C]);
    }

    public function testConvertToPhpValuesNumeric()
    {
        $connection = $this->table->getDbManager()->getConnection();
        $rows = $connection->createQueryBuilder()
            ->select($this->table->getColumnNames())
            ->from($this->table->getTableName())
            ->execute()
            ->fetchAll(\PDO::FETCH_NUM);

        $this->assertCount(2, $rows);
        $mappedRow = $this->invokeMethodFromObject($this->table, 'convertToPhpValues', $rows[1]);
        $this->assertArrayHasKey(0, $mappedRow);
        $this->assertTrue(is_int($mappedRow[0]));
        $this->assertEquals(3, $mappedRow[0]);
        $this->assertArrayHasKey(1, $mappedRow);
        $this->assertTrue(is_int($mappedRow[1]));
        $this->assertEquals(4, $mappedRow[1]);
        $this->assertArrayHasKey(2, $mappedRow);
        $this->assertTrue(is_string($mappedRow[2]));
        $this->assertEquals('b string', $mappedRow[2]);
        $this->assertArrayHasKey(3, $mappedRow);
        $this->assertInstanceOf(\DateTimeImmutable::class, $mappedRow[3]);
    }

    public function testConvertToPhpValuesPartiallyNumericFails()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(RuntimeException::INDICES_MISSING);

        $connection = $this->table->getDbManager()->getConnection();
        $rows = $connection->createQueryBuilder()
            ->select(['a', 'c'])
            ->from($this->table->getTableName())
            ->execute()
            ->fetchAll(\PDO::FETCH_NUM);

        $this->assertCount(2, $rows);
        $this->invokeMethodFromObject($this->table, 'convertToPhpValues', $rows[1]);
    }

    public function testInsertWithTableColumnTypes()
    {
        $a = mt_rand();
        $b = 'foobar';
        $c = new \DateTimeImmutable(sprintf('@%d', mt_rand(1, time())));
        $this->table->insert(['a' => $a, 'b' => $b, 'c' => $c]);
        $rows = $this->table->find(['a' => $a, 'b' => $b]);
        $this->assertCount(1, $rows);
        $data = reset($rows);
        $this->assertArrayHasKey('c', $data);
        $this->assertInstanceOf(\DateTimeImmutable::class, $data['c']);
        $this->assertEquals($c, $data['c']);
    }

    public function testInsertWithoutTypes()
    {
        $a = mt_rand();
        $b = 'foobar';
        $c = new \DateTimeImmutable(sprintf('@%d', mt_rand(1, time())));
        $this->table->insert(['a' => $a, 'b' => $b, 'c' => $c->format('Y-m-d H:i:s')], []);
        $rows = $this->table->find(['a' => $a, 'b' => $b]);
        $this->assertCount(1, $rows);
        $data = reset($rows);
        $this->assertArrayHasKey('c', $data);
        $this->assertEquals($c, $data['c']);
    }

    public function testUpdateWithTableColumnTypes()
    {
        $a = mt_rand();
        $b = 'foobar';
        $c = new \DateTimeImmutable(sprintf('@%d', mt_rand(1, time())));
        $newC = new \DateTimeImmutable(sprintf('@%d', mt_rand(1, time())));
        ;
        $this->table->insert(['a' => $a, 'b' => $b, 'c' => $c]);
        $this->table->update(['c' => $newC], ['a' => $a, 'b' => $b]);
        $rows = $this->table->find(['a' => $a, 'b' => $b]);
        $this->assertEquals($newC, $rows[0]['c']);
    }

    public function testUpdateWithoutTypes()
    {
        $a = mt_rand();
        $b = 'foobar';
        $c = new \DateTimeImmutable(sprintf('@%d', mt_rand(1, time())));
        $newC = new \DateTimeImmutable(sprintf('@%d', mt_rand(1, time())));
        ;
        $this->table->insert(['a' => $a, 'b' => $b, 'c' => $c]);
        $this->table->update(['c' => $newC->format('Y-m-d H:i:s')], ['a' => $a, 'b' => $b], []);
        $rows = $this->table->find(['a' => $a, 'b' => $b]);
        $this->assertEquals($newC, $rows[0]['c']);
    }

    public function testDeleteWithTableColumnTypes()
    {
        $a = mt_rand();
        $b = 'foobar';
        $c = new \DateTimeImmutable(sprintf('@%d', mt_rand(1, time())));
        $this->table->insert(['a' => $a, 'b' => $b, 'c' => $c]);
        $this->table->delete(['a' => $a, 'c' => $c]);
        $this->assertCount(0, $this->table->find(['a' => $a, 'b' => $b]));
    }

    public function testDeleteWithoutTypes()
    {
        $a = mt_rand();
        $b = 'foobar';
        $c = new \DateTimeImmutable(sprintf('@%d', mt_rand(1, time())));
        $this->table->insert(['a' => $a, 'b' => $b, 'c' => $c]);
        $this->table->delete(['a' => $a, 'c' => $c->format('Y-m-d H:i:s')], []);
        $this->assertCount(0, $this->table->find(['a' => $a, 'b' => $b]));
    }
}
