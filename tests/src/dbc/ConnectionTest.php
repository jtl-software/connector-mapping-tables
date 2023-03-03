<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\DBAL\Schema\SchemaException;
use Jtl\Connector\Dbc\Query\QueryBuilder;
use Jtl\Connector\Dbc\Schema\TableRestriction;
use Throwable;

class ConnectionTest extends TestCase
{
    protected Connection|\Doctrine\DBAL\Connection $connection;

    /**
     * @throws DBALException
     * @throws SchemaException
     * @throws Exception
     */
    public function testInsertWithTableRestriction(): void
    {
        $this->assertEquals(2, $this->countRows($this->table->getTableName()));
        $this->connection->restrictTable(
            new TableRestriction($this->table->getTableSchema(), TableStub::B, 'b string')
        );
        $data = [
            TableStub::A => 25,
            TableStub::B => 'another string',
            TableStub::C => '2015-03-25 13:12:25',
        ];
        $this->assertEquals(1, $this->connection->insert($this->table->getTableName(), $data));
        $this->assertEquals(3, $this->countRows($this->table->getTableName()));
        $qb   = $this->connection->createQueryBuilder();
        $stmt = $qb
            ->select($this->table->getColumnNames())
            ->from($this->table->getTableName())
            ->where(TableStub::A . ' = :a')
            ->setParameter('a', 25)->execute();

        $result = $stmt->fetchAll();
        $this->assertCount(1, $result);
        $row = $result[0];
        $this->assertArrayHasKey(TableStub::B, $row);
        $this->assertEquals('b string', $row[TableStub::B]);
    }

    /**
     * @throws DBALException
     * @throws SchemaException
     * @throws Exception
     */
    public function testUpdateWithTableRestriction(): void
    {
        $this->assertEquals(2, $this->countRows($this->table->getTableName()));
        $this->connection->restrictTable(
            new TableRestriction($this->table->getTableSchema(), TableStub::B, 'b string')
        );
        $data = [
            TableStub::A => 25,
            TableStub::B => 'another string',
            TableStub::C => '2019-02-23 13:12:25',
        ];

        $identifier = [TableStub::B => 'yolo'];
        $this->connection->update($this->table->getTableName(), $data, $identifier);
        $qb   = $this->connection->createQueryBuilder();
        $stmt = $qb
            ->select($this->table->getColumnNames())
            ->from($this->table->getTableName())
            ->where(TableStub::A . ' = :a')
            ->setParameter('a', 25)->execute();

        $result = $stmt->fetchAll();
        $this->assertCount(1, $result);
        $row = $result[0];
        $this->assertArrayHasKey(TableStub::B, $row);
        $this->assertEquals('b string', $row[TableStub::B]);
    }

    /**
     * @throws DBALException
     * @throws SchemaException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testDeleteWithTableRestriction(): void
    {
        $this->assertEquals(2, $this->countRows($this->table->getTableName()));
        $this->connection->restrictTable(
            new TableRestriction($this->table->getTableSchema(), TableStub::B, 'b string')
        );
        $this->connection->delete($this->table->getTableName(), [TableStub::B => 'something else']);
        $this->assertEquals(1, $this->countRows($this->table->getTableName()));
        $qb   = $this->connection->createQueryBuilder();
        $stmt = $qb
            ->select($this->table->getColumnNames())
            ->from($this->table->getTableName())
            ->execute();

        $result = $stmt->fetchAll();
        $this->assertCount(0, $result);
    }

    /**
     * @throws DBALException
     * @throws SchemaException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testDeleteWithTableRestrictionAndAdditionalIdentifier(): void
    {
        $this->assertEquals(2, $this->countRows($this->table->getTableName()));
        $this->connection->restrictTable(
            new TableRestriction($this->table->getTableSchema(), TableStub::B, 'b string')
        );
        $this->connection->delete($this->table->getTableName(), [TableStub::A => 99]);
        $this->assertEquals(2, $this->countRows($this->table->getTableName()));
    }

    /**
     * @throws SchemaException
     * @throws DBALException
     */
    public function testHasTableRestriction(): void
    {
        $this->assertFalse($this->connection->hasTableRestriction($this->table->getTableName(), TableStub::B));
        $this->connection->restrictTable(
            new TableRestriction($this->table->getTableSchema(), TableStub::B, 'b string')
        );
        $this->assertTrue($this->connection->hasTableRestriction($this->table->getTableName(), TableStub::B));
    }

    /**
     * @throws DBALException
     * @throws SchemaException
     * @throws \Exception
     */
    public function testGetTableRestrictionsAll(): void
    {
        $coordStub = new CoordinatesStub($this->getDBManager());
        $this->assertEmpty($this->connection->getTableRestrictions());
        $this->connection->restrictTable(
            new TableRestriction($this->table->getTableSchema(), TableStub::B, 'b string')
        );
        $this->connection->restrictTable(
            new TableRestriction($coordStub->getTableSchema(), CoordinatesStub::COL_X, 1.)
        );

        $restrictions = $this->connection->getTableRestrictions();
        $this->assertArrayHasKey($this->table->getTableName(), $restrictions);
        $this->assertArrayHasKey(TableStub::B, $restrictions[$this->table->getTableName()]);
        $this->assertEquals('b string', $restrictions[$this->table->getTableName()][TableStub::B]);

        $this->assertArrayHasKey($coordStub->getTableName(), $restrictions);
        $this->assertArrayHasKey(CoordinatesStub::COL_X, $restrictions[$coordStub->getTableName()]);
        $this->assertEquals(1., $restrictions[$coordStub->getTableName()][CoordinatesStub::COL_X]);
    }

    /**
     * @throws DBALException
     * @throws SchemaException
     * @throws \Exception
     */
    public function testGetTableRestrictionsFromTable(): void
    {
        $coordStub = new CoordinatesStub($this->getDBManager());
        $this->assertEmpty($this->connection->getTableRestrictions());
        $this->connection->restrictTable(
            new TableRestriction($this->table->getTableSchema(), TableStub::B, 'b string')
        );
        $this->connection->restrictTable(
            new TableRestriction($coordStub->getTableSchema(), CoordinatesStub::COL_X, 1.)
        );
        $restrictions = $this->connection->getTableRestrictions($coordStub->getTableName());
        $this->assertCount(1, $restrictions);
        $this->assertArrayHasKey(CoordinatesStub::COL_X, $restrictions);
        $this->assertEquals(1., $restrictions[CoordinatesStub::COL_X]);
    }

    public function testCreateQueryBuilder(): void
    {
        $this->assertInstanceOf(QueryBuilder::class, $this->connection->createQueryBuilder());
    }

    /**
     * @throws DBALException
     * @throws Exception
     */
    public function testInsert(): void
    {
        $data = [
            TableStub::A => 25,
            TableStub::B => 'another string',
            TableStub::C => '2019-01-21 15:25:02',
        ];
        $this->assertEquals(1, $this->connection->insert($this->table->getTableName(), $data));
        $this->assertEquals(3, $this->countRows($this->table->getTableName()));
    }

    /**
     * @throws DBALException
     * @throws \Exception
     */
    public function testMultiInsert(): void
    {
        $data   = [];
        $data[] = [
            TableStub::A => 25,
            TableStub::B => 'another string',
            TableStub::C => '2019-01-21 15:25:02',
        ];

        $data[] = [
            TableStub::A => 27,
            TableStub::B => 'Yolo string',
            TableStub::C => '2011-01-01 15:25:02',
        ];

        $this->assertEquals(2, $this->connection->multiInsert($this->table->getTableName(), $data));
        $this->assertEquals(4, $this->countRows($this->table->getTableName()));
    }

    /**
     * @throws \Exception
     */
    public function testMultiInsertThrowsException(): void
    {
        $this->expectException(\Exception::class);

        $data   = [];
        $data[] = [
            TableStub::A => 25,
            TableStub::B => 'another string',
            TableStub::C => '2019-01-21 15:25:02',
        ];

        $this->connection->multiInsert('table_doesnt_exist', $data);
    }

    /**
     * @throws DBALException
     * @throws Exception
     */
    public function testUpdateRow(): void
    {
        $data = [
            TableStub::A => 25,
            TableStub::B => 'another string',
            TableStub::C => '2019-01-21 15:25:02',
        ];

        $identifier = [TableStub::ID => 1];

        $this->assertEquals(1, $this->connection->update($this->table->getTableName(), $data, $identifier));

        $stmt = $this->connection->createQueryBuilder()
                                 ->select($this->table->getColumnNames())
                                 ->from($this->table->getTableName())
                                 ->where(TableStub::ID . ' = :id')
                                 ->setParameter('id', 1)
                                 ->execute();

        $result = $stmt->fetchAll();
        $this->assertCount(1, $result);
        $row = $result[0];
        $this->assertEquals(1, $row[TableStub::ID]);
        $this->assertEquals(25, $row[TableStub::A]);
        $this->assertEquals('another string', $row[TableStub::B]);
        $this->assertEquals('2019-01-21 15:25:02', $row[TableStub::C]);
    }

    /**
     * @throws DBALException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testDeleteRow(): void
    {
        $identifier = [TableStub::ID => 3];
        $this->assertEquals(1, $this->connection->delete($this->table->getTableName(), $identifier));

        $stmt = $this->connection->createQueryBuilder()
                                 ->select($this->table->getColumnNames())
                                 ->from($this->table->getTableName())
                                 ->where(TableStub::ID . ' = :id')
                                 ->setParameter('id', 3)
                                 ->execute();

        $result = $stmt->fetchAll();
        $this->assertCount(0, $result);
        $this->assertEquals(1, $this->countRows($this->table->getTableName()));
    }

    /**
     * @throws Exception
     * @throws DBALException
     * @throws \Exception
     * @throws Throwable
     */
    protected function setUp(): void
    {
        $this->table = new TableStub($this->getDBManager());
        parent::setUp();
        $this->insertFixtures($this->table, self::getTableStubFixtures());
        $params           = [
            'pdo'          => $this->getPDO(),
            'wrapperClass' => Connection::class
        ];
        $config           = null;
        $connection       = DriverManager::getConnection($params, $config);
        $this->connection = $connection;
    }
}
