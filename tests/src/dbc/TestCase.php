<?php

declare(strict_types=1);

/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2016 JTL-Software GmbH
 */

namespace Jtl\Connector\Dbc;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Jtl\UnitTest\TestCase as JtlTestCase;

abstract class TestCase extends JtlTestCase
{
    public const TABLE_PREFIX = 'pre_';
    public const SCHEMA       = \TESTROOT . '/tmp/db.sqlite';

    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @var DbManagerStub
     */
    private $dbManager;

    /**
     * @var AbstractTable
     */
    protected $table;

    protected function setUp(): void
    {
        parent::setUp();
        if ($this->getDBManager()->hasSchemaUpdates()) {
            $this->getDBManager()->updateDatabaseSchema();
        }
    }

    /**
     * @return PDO
     */
    protected function getPDO()
    {
        if (!$this->pdo instanceof \PDO) {
            if (!\is_dir(\dirname(self::SCHEMA))) {
                \mkdir(\dirname(self::SCHEMA));
            }

            if (\file_exists(self::SCHEMA)) {
                \unlink(self::SCHEMA);
            }
            $this->pdo = new \PDO('sqlite:' . self::SCHEMA);
        }
        return $this->pdo;
    }

    /**
     * @return DbManager|DbManagerStub
     * @throws DBALException
     */
    protected function getDBManager()
    {
        if (!$this->dbManager instanceof DbManagerStub) {
            $this->dbManager = DbManagerStub::createFromPDO($this->getPDO(), null, self::TABLE_PREFIX);
        }
        return $this->dbManager;
    }

    /**
     * @param string $tableName
     * @param array $conditions
     * @return int
     * @throws DBALException
     */
    protected function countRows(string $tableName, array $conditions = []): int
    {
        $connection = $this->getDbManager()->getConnection();

        $qb = (new QueryBuilder($connection))
            ->select($connection->getDatabasePlatform()->getCountExpression('*'))
            ->from($tableName);

        foreach ($conditions as $column => $value) {
            $qb
                ->andWhere(\sprintf('%s = :%s', $column, $column))
                ->setParameter($column, $value);
        }

        return $qb->execute()->fetchColumn();
    }

    /**
     * @param AbstractTable $table
     * @param array $fixtures
     * @throws DBALException
     */
    protected function insertFixtures(AbstractTable $table, array $fixtures)
    {
        foreach ($fixtures as $fixture) {
            $table->insert($fixture);
        }
    }

    /**
     * @return mixed[]
     */
    public static function getTableStubFixtures(): array
    {
        return [
            ["id" => 1, "a" => 1, "b" => "a string", "c" => new \DateTimeImmutable("2017-03-29 00:00:00")],
            ["id" => 3, "a" => 4, "b" => "b string", "c" => new \DateTimeImmutable("2015-03-25 13:12:25")],
        ];
    }

    /**
     * @return mixed[]
     */
    public static function getCoordinatesFixtures(): array
    {
        return [
            ["x" => 1, "y" => 2, "z" => 3],
            ["x" => 1, "y" => 4, "z" => 5.],
            ["x" => 3, "y" => 1, "z" => 2],
            ["x" => 2, "y" => 3, "z" => 1],
        ];
    }
}
