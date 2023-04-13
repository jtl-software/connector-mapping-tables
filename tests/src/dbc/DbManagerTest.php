<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Exception;
use PHPUnit\Framework\ExpectationFailedException;
use RuntimeException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use Throwable;

class DbManagerTest extends TestCase
{
    /**
     * @throws DBALException
     * @throws Exception
     */
    public function testRegisterTable(): void
    {
        new CoordinatesStub($this->getDBManager());
        $schemaTables = $this->getDbManager()->getSchemaTables();
        $this->assertCount(2, $schemaTables);
        $this->assertInstanceOf(Table::class, $schemaTables[1]);
        $tables = $this->getDbManager()->getTables();
        $this->assertCount(2, $tables);
        $this->assertInstanceOf(CoordinatesStub::class, $tables[1]);
    }

    /**
     * @throws DBALException
     * @throws Exception
     */
    public function testTablesPrefix(): void
    {
        new CoordinatesStub($this->getDbManager());
        $this->assertTrue($this->getDbManager()->hasTablesPrefix());
        $this->assertEquals(self::TABLE_PREFIX, $this->getDbManager()->getTablesPrefix());
        $tables = $this->getDbManager()->getTables();
        /** @var CoordinatesStub $coordinateTable */
        $coordinateTable = $tables[1];
        $this->assertEquals('coordinates', $coordinateTable->getName());
        $schemaTables = $this->getDbManager()->getSchemaTables();
        $this->assertEquals(self::TABLE_PREFIX, \substr($schemaTables[1]->getName(), 0, \strlen(self::TABLE_PREFIX)));
    }

    /**
     * @throws DBALException
     * @throws Exception
     */
    public function testHasSchemaUpdates(): void
    {
        $this->assertFalse($this->getDBManager()->hasSchemaUpdates());
        new CoordinatesStub($this->getDbManager());
        $tables = $this->getDbManager()->getSchemaTables();
        $this->assertCount(2, $tables);
        $this->assertTrue($this->getDbManager()->hasSchemaUpdates());
    }

    /**
     * @throws DBALException
     * @throws Exception
     */
    public function testGetSchemaUpdates(): void
    {
        $dbManager = $this->getDBManager();
        $this->assertCount(0, $dbManager->getSchemaUpdates());
        new CoordinatesStub($this->getDbManager());
        $this->assertCount(1, $dbManager->getSchemaUpdates());
    }

    /**
     * @throws Throwable
     * @throws DBALException
     */
    public function testUpdateDatabaseSchema(): void
    {
        new CoordinatesStub($this->getDbManager());
        $this->assertTrue($this->getDbManager()->hasSchemaUpdates());
        $this->getDbManager()->updateDatabaseSchema();
        $this->assertFalse($this->getDbManager()->hasSchemaUpdates());
    }

    /**
     * @throws DBALException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testCreateFromParams(): void
    {
        $dbm = DbManager::createFromParams(['url' => 'sqlite:///:memory:']);
        $this->assertInstanceOf(DbManager::class, $dbm);
    }

    /**
     * @throws DBALException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testCreateSchemaAssetsFilterCallback(): void
    {
        $dbm      = DbManager::createFromParams(['url' => 'sqlite:///:memory:']);
        $callback = $dbm->createSchemaAssetsFilterCallback();
        $tables   = $this->createTableStubs($dbm);

        foreach ($tables as $table) {
            $this->assertTrue($callback($table->getTableName()));
        }

        $tablesCount = \count($tables);

        for ($i = 0; $i < $tablesCount; $i++) {
            $this->assertFalse($callback(\uniqid('nxtbl-', false)));
        }
    }

    /**
     * @param DbManager $dbManager
     * @param int|null  $amount
     *
     * @return array<int, AbstractTable>
     * @throws Exception
     */
    protected function createTableStubs(DbManager $dbManager, int $amount = null): array
    {
        if (\is_null($amount)) {
            $amount = \random_int(1, 10);
        }

        return \array_map(function (DbManager $dbManager) {
            return new class ($dbManager) extends AbstractTable {
                protected string $tableName;

                public function __construct(DbManager $dbManager)
                {
                    $this->tableName = \uniqid('tbl-', false);
                    parent::__construct($dbManager);
                }

                public function getTableName(): string
                {
                    return $this->tableName;
                }

                protected function getName(): string
                {
                    return $this->tableName;
                }

                protected function createTableSchema(Table $tableSchema): void
                {
                    $tableSchema->addColumn('id', Types::INTEGER);
                    $tableSchema->setPrimaryKey(['id']);
                }
            };
        }, \array_fill(0, $amount, $dbManager));
    }

    /**
     * @dataProvider tableNameProvider
     *
     * @param string      $shortName
     * @param string|null $tablesPrefix
     * @param string      $expectedTableName
     *
     * @throws DBALException
     * @throws DbcRuntimeException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     */
    public function testCreateTableName(string $shortName, ?string $tablesPrefix, string $expectedTableName): void
    {
        $dbm             = DbManager::createFromParams(['url' => 'sqlite:///:memory:'], null, $tablesPrefix);
        $actualTableName = $dbm->createTableName($shortName);
        $this->assertEquals($expectedTableName, $actualTableName);
    }

    /**
     * @throws DBALException
     * @throws DbcRuntimeException
     */
    public function testCreateTableNameEmptyString(): void
    {
        $this->expectException(DbcRuntimeException::class);
        $this->expectExceptionCode(DbcRuntimeException::TABLE_NAME_EMPTY);
        $dbm = DbManager::createFromParams(['url' => 'sqlite:///:memory:'], null, 'foo');
        $dbm->createTableName('');
    }

    /**
     * @return array<array{0: 'foo'|'post', 1: null|'pre', 2: 'foo'|'prepost'}>
     */
    public function tableNameProvider(): array
    {
        return [
            ['foo', null, 'foo'],
            ['post', 'pre', 'prepost'],
        ];
    }

    /**
     * @throws DBALException
     * @throws Exception
     * @throws Throwable
     */
    protected function setUp(): void
    {
        new TableStub($this->getDBManager());
        parent::setUp();
    }
}
