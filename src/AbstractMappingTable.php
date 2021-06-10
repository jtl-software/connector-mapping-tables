<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */

namespace Jtl\Connector\MappingTables;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Jtl\Connector\Dbc\AbstractTable;
use Jtl\Connector\Dbc\DbManager;
use Jtl\Connector\Dbc\Query\QueryBuilder;
use Jtl\Connector\Dbc\TableException;

abstract class AbstractMappingTable extends AbstractTable implements MappingTableInterface
{
    const ENDPOINT_INDEX_NAME = 'endpoint_idx';
    const HOST_INDEX_NAME = 'host_idx';
    const HOST_ID = 'host_id';

    /**
     * @var string
     */
    protected $endpointDelimiter = '||';

    /**
     * @var string[]
     */
    protected $columns = [];

    /**
     * AbstractMappingTable constructor.
     * @param DbManager $dbManager
     * @throws \Exception
     */
    public function __construct(DbManager $dbManager)
    {
        parent::__construct($dbManager);
        $this->defineEndpoint();
    }

    /**
     * @param string $name
     * @return string
     */
    public function createIndexName(string $name): string
    {
        return $this->getTableName() . '_' . $name;
    }

    /**
     * @return void
     */
    abstract protected function defineEndpoint(): void;

    /**
     * @param Table $tableSchema
     * @return void
     * @throws RuntimeException
     */
    protected function createTableSchema(Table $tableSchema): void
    {
        $endpointColumns = $this->getEndpointColumns();
        $primaryColumns = $this->getPrimaryColumns();
        if (count($endpointColumns) === 0) {
            throw RuntimeException::endpointColumnsNotDefined();
        }

        foreach ($endpointColumns as $columnName => $columnData) {
            $tableSchema->addColumn($columnName, $columnData['type'], $columnData['options']);
        }

        $tableSchema->addColumn(self::HOST_ID, Type::INTEGER, ['notnull' => false]);
        $tableSchema->addIndex([self::HOST_ID], $this->createIndexName(self::HOST_INDEX_NAME));

        $tableSchema->setPrimaryKey(array_keys($primaryColumns));
        if (count($primaryColumns) < count($endpointColumns)) {
            $tableSchema->addIndex(array_keys($endpointColumns), $this->createIndexName(self::ENDPOINT_INDEX_NAME));
        }
    }

    /**
     * @param string $endpoint
     * @return int|null
     * @throws DBALException
     */
    public function getHostId(string $endpoint): ?int
    {
        $qb = $this->createQueryBuilder()
            ->select(self::HOST_ID)
            ->from($this->getTableName());

        $primaryColumns = array_keys($this->getPrimaryColumns());

        foreach ($this->extractEndpoint($endpoint) as $column => $value) {
            if (in_array($column, $primaryColumns)) {
                $qb->andWhere($column . ' = :' . $column)
                    ->setParameter($column, $value);
            }
        }

        $hostId = $qb->execute()->fetchColumn(0);
        if ($hostId !== false) {
            return (int)$hostId;
        }

        return null;
    }

    /**
     * @param integer $hostId
     * @param string|null $relationType
     * @return null|string
     */
    public function getEndpoint(int $hostId, string $relationType = null): ?string
    {
        $endpointData = $this->createEndpointIdQuery($hostId)
            ->execute()
            ->fetch();

        if (is_array($endpointData)) {
            return $this->buildEndpoint($endpointData);
        }
        return null;
    }

    /**
     * @param string $endpoint
     * @param int $hostId
     * @return boolean
     * @throws DBALException
     */
    public function save(string $endpoint, int $hostId): bool
    {
        $data = $this->extractEndpoint($endpoint);
        $data[self::HOST_ID] = $hostId;

        try {
            $this->insert($data);

            return true;
        } catch (UniqueConstraintViolationException $ex) {
            $primaryColumns = $this->getPrimaryColumns();
            $primaryColumnNames = array_keys($primaryColumns);

            $identifier = [];
            foreach ($data as $column => $value) {
                if (in_array($column, $primaryColumnNames, true)) {
                    $identifier[$column] = $value;
                }
            }

            $this->update($data, $identifier);

            return true;
        }

        return false;
    }

    /**
     * @param string|null $endpoint
     * @param integer|null $hostId
     * @return bool
     * @throws DBALException
     */
    public function remove(string $endpoint = null, int $hostId = null): bool
    {
        $qb = $this->createQueryBuilder();
        $qb->delete($this->getTableName());

        $primaryColumns = array_keys($this->getPrimaryColumns());

        if ($endpoint !== null) {
            foreach ($this->extractEndpoint($endpoint) as $column => $value) {
                if (in_array($column, $primaryColumns)) {
                    $qb->andWhere($column . ' = :' . $column)
                        ->setParameter($column, $value);
                }
            }
        }

        if ($hostId !== null) {
            $qb->andWhere(self::HOST_ID . ' = :' . self::HOST_ID)
                ->setParameter(self::HOST_ID, $hostId);
        }

        $qb->execute();

        return true;
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        $this->createQueryBuilder()
            ->delete($this->getTableName())
            ->execute();

        return true;
    }

    /**
     * @param string[] $where
     * @param mixed[] $parameters
     * @param string[] $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return integer
     * @throws DBALException
     */
    public function count(array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null): int
    {
        return $this->createFindQuery($where, $parameters, $orderBy, $limit, $offset)
            ->select($this->getDbManager()->getConnection()->getDatabasePlatform()->getCountExpression('*'))
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * @param string[] $where
     * @param mixed[] $parameters
     * @param string[] $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return string[]
     * @throws DBALException
     */
    public function findEndpoints(array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null): array
    {
        $stmt = $this->createFindQuery($where, $parameters, $orderBy, $limit, $offset)
            ->select(array_keys($this->getEndpointColumns()))
            ->execute();

        return array_map(function (array $data) {
            return $this->buildEndpoint($data);
        }, $stmt->fetchAll());
    }

    /**
     * @param array $where
     * @param array $parameters
     * @param array $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return QueryBuilder
     * @throws DBALException
     */
    public function createFindQuery(array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder()
            ->from($this->getTableName());

        foreach ($where as $condition) {
            $qb->andWhere($condition);
        }

        foreach ($parameters as $param => $value) {
            $qb->setParameter($param, $value);
        }

        if (is_int($limit)) {
            $qb->setMaxResults($limit);
        }

        if (is_int($offset)) {
            $qb->setFirstResult($offset);
        }

        $allColumns = $this->getColumnNames();
        foreach ($orderBy as $column => $direction) {
            if (!in_array($column, $allColumns)) {
                throw RuntimeException::columnNotFound($column);
            }
            $qb->addOrderBy($column, $direction);
        }

        return $qb;
    }

    /**
     * @param array $endpoints
     * @return array|string[]
     * @throws DBALException
     */
    public function findNotFetchedEndpoints(array $endpoints): array
    {
        $platform = $this->getConnection()->getDatabasePlatform();
        $primaryColumns = array_keys($this->getPrimaryColumns());

        $concatArray = [];
        foreach ($primaryColumns as $column) {
            $concatArray[] = $column;
            if ($column !== end($primaryColumns)) {
                $concatArray[] = $this->getConnection()->quote($this->endpointDelimiter);
            }
        }

        $preparedEndpoints = [];
        foreach ($endpoints as $endpoint) {
            $extracted = array_filter($this->extractEndpoint($endpoint), function ($key) use ($primaryColumns) {
                return in_array($key, $primaryColumns);
            }, \ARRAY_FILTER_USE_KEY);

            $preparedEndpoints[implode($this->endpointDelimiter, $extracted)] = $endpoint;
        }

        $concatExpression = call_user_func_array([$platform, 'getConcatExpression'], $concatArray);
        $qb = $this->createQueryBuilder()
            ->select($concatExpression)
            ->from($this->getTableName())
            ->where($this->getConnection()->getExpressionBuilder()->in($concatExpression, ':preparedEndpoints'))
            ->setParameter('preparedEndpoints', array_keys($preparedEndpoints), Connection::PARAM_STR_ARRAY);

        $fetchedEndpoints = $qb->execute()->fetchAll(\PDO::FETCH_COLUMN);
        if (is_array($fetchedEndpoints) && !empty($fetchedEndpoints)) {
            foreach ($preparedEndpoints as $prepared => $endpoint) {
                if (in_array($prepared, $fetchedEndpoints)) {
                    unset($preparedEndpoints[$prepared]);
                }
            }
            return array_values($preparedEndpoints);
        }
        return $endpoints;
    }

    /**
     * @return string
     */
    public function getEndpointDelimiter(): string
    {
        return $this->endpointDelimiter;
    }

    /**
     * @param string $endpointDelimiter
     * @return AbstractMappingTable
     */
    public function setEndpointDelimiter(string $endpointDelimiter): AbstractMappingTable
    {
        $this->endpointDelimiter = $endpointDelimiter;
        return $this;
    }

    /**
     * @param mixed[] $data
     * @return string
     */
    public function buildEndpoint(array $data): string
    {
        return $this->implodeEndpoint($data);
    }

    /**
     * @param string $endpointId
     * @return mixed[]
     * @throws DBALException
     */
    public function extractEndpoint(string $endpointId): array
    {
        $data = $this->explodeEndpoint($endpointId);
        return $this->createEndpointData($data);
    }

    /**
     * @param int $hostId
     * @return QueryBuilder
     */
    protected function createEndpointIdQuery(int $hostId): QueryBuilder
    {
        $columns = array_keys($this->getEndpointColumns());
        return $this->createQueryBuilder()
            ->select($columns)
            ->from($this->getTableName())
            ->where(self::HOST_ID . ' = :' . self::HOST_ID)
            ->setParameter(self::HOST_ID, $hostId);
    }

    /**
     * @param string $endpointId
     * @return mixed[]
     */
    protected function explodeEndpoint(string $endpointId): array
    {
        return explode($this->endpointDelimiter, $endpointId);
    }

    /**
     * @param mixed[] $data
     * @return string
     */
    protected function implodeEndpoint(array $data): string
    {
        return implode($this->endpointDelimiter, $data);
    }

    /**
     * @param mixed[] $data
     * @return mixed[]
     * @throws DBALException
     */
    protected function createEndpointData(array $data): array
    {
        $columns = $this->getEndpointColumns();
        $dataCount = count($data);
        $columnNames = array_keys($columns);
        if ($dataCount < count($columns)) {
            throw RuntimeException::columnDataMissing($columnNames[$dataCount]);
        }
        return $this->convertToPhpValues(array_combine($columnNames, $data));
    }

    /**
     * @param string $name
     * @param string $type
     * @param mixed $options
     * @param boolean $primary
     * @return AbstractMappingTable
     * @throws RuntimeException
     */
    protected function addEndpointColumn(string $name, string $type, array $options = [], bool $primary = true): AbstractMappingTable
    {
        if ($this->hasEndpointColumn($name)) {
            throw RuntimeException::columnExists($name);
        }
        $this->columns[$name]['type'] = $type;
        $this->columns[$name]['options'] = $options;
        $this->columns[$name]['primary'] = $primary;
        return $this;
    }

    /**
     * @param string $name
     * @return boolean
     */
    protected function hasEndpointColumn(string $name): bool
    {
        return isset($this->columns[$name]);
    }

    /**
     * @return string[]
     */
    protected function getEndpointColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return string[]
     */
    protected function getPrimaryColumns(): array
    {
        return array_filter($this->columns, function (array $column) {
            return isset($column['primary']) && $column['primary'] === true;
        });
    }
}
