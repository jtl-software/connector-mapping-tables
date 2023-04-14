<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use RuntimeException;

class DbManager
{
    protected Connection $connection;

    /**
     * @var AbstractTable[]
     */
    protected array   $tables = [];
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
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @param array<string, mixed> $params
     * @param Configuration|null   $config
     * @param string|null          $tablesPrefix
     *
     * @return self
     * @throws DBALException
     */
    public static function createFromParams(
        array         $params,
        Configuration $config = null,
        string        $tablesPrefix = null
    ): self {
        $params['wrapperClass'] = Connection::class;
        /** @var Connection $connection */
        $connection = DriverManager::getConnection($params, $config);

        return new self($connection, $tablesPrefix);
    }

    /**
     * @param AbstractTable $table
     *
     * @return DbManager
     * @throws DbcRuntimeException
     */
    public function registerTable(AbstractTable $table): DbManager
    {
        $this->tables[$table->getTableName()] = $table;

        return $this;
    }

    /**
     * @return string[]
     * @throws DBALException
     * @throws DbcRuntimeException
     */
    public function getSchema(): array
    {
        return (new Schema($this->getSchemaTables()))->toSql($this->connection->getDatabasePlatform());
    }

    /**
     * @return Table[]
     * @throws DBALException
     * @throws DbcRuntimeException
     */
    public function getSchemaTables(): array
    {
        return \array_map(static function (AbstractTable $table) {
            return $table->getTableSchema();
        }, $this->getTables());
    }

    /**
     * @return AbstractTable[]
     */
    public function getTables(): array
    {
        return \array_values($this->tables);
    }

    /**
     * @return boolean
     * @throws DBALException
     * @throws DbcRuntimeException
     * @throws DbcRuntimeException|\RuntimeException
     */
    public function hasSchemaUpdates(): bool
    {
        return \count($this->getSchemaUpdates()) > 0;
    }

    /**
     * @return string[]
     * @throws DBALException
     * @throws DbcRuntimeException
     * @throws Exception
     * @throws SchemaException
     * @throws DbcRuntimeException|RuntimeException
     */
    public function getSchemaUpdates(): array
    {
        /** @var Configuration $configuration */
        $configuration = $this->connection->getConfiguration();
        /** @var AbstractSchemaManager $schemaManager */
        $schemaManager              = $this->connection->getSchemaManager();
        $originalSchemaAssetsFilter = $configuration->getSchemaAssetsFilter();
        $configuration->setSchemaAssetsFilter($this->createSchemaAssetsFilterCallback());
        $fromSchema       = $schemaManager->createSchema();
        $updateStatements = $fromSchema->getMigrateToSql(
            new Schema($this->getSchemaTables()),
            $this->connection->getDatabasePlatform()
        );
        $configuration->setSchemaAssetsFilter($originalSchemaAssetsFilter);
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
        return \is_string($this->tablesPrefix) && $this->tablesPrefix !== '';
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
     * @throws DbcRuntimeException
     */
    public function createTableName(string $shortName): string
    {
        if ($shortName === '') {
            throw DbcRuntimeException::tableNameEmpty();
        }
        return \sprintf('%s%s', (string)$this->tablesPrefix, $shortName);
    }
}
