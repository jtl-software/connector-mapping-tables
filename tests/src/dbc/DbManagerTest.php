<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */
namespace Jtl\Connector\Dbc;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;

class DbManagerTest extends TestCase
{
    protected function setUp(): void
    {
        new TableStub($this->getDBManager());
        parent::setUp();
    }


    public function testRegisterTable()
    {
        new CoordinatesStub($this->getDBManager());
        $schemaTables = $this->getDbManager()->getSchemaTables();
        $this->assertCount(2, $schemaTables);
        $this->assertInstanceOf(Table::class, $schemaTables[1]);
        $tables = $this->getDbManager()->getTables();
        $this->assertCount(2, $tables);
        $this->assertInstanceOf(CoordinatesStub::class, $tables[1]);
    }

    public function testTablesPrefix()
    {
        new CoordinatesStub($this->getDbManager());
        $this->assertTrue($this->getDbManager()->hasTablesPrefix());
        $this->assertEquals(self::TABLE_PREFIX, $this->getDbManager()->getTablesPrefix());
        $tables = $this->getDbManager()->getTables();
        /** @var CoordinatesStub $coordinateTable */
        $coordinateTable = $tables[1];
        $this->assertEquals('coordinates', $coordinateTable->getName());
        $schemaTables = $this->getDbManager()->getSchemaTables();
        $this->assertEquals(self::TABLE_PREFIX, substr($schemaTables[1]->getName(), 0, strlen(self::TABLE_PREFIX)));
    }

    public function testHasSchemaUpdates()
    {
        $this->assertFalse($this->getDBManager()->hasSchemaUpdates());
        new CoordinatesStub($this->getDbManager());
        $tables = $this->getDbManager()->getSchemaTables();
        $this->assertCount(2, $tables);
        $this->assertTrue($this->getDbManager()->hasSchemaUpdates());
    }

    public function testGetSchemaUpdates()
    {
        $dbManager = $this->getDBManager();
        $this->assertCount(0, $dbManager->getSchemaUpdates());
        new CoordinatesStub($this->getDbManager());
        $this->assertCount(1, $dbManager->getSchemaUpdates());
    }

    public function testUpdateDatabaseSchema()
    {
        new CoordinatesStub($this->getDbManager());
        $this->assertTrue($this->getDbManager()->hasSchemaUpdates());
        $this->getDbManager()->updateDatabaseSchema();
        $this->assertFalse($this->getDbManager()->hasSchemaUpdates());
    }

    public function testCreateFromPDO()
    {
        $dbm = DbManager::createFromPDO($this->getPDO());
        $this->assertInstanceOf(DbManager::class, $dbm);
    }

    public function testCreateFromParams()
    {
        $dbm = DbManager::createFromParams(['url' => 'sqlite:///:memory:']);
        $this->assertInstanceOf(DbManager::class, $dbm);
    }

    public function testCreateSchemaAssetsFilterCallback()
    {
        $dbm = DbManager::createFromParams(['url' => 'sqlite:///:memory:']);
        $callback = $dbm->createSchemaAssetsFilterCallback();
        $tables = $this->createTableStubs($dbm);

        foreach ($tables as $table) {
            $this->assertTrue($callback($table->getTableName()));
        }

        for ($i = 0; $i < count($tables); $i++) {
            $this->assertFalse($callback(uniqid('nxtbl-')));
        }
    }

    /**
     * @dataProvider tableNameProvider
     *
     * @param string $shortName
     * @param string|null $tablesPrefix
     * @param string $expectedTableName
     * @throws DBALException
     */
    public function testCreateTableName(string $shortName, ?string $tablesPrefix, string $expectedTableName)
    {
        $dbm = DbManager::createFromParams(['url' => 'sqlite:///:memory:'], null, $tablesPrefix);
        $actualTableName = $dbm->createTableName($shortName);
        $this->assertEquals($expectedTableName, $actualTableName);
    }

    public function testCreateTableNameEmptyString()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(RuntimeException::TABLE_NAME_EMPTY);
        $dbm = DbManager::createFromParams(['url' => 'sqlite:///:memory:'], null, 'foo');
        $dbm->createTableName('');
    }

    /**
     * @return array
     */
    public function tableNameProvider(): array
    {
        return [
            ['foo', null, 'foo'],
            ['post', 'pre', 'prepost'],
        ];
    }

    /**
     * @param DbManager $dbManager
     * @param int|null $amount
     * @return AbstractTable[]
     */
    protected function createTableStubs(DbManager $dbManager, int $amount = null): array
    {
        if (is_null($amount)) {
            $amount = mt_rand(1, 10);
        }

        return array_map(function (DbManager $dbManager) {
            return new class($dbManager) extends AbstractTable {
                protected $tableName;

                public function __construct(DbManager $dbManager)
                {
                    $this->tableName = uniqid('tbl-');
                    parent::__construct($dbManager);
                }

                protected function getName(): string
                {
                    return $this->tableName;
                }

                protected function createTableSchema(Table $tableSchema): void
                {
                    $tableSchema->addColumn('id', Type::INTEGER);
                    $tableSchema->setPrimaryKey(['id']);
                }
            };
        }, array_fill(0, $amount, $dbManager));
    }
}
