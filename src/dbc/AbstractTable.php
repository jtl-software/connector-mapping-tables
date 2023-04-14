<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Jtl\Connector\Dbc\Query\QueryBuilder;
use Jtl\Connector\Dbc\Schema\TableRestriction;

abstract class AbstractTable
{
    /**
     * @var DbManager
     */
    protected DbManager $dbManager;

    /**
     * @var Table
     */
    protected Table $tableSchema;

    /**
     * Table constructor.
     *
     * @param DbManager $dbManager
     *
     * @throws \Exception
     */
    public function __construct(DbManager $dbManager)
    {
        $this->dbManager = $dbManager;
        $dbManager->registerTable($this);
    }

    /**
     * @param mixed[]       $data
     * @param string[]|null $types
     *
     * @return int
     * @throws DBALException
     * @throws DbcRuntimeException
     * @throws DbcRuntimeException
     */
    public function insert(array $data, array $types = null): int
    {
        if (\is_null($types)) {
            $types = $this->getColumnTypesFor(...\array_keys($data));
        }

        return $this->getConnection()->insert($this->getTableName(), $data, $types);
    }

    /**
     * @param string ...$columnNames
     *
     * @return string[]
     * @throws DBALException
     * @throws DbcRuntimeException
     */
    protected function getColumnTypesFor(string ...$columnNames): array
    {
        return \array_filter($this->getColumnTypes(), static function (string $columnName) use ($columnNames) {
            return \in_array($columnName, $columnNames, true);
        }, \ARRAY_FILTER_USE_KEY);
    }

    /**
     * @return array<string, string>
     * @throws DBALException
     * @throws DbcRuntimeException
     * @throws SchemaException
     */
    public function getColumnTypes(): array
    {
        $columnTypes = [];
        foreach ($this->getTableSchema()->getColumns() as $column) {
            $columnTypes[$column->getName()] = $column->getType()->getName();
        }
        return $columnTypes;
    }

    /**
     * @return Table
     * @throws DbcRuntimeException
     * @throws DBALException
     */
    public function getTableSchema(): Table
    {
        if (!isset($this->tableSchema)) {
            $this->tableSchema = $this->createSchemaTable();
            $this->preCreateTableSchema($this->tableSchema);
            $this->createTableSchema($this->tableSchema);
            $this->postCreateTableSchema($this->tableSchema);
            if (\count($this->tableSchema->getColumns()) === 0) {
                throw DbcRuntimeException::tableEmpty($this->tableSchema->getName());
            }
        }

        return $this->tableSchema;
    }

    /**
     * @return Table
     * @throws Exception
     * @throws DbcRuntimeException
     */
    protected function createSchemaTable(): Table
    {
        return new Table($this->getTableName());
    }

    /**
     * @return string
     * @throws DbcRuntimeException
     */
    public function getTableName(): string
    {
        return $this->dbManager->createTableName($this->getName());
    }

    /**
     * @return string
     */
    abstract protected function getName(): string;

    /**
     * @param Table $schemaTable
     */
    protected function preCreateTableSchema(Table $schemaTable): void
    {
    }

    /**
     * @param $tableSchema Table
     *
     * @return void
     */
    abstract protected function createTableSchema(Table $tableSchema): void;

    /**
     * @param Table $schemaTable
     */
    protected function postCreateTableSchema(Table $schemaTable): void
    {
    }

    /**
     * @return Connection
     */
    protected function getConnection(): Connection
    {
        return $this->getDbManager()->getConnection();
    }

    /**
     * @return DbManager
     */
    public function getDbManager(): DbManager
    {
        return $this->dbManager;
    }

    /**
     * @param mixed[]       $data
     * @param mixed[]       $identifiers
     * @param string[]|null $types
     *
     * @return integer
     * @throws DBALException
     * @throws DbcRuntimeException
     * @throws DbcRuntimeException
     */
    public function update(array $data, array $identifiers, ?array $types = null): int
    {
        if (\is_null($types)) {
            $types =
                $this->getColumnTypesFor(...\array_unique(\array_merge(\array_keys($data), \array_keys($identifiers))));
        }

        return $this->getConnection()->update($this->getTableName(), $data, $identifiers, $types);
    }

    /**
     * @param mixed[]       $identifiers
     * @param string[]|null $types
     *
     * @return int
     * @throws DBALException
     * @throws InvalidArgumentException
     * @throws DbcRuntimeException
     * @throws DbcRuntimeException
     */
    public function delete(array $identifiers, array $types = null): int
    {
        if (\is_null($types)) {
            $types = $this->getColumnTypesFor(...\array_keys($identifiers));
        }

        return $this->getConnection()->delete($this->getTableName(), $identifiers, $types);
    }

    /**
     * @return string[]
     * @throws DbcRuntimeException
     * @throws DBALException
     */
    public function getColumnNames(): array
    {
        return \array_keys($this->getColumnTypes());
    }

    /**
     * @param string $doctrineType
     * @param mixed  $phpValue
     *
     * @return mixed
     * @throws Exception
     */
    protected function convertToDatabaseValue(string $doctrineType, $phpValue)
    {
        return Type::getType($doctrineType)->convertToDatabaseValue(
            $phpValue,
            $this->getConnection()->getDatabasePlatform()
        );
    }

    /**
     * @param array<int, array<string>> $rows
     *
     * @return array<int, array<int|string, string>>
     * @throws DBALException
     * @throws DbcRuntimeException
     */
    protected function convertAllToPhpValues(array $rows): array
    {
        $return = [];
        foreach ($rows as $row) {
            $return[] = $this->convertToPhpValues($row);
        }

        return $return;
    }

    /**
     * @param array<string> $row
     *
     * @return array<int|string, string>
     * @throws DbcRuntimeException
     * @throws DBALException
     */
    protected function convertToPhpValues(array $row): array
    {
        $types          = $this->getColumnTypes();
        $numericIndices = \is_int(\key($row));

        if ($numericIndices && \count($row) < \count($types)) {
            throw DbcRuntimeException::numericIndicesMissing();
        }

        if ($numericIndices) {
            $types = \array_values($types);
        }

        $result = [];
        foreach ($row as $index => $value) {
            $result[$index] = $value;
            if (isset($types[$index]) && $types[$index] !== Types::BINARY && Type::hasType($types[$index])) {
                $result[$index] = Type::getType($types[$index])->convertToPHPValue(
                    $value,
                    $this->getConnection()->getDatabasePlatform()
                );

                //Dirty BIGINT to int cast
                if ($result[$index] !== null && $types[$index] === Types::BIGINT) {
                    $result[$index] = (int)$result[$index];
                }
            }
        }

        return $result;
    }

    /**
     * @param string|null $tableAlias
     *
     * @return QueryBuilder
     * @throws DbcRuntimeException
     */
    protected function createQueryBuilder(string $tableAlias = null): QueryBuilder
    {
        return new QueryBuilder(
            $this->getConnection(),
            $this->getConnection()->getTableRestrictions(),
            $this->getTableName(),
            $tableAlias
        );
    }

    /**
     * @param string $column
     * @param mixed  $value
     *
     * @return AbstractTable
     * @throws DbcRuntimeException
     * @throws DBALException
     * @throws SchemaException
     */
    protected function restrict(string $column, $value): AbstractTable
    {
        $this->getConnection()->restrictTable(new TableRestriction($this->getTableSchema(), $column, $value));
        return $this;
    }
}
