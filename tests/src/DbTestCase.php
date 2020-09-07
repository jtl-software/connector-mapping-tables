<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2016 JTL-Software GmbH
 */
namespace Jtl\Connector\MappingTables;
use Doctrine\DBAL\DBALException;
use Jtl\Connector\Dbc\DbManager;
use Jtl\Connector\Dbc\DbManagerStub;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use PHPUnit\DbUnit\Database\DefaultConnection;

abstract class DbTestCase extends \PHPUnit\DbUnit\TestCase
{
    const TABLES_PREFIX = 'pre_';
    const SCHEMA = TESTROOT . '/tmp/db.sqlite';

    /**
     * @var PDO
     */
    protected $pdo;
    /**
     * @var DbManagerStub
     */
    protected $dbManager;

    /**
     * @var YamlDataSet
     */
    protected $yamlDataSet;

    /**
     * @var MappingTableStub
     */
    protected $table;


    protected function setUp(): void
    {
        $this->table = new MappingTableStub($this->getDbManager());
        if($this->getDbManager()->hasSchemaUpdates()){
            $this->getDbManager()->updateDatabaseSchema();
        }
        parent::setUp();
    }

    /**
     * @return PDO
     */
    protected function getPdo()
    {
        if(!$this->pdo instanceof \PDO){
            if(file_exists(self::SCHEMA)){
                unlink(self::SCHEMA);
            }
            $this->pdo = new \PDO('sqlite:' . self::SCHEMA);
        }
        return $this->pdo;
    }

    /**
     * @return DbManager|DbManagerStub
     * @throws DBALException
     */
    protected function getDbManager()
    {
        if(is_null($this->dbManager)){
            //$this->dbManager = DbManagerStub::createFromPDO($this->getConnection()->getConnection(), null, self::TABLES_PREFIX);
            $this->dbManager = DbManager::createFromPDO($this->getConnection()->getConnection(), null, self::TABLES_PREFIX);
        }
        return $this->dbManager;
    }

    /**
     * @return DefaultConnection;
     */
    protected function getConnection()
    {
        return $this->createDefaultDBConnection($this->getPdo(), self::SCHEMA);
    }

    /**
     * @return YamlDataSet
     */
    protected function getYamlDataSet()
    {
        if(!$this->yamlDataSet instanceof YamlDataSet){
            $this->yamlDataSet = new YamlDataSet(TESTROOT . '/files/mapping_table_stub.yaml');
        }
        return $this->yamlDataSet;
    }

    /**
     * @return YamlDataSet
     */
    protected function getDataSet()
    {
        return $this->getYamlDataSet();
    }
}
