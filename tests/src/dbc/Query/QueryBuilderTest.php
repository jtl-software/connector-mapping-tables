<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc\Query;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\SchemaException;
use Jtl\Connector\Dbc\CoordinatesStub;
use Jtl\Connector\Dbc\Schema\TableRestriction;
use Jtl\Connector\Dbc\TestCase;
use Throwable;

class QueryBuilderTest extends TestCase
{
    /**
     * @var QueryBuilder
     */
    protected QueryBuilder $qb;

    /**
     * @var CoordinatesStub
     */
    protected CoordinatesStub $coordsTable;

    /**
     * @var string
     */
    protected string $tableExpression = 'yolo';

    /**
     * @var string[]
     */
    protected array $globalIdentifiers = ['foo' => 'bar'];

    public function testTableRestrictionWithSelect(): void
    {
        $this->qb
            ->select('something')
            ->from($this->tableExpression)
            ->where('yo = :yo')
            ->orWhere('hanni = nanni');

        $sql        = $this->qb->getSQL();
        $whereSplit = \explode('WHERE', $sql);
        $andSplit   = \array_map([$this, 'myTrim'], \explode('AND', $whereSplit[1]));
        $this->assertContains('foo = :glob_id_foo', $andSplit);
    }

    public function testTableRestrictionWithInsert(): void
    {
        $this->qb
            ->insert($this->tableExpression)
            ->values(['a' => ':a', 'b' => ':b']);

        $sql          = $this->qb->getSQL();
        $valuesSplit  = \explode('VALUES', $sql);
        $valuesString = \str_replace(['(', ')'], ['', ''], $valuesSplit[1]);
        $values       = \array_map('trim', \explode(',', $valuesString));
        $this->assertContains(':glob_id_foo', $values);
    }

    public function testGlobalIdentifierWithUpdate(): void
    {
        $this->qb->update($this->tableExpression)->set('key', 'value');
        $sql = $this->qb->getSQL();

        $setSplit    = \explode('SET', $sql);
        $paramsSplit = \explode('WHERE', $setSplit[1]);

        $setParams = \array_map('trim', \explode(',', $paramsSplit[0]));
        $sets      = [];
        foreach ($setParams as $value) {
            $split           = \array_map('trim', \explode('=', $value));
            $sets[$split[0]] = $split[1];
        }

        $whereParams = \array_map('trim', \explode(',', $paramsSplit[1]));
        $wheres      = [];
        foreach ($whereParams as $value) {
            $split             = \array_map('trim', \explode('=', $value));
            $wheres[$split[0]] = $split[1];
        }

        $this->assertArrayHasKey('foo', $sets);
        $this->assertEquals(':glob_id_foo', $sets['foo']);
        $this->assertArrayHasKey('foo', $wheres);
        $this->assertEquals(':glob_id_foo', $wheres['foo']);
    }

    public function testGlobalIdentifierWithDelete(): void
    {
        $this->qb->delete($this->tableExpression)->where('a = b');
        $sql        = $this->qb->getSQL();
        $whereSplit = \explode('WHERE', $sql);
        $andSplit   = \array_map([$this, 'myTrim'], \explode('AND', $whereSplit[1]));
        $this->assertContains('foo = :glob_id_foo', $andSplit);
    }

    /**
     * @throws DBALException
     * @throws SchemaException
     * @throws Exception
     */
    public function testTableRestriction(): void
    {
        $this->getDBManager()->getConnection()->restrictTable(
            new TableRestriction($this->coordsTable->getTableSchema(), CoordinatesStub::COL_X, 1.)
        );
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

    /**
     * @throws DBALException
     */
    public function testSelectWithLockedFromTableAndCalledFromMethod(): void
    {
        $fromTable   = 'tableau';
        $connection  = $this->getDBManager()->getConnection();
        $qb          = new QueryBuilder($connection, [], $fromTable);
        $actualSql   = $qb->select('a', 'b', 'c')->from('somewhere')->getSQL();
        $expectedSql = 'SELECT a, b, c FROM tableau';
        $this->assertEquals($expectedSql, $actualSql);
    }

    /**
     * @throws DBALException
     */
    public function testSelectWithLockedFromTableAndFromAliasAndNotCalledFromMethod(): void
    {
        $fromTable   = 'tableau';
        $fromAlias   = 't';
        $connection  = $this->getDBManager()->getConnection();
        $qb          = new QueryBuilder($connection, [], $fromTable, $fromAlias);
        $actualSql   = $qb->select('a', 't.b', 't.c')->getSQL();
        $expectedSql = 'SELECT a, t.b, t.c FROM tableau t';
        $this->assertEquals($expectedSql, $actualSql);
    }

    /**
     * @throws DBALException
     */
    public function testInsertWithLockedFromTableAndTableNameInInsert(): void
    {
        $fromTable   = 'tableau';
        $connection  = $this->getDBManager()->getConnection();
        $qb          = new QueryBuilder($connection, [], $fromTable);
        $actualSql   = $qb->insert('something')->setValue('foo', ':bar')->getSQL();
        $expectedSql = 'INSERT INTO tableau (foo) VALUES(:bar)';
        $this->assertEquals($expectedSql, $actualSql);
    }

    /**
     * @throws DBALException
     */
    public function testInsertWithLockedFromTableAndNotTableNameInInsert(): void
    {
        $fromTable   = 'tableau';
        $connection  = $this->getDBManager()->getConnection();
        $qb          = new QueryBuilder($connection, [], $fromTable);
        $actualSql   = $qb->insert()->getSQL();
        $expectedSql = 'INSERT INTO tableau () VALUES()';
        $this->assertEquals($expectedSql, $actualSql);
    }

    /**
     * @throws DBALException
     */
    public function testUpdateWithLockedFromTableAndFromAliasAndTableNameInUpdate(): void
    {
        $fromTable   = 'tableau';
        $fromAlias   = 't';
        $connection  = $this->getDBManager()->getConnection();
        $qb          = new QueryBuilder($connection, [], $fromTable, $fromAlias);
        $actualSql   = $qb->update('foobar')->getSQL();
        $expectedSql = 'UPDATE tableau t SET ';
        $this->assertEquals($expectedSql, $actualSql);
    }

    /**
     * @throws DBALException
     */
    public function testUpdateWithLockedFromTableAndNotTableNameInUpdate(): void
    {
        $fromTable   = 'tableau';
        $connection  = $this->getDBManager()->getConnection();
        $qb          = new QueryBuilder($connection, [], $fromTable);
        $actualSql   = $qb->update()->getSQL();
        $expectedSql = 'UPDATE tableau SET ';
        $this->assertEquals($expectedSql, $actualSql);
    }

    /**
     * @throws DBALException
     */
    public function testDeleteWithLockedFromTableAndTableNameInDelete(): void
    {
        $fromTable   = 'tableau';
        $connection  = $this->getDBManager()->getConnection();
        $qb          = new QueryBuilder($connection, [], $fromTable);
        $actualSql   = $qb->delete('foobar')->getSQL();
        $expectedSql = 'DELETE FROM tableau';
        $this->assertEquals($expectedSql, $actualSql);
    }

    /**
     * @throws DBALException
     */
    public function testDeleteWithLockedFromTableAndFromAliasAndTableNameNotInDelete(): void
    {
        $fromTable   = 'tableau';
        $fromAlias   = 't';
        $connection  = $this->getDBManager()->getConnection();
        $qb          = new QueryBuilder($connection, [], $fromTable, $fromAlias);
        $actualSql   = $qb->delete()->getSQL();
        $expectedSql = 'DELETE FROM tableau t';
        $this->assertEquals($expectedSql, $actualSql);
    }

    public function myTrim($str): string
    {
        return \trim($str, " \t\n\r\0\x0B()");
    }

    /**
     * @throws DBALException
     * @throws \Exception
     * @throws Throwable
     */
    protected function setUp(): void
    {
        $this->coordsTable = new CoordinatesStub($this->getDBManager());
        $this->qb          = new QueryBuilder(
            $this->getDBManager()->getConnection(),
            [$this->tableExpression => $this->globalIdentifiers]
        );
        parent::setUp();
        $this->insertFixtures($this->coordsTable, self::getCoordinatesFixtures());
    }
}
