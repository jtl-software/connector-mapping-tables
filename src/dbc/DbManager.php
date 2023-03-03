<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

class DbManager
{
    /**
     * @var Connection
     */
    protected Connection $connection;

    /**
     * @var AbstractTable[]
     */
    protected array $tables = [];

    /**
     * @var string|null
     */
    protected ?string $tablesPrefix;

    /**
     * DbManager constructor.
     *
     * @param Connection  $connection
     * @param string|null $tablesPrefix
     */
    public function __construct(Connection $connection, string $tablesPrefix = null)
    {
        $this->connection   = $connection;
        $this->tablesPrefix = $tablesPrefix;
    }

    /**
     * @param \PDO               $pdo
     * @param Configuration|null $config
     * @param string|null        $tablesPrefix
     *
     * @return DbManager
     * @throws DBALException
     * @deprecated Is getting removed in a future release. Use static::createFromParams() instead.
     *
     */
    public static function createFromPDO(
        \PDO          $pdo,
        Configuration $config = null,
        string        $tablesPrefix = null
    ): DbManager {
        $params     = [
            'pdo'          => $pdo,
            'wrapperClass' => Connection::class
        ];
        $connection = DriverManager::getConnection($params, $config);

        return new static($connection, $tablesPrefix);
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @param string[]           $params
     * @param Configuration|null $config
     * @param string|null        $tablesPrefix
     *
     * @return DbManager
     * @throws DBALException
     */
    public static function createFromParams(
        array         $params,
        Configuration $config = null,
        string        $tablesPrefix = null
    ): DbManager {
        $params['wrapperClass'] = Connection::class;
        $connection             = DriverManager::getConnection($params, $config);

        return new static($connection, $tablesPrefix);
    }

    /**
     * @param AbstractTable $table
     *
     * @return DbManager
     */
    public function registerTable(AbstractTable $table): DbManager
    {
        $this->tables[$table->getTableName()] = $table;

        return $this;
    }

    /**
     * @return string[]
     * @throws DBALException
     */
    public function getSchema(): array
    {
        return (new Schema($this->getSchemaTables()))->toSql($this->connection->getDatabasePlatform());
    }

    /**
     * @return Table[]
     * @throws DBALException
     */
    protected function getSchemaTables(): array
    {
        return \array_map(static function (AbstractTable $table) {
            return $table->getTableSchema();
        }, $this->getTables());
    }

    /**
     * @return AbstractTable[]
     */
    protected function getTables(): array
    {
        return \array_values($this->tables);
    }

    /**
     * @return boolean
     * @throws DBALException
     */
    public function hasSchemaUpdates(): bool
    {
        return \count($this->getSchemaUpdates()) > 0;
    }

    /**
     * @return string[]
     * @throws DBALException
     */
    public function getSchemaUpdates(): array
    {
        $originalSchemaAssetsFilter = $this->connection->getConfiguration()->getSchemaAssetsFilter();
        $this->connection->getConfiguration()->setSchemaAssetsFilter($this->createSchemaAssetsFilterCallback());
        $fromSchema       = $this->connection->getSchemaManager()->createSchema();
        $updateStatements = $fromSchema->getMigrateToSql(
            new Schema($this->getSchemaTables()),
            $this->connection->getDatabasePlatform()
        );
        $this->connection->getConfiguration()->setSchemaAssetsFilter($originalSchemaAssetsFilter);
        return $updateStatements;
    }

    /**
     * @return callable
     */
    public function createSchemaAssetsFilterCallback(): callable
    {
        return function (string $tableName) {
            $tableNames = \array_map(static function (AbstractTable $table) {
                return $table->getTableName();
            }, $this->getTables());

            return \in_array($tableName, $tableNames, true);
        };
    }

    /**
     * @throws \Throwable
     */
    public function updateDatabaseSchema(): void
    {
        $this->connection->transactional(function ($connection) {
            foreach ($this->getSchemaUpdates() as $ddl) {
                $connection->executeQuery($ddl);
            }
        });
    }

    /**
     * @return boolean
     */
    public function hasTablesPrefix(): bool
    {
        return \is_string($this->tablesPrefix) && \strlen($this->tablesPrefix) > 0;
    }

    /**
     * @return string|null
     */
    public function getTablesPrefix(): ?string
    {
        return $this->tablesPrefix;
    }

    /**
     * @param string $shortName
     *
     * @return string
     */
    public function createTableName(string $shortName): string
    {
        if ($shortName === '') {
            throw RuntimeException::tableNameEmpty();
        }
        return \sprintf('%s%s', (string)$this->tablesPrefix, $shortName);
    }
}
