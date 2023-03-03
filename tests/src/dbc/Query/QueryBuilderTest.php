<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */

namespace Jtl\Connector\Dbc\Query;

use Jtl\Connector\Dbc\CoordinatesStub;
use Jtl\Connector\Dbc\TestCase;
use Jtl\Connector\Dbc\Schema\TableRestriction;

class QueryBuilderTest extends TestCase
{
    /**
     * @var QueryBuilder
     */
    protected $qb;

    /**
     * @var CoordinatesStub
     */
    protected $coordsTable;

    /**
     * @var string
     */
    protected $tableExpression = 'yolo';

    /**
     * @var string[]
     */
    protected $globalIdentifiers = ['foo' => 'bar'];

    protected function setUp(): void
    {
        $this->coordsTable = new CoordinatesStub($this->getDBManager());
        $this->qb = new QueryBuilder($this->getDBManager()->getConnection(), [$this->tableExpression => $this->globalIdentifiers]);
        parent::setUp();
        $this->insertFixtures($this->coordsTable, self::getCoordinatesFixtures());
    }

    public function testTableRestrictionWithSelect()
    {
        $this->qb
            ->select('something')
            ->from($this->tableExpression)
            ->where('yo = :yo')
            ->orWhere('hanni = nanni');

        $sql = $this->qb->getSQL();
        $whereSplit = explode('WHERE', $sql);
        $andSplit = array_map([$this, 'myTrim'], explode('AND', $whereSplit[1]));
        $this->assertTrue(in_array('foo = :glob_id_foo', $andSplit, true));
    }

    public function testTableRestrictionWithInsert()
    {
        $this->qb
            ->insert($this->tableExpression)
            ->values(['a' => ':a', 'b' => ':b']);

        $sql = $this->qb->getSQL();
        $valuesSplit = explode('VALUES', $sql);
        $valuesString = str_replace(['(', ')'], ['', ''], $valuesSplit[1]);
        $values = array_map('trim', explode(',', $valuesString));
        $this->assertTrue(in_array(':glob_id_foo', $values, true));
    }

    public function testGlobalIdentifierWithUpdate()
    {
        $this->qb->update($this->tableExpression)->set('key', 'value');
        $sql = $this->qb->getSQL();

        $setSplit = explode('SET', $sql);
        $paramsSplit = explode('WHERE', $setSplit[1]);

        $setParams = array_map('trim', explode(',', $paramsSplit[0]));
        $sets = [];
        foreach ($setParams as $value) {
            $split = array_map('trim', explode('=', $value));
            $sets[$split[0]] = $split[1];
        }

        $whereParams = array_map('trim', explode(',', $paramsSplit[1]));
        $wheres = [];
        foreach ($whereParams as $value) {
            $split = array_map('trim', explode('=', $value));
            $wheres[$split[0]] = $split[1];
        }

        $this->assertArrayHasKey('foo', $sets);
        $this->assertEquals(':glob_id_foo', $sets['foo']);
        $this->assertArrayHasKey('foo', $wheres);
        $this->assertEquals(':glob_id_foo', $wheres['foo']);
    }

    public function testGlobalIdentifierWithDelete()
    {
        $this->qb->delete($this->tableExpression)->where('a = b');
        $sql = $this->qb->getSQL();
        $whereSplit = explode('WHERE', $sql);
        $andSplit = array_map([$this, 'myTrim'], explode('AND', $whereSplit[1]));
        $this->assertTrue(in_array('foo = :glob_id_foo', $andSplit, true));
    }

    public function testTableRestriction()
    {
        $this->getDBManager()->getConnection()->restrictTable(new TableRestriction($this->coordsTable->getTableSchema(), CoordinatesStub::COL_X, 1.));
        $this->assertEquals(4, $this->countRows($this->coordsTable->getTableName()));
        $datasets = $this->coordsTable->findAll();
        $this->assertEquals(3, $datasets[0]['z']);
        $this->assertEquals(5., $datasets[1]['z']);

        $qb = $this->getDBManager()->getConnection()->createQueryBuilder();
        $qb->update($this->coordsTable->getTableName())
            ->set('z', ':z')
            ->setParameter('z', 10.5)
            ->execute();

        $datasets = $this->coordsTable->findAll();
        $this->assertEquals(10.5, $datasets[0]['z']);
        $this->assertEquals(10.5, $datasets[1]['z']);
    }

    public function testSelectWithLockedFromTableAndCalledFromMethod()
    {
        $fromTable = 'tableau';
        $connection = $this->getDBManager()->getConnection();
        $qb = new QueryBuilder($connection, [], $fromTable);
        $actualSql = $qb->select('a', 'b', 'c')->from('somewhere')->getSQL();
        $expectedSql = 'SELECT a, b, c FROM tableau';
        $this->assertEquals($expectedSql, $actualSql);
    }

    public function testSelectWithLockedFromTableAndFromAliasAndNotCalledFromMethod()
    {
        $fromTable = 'tableau';
        $fromAlias = 't';
        $connection = $this->getDBManager()->getConnection();
        $qb = new QueryBuilder($connection, [], $fromTable, $fromAlias);
        $actualSql = $qb->select('a', 't.b', 't.c')->getSQL();
        $expectedSql = 'SELECT a, t.b, t.c FROM tableau t';
        $this->assertEquals($expectedSql, $actualSql);
    }

    public function testInsertWithLockedFromTableAndTableNameInInsert()
    {
        $fromTable = 'tableau';
        $connection = $this->getDBManager()->getConnection();
        $qb = new QueryBuilder($connection, [], $fromTable);
        $actualSql = $qb->insert('something')->setValue('foo', ':bar')->getSQL();
        $expectedSql = 'INSERT INTO tableau (foo) VALUES(:bar)';
        $this->assertEquals($expectedSql, $actualSql);
    }

    public function testInsertWithLockedFromTableAndNotTableNameInInsert()
    {
        $fromTable = 'tableau';
        $connection = $this->getDBManager()->getConnection();
        $qb = new QueryBuilder($connection, [], $fromTable);
        $actualSql = $qb->insert()->getSQL();
        $expectedSql = 'INSERT INTO tableau () VALUES()';
        $this->assertEquals($expectedSql, $actualSql);
    }

    public function testUpdateWithLockedFromTableAndFromAliasAndTableNameInUpdate()
    {
        $fromTable = 'tableau';
        $fromAlias = 't';
        $connection = $this->getDBManager()->getConnection();
        $qb = new QueryBuilder($connection, [], $fromTable, $fromAlias);
        $actualSql = $qb->update('foobar')->getSQL();
        $expectedSql = 'UPDATE tableau t SET ';
        $this->assertEquals($expectedSql, $actualSql);
    }

    public function testUpdateWithLockedFromTableAndNotTableNameInUpdate()
    {
        $fromTable = 'tableau';
        $connection = $this->getDBManager()->getConnection();
        $qb = new QueryBuilder($connection, [], $fromTable);
        $actualSql = $qb->update()->getSQL();
        $expectedSql = 'UPDATE tableau SET ';
        $this->assertEquals($expectedSql, $actualSql);
    }

    public function testDeleteWithLockedFromTableAndTableNameInDelete()
    {
        $fromTable = 'tableau';
        $connection = $this->getDBManager()->getConnection();
        $qb = new QueryBuilder($connection, [], $fromTable);
        $actualSql = $qb->delete('foobar')->getSQL();
        $expectedSql = 'DELETE FROM tableau';
        $this->assertEquals($expectedSql, $actualSql);
    }

    public function testDeleteWithLockedFromTableAndFromAliasAndTableNameNotInDelete()
    {
        $fromTable = 'tableau';
        $fromAlias = 't';
        $connection = $this->getDBManager()->getConnection();
        $qb = new QueryBuilder($connection, [], $fromTable, $fromAlias);
        $actualSql = $qb->delete()->getSQL();
        $expectedSql = 'DELETE FROM tableau t';
        $this->assertEquals($expectedSql, $actualSql);
    }

    public function myTrim($str)
    {
        return trim($str, " \t\n\r\0\x0B()");
    }
}
