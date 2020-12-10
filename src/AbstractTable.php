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
use Doctrine\DBAL\Types\Types;
use Jtl\Connector\Dbc\AbstractTable as AbstractDbcTable;
use Jtl\Connector\Dbc\DbManager;
use Jtl\Connector\Dbc\Query\QueryBuilder;
use Jtl\Connector\Dbc\TableException;
use Jtl\Connector\MappingTables\Schema\EndpointColumn;

abstract class AbstractTable extends AbstractDbcTable implements TableInterface
{
    const ENDPOINT_INDEX_NAME = 'endpoint_idx';
    const HOST_INDEX_NAME = 'host_idx';
    const HOST_ID = 'host_id';
    const IDENTITY_TYPE = 'identity_type';

    /**
     * @var string
     */
    protected $endpointDelimiter = '||';

    /**
     * @var EndpointColumn[]
     */
    protected $endpointColumns = [];

    /**
     * AbstractMappingTable constructor.
     * @param DbManager $dbManager
     * @throws \Exception
     */
    public function __construct(DbManager $dbManager)
    {
        if (count($this->getTypes()) === 0) {
            throw RuntimeException::typesEmpty();
        }

        foreach ($this->getTypes() as $type) {
            if (!is_int($type)) {
                throw RuntimeException::wrongTypes();
            }
        }

        parent::__construct($dbManager);
        $this->defineEndpoint();
        $this->addEndpointColumn(self::IDENTITY_TYPE, Types::INTEGER);
    }

    /**
     * @param string $name
     * @return string
     */
    public function createIndexName(string $name): string
    {
        return sprintf('%s_%s', $this->getTableName(), $name);
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

        foreach ($endpointColumns as $columnName => $endpointColumn) {
            $tableSchema->addColumn($endpointColumn->getName(), $endpointColumn->getType(), $endpointColumn->getOptions());
        }

        $tableSchema->addColumn(self::HOST_ID, Types::INTEGER, ['notnull' => false]);
        $tableSchema->addIndex([self::HOST_ID], $this->createIndexName(self::HOST_INDEX_NAME));

        $tableSchema->setPrimaryKey(array_keys($primaryColumns));
        if (count($primaryColumns) < count($endpointColumns)) {
            $tableSchema->addIndex(array_keys($endpointColumns), $this->createIndexName(self::ENDPOINT_INDEX_NAME));
        }
    }

    /**
     * @param string $endpoint
     * @return integer|null
     * @throws DBALException
     * @throws RuntimeException
     */
    public function getHostId(string $endpoint): ?int
    {
        $qb = $this->createQueryBuilder()
            ->select(self::HOST_ID)
            ->from($this->getTableName());

        $primaryColumnNames = array_keys($this->getPrimaryColumns());

        foreach ($this->extractEndpoint($endpoint) as $column => $value) {
            if (in_array($column, $primaryColumnNames, true)) {
                $qb->andWhere($column . ' = :' . $column)
                    ->setParameter($column, $value, $this->endpointColumns[$column]->getType());
            }
        }

        $hostId = $qb->execute()->fetchColumn(0);
        if ($hostId !== false) {
            return (int)$hostId;
        }

        return null;
    }

    /**
     * @param integer $type
     * @param integer $hostId
     * @return null|string
     */
    public function getEndpoint(int $type, int $hostId): ?string
    {
        $endpointData = $this->createEndpointIdQuery($type, $hostId)
            ->execute()
            ->fetch();

        if (is_array($endpointData)) {
            return $this->buildEndpoint($endpointData);
        }

        return null;
    }

    /**
     * @param string $endpoint
     * @param integer $hostId
     * @return integer
     * @throws DBALException
     * @throws RuntimeException
     */
    public function save(string $endpoint, int $hostId): int
    {
        $data = $this->extractEndpoint($endpoint);
        $data[self::HOST_ID] = $hostId;

        try {
            return $this->insert($data);
        } catch (UniqueConstraintViolationException $ex) {
            $primaryColumnNames = array_keys($this->getPrimaryColumns());

            $identifier = [];
            foreach ($data as $column => $value) {
                if (in_array($column, $primaryColumnNames, true)) {
                    $identifier[$column] = $value;
                }
            }

            return $this->update($data, $identifier);
        }
    }

    /**
     * @param integer $type
     * @param string|null $endpoint
     * @param integer|null $hostId
     * @return integer
     * @throws DBALException
     * @throws RuntimeException
     */
    public function remove(int $type, string $endpoint = null, int $hostId = null): int
    {
        $qb = $this->createQueryBuilder()
            ->delete($this->getTableName());

        $primaryColumnNames = array_keys($this->getPrimaryColumns());

        if ($endpoint !== null) {
            foreach ($this->extractEndpoint($endpoint) as $column => $value) {
                if (in_array($column, $primaryColumnNames)) {
                    $qb->andWhere($column . ' = :' . $column)
                        ->setParameter($column, $value);
                }
            }
        } else {
            if (!$this->isResponsible($type)) {
                throw RuntimeException::unknownType($type);
            }

            $qb->andWhere(sprintf('%s = :%s', self::IDENTITY_TYPE, self::IDENTITY_TYPE))
                ->setParameter(self::IDENTITY_TYPE, $type);
        }

        if ($hostId !== null) {
            $qb->andWhere(self::HOST_ID . ' = :' . self::HOST_ID)
                ->setParameter(self::HOST_ID, $hostId);
        }

        return $qb->execute();
    }

    /**
     * @param integer $type
     * @return integer
     * @throws RuntimeException
     */
    public function clear(int $type = null): int
    {
        $qb = $this->createQueryBuilder()
            ->delete($this->getTableName());

        if (!is_null($type)) {
            if (!$this->isResponsible($type)) {
                throw RuntimeException::unknownType($type);
            }

            $qb->andWhere(sprintf('%s = :%s', self::IDENTITY_TYPE, self::IDENTITY_TYPE))
                ->setParameter(self::IDENTITY_TYPE, $type);
        }

        return $qb->execute();
    }

    /**
     * @param integer $type
     * @param string[] $where
     * @param mixed[] $parameters
     * @param string[] $orderBy
     * @param integer|null $limit
     * @param integer|null $offset
     * @return integer
     * @throws DBALException
     */
    public function count(int $type = null, array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null): int
    {
        $result = $this->createFindQuery($type, $where, $parameters, $orderBy, $limit, $offset)
            ->select($this->getDbManager()->getConnection()->getDatabasePlatform()->getCountExpression('*'))
            ->execute()
            ->fetchColumn(0);

        return $result;
    }

    /**
     * @param integer|null $type
     * @param string[] $where
     * @param mixed[] $parameters
     * @param string[] $orderBy
     * @param integer|null $limit
     * @param integer|null $offset
     * @return string[]
     * @throws DBALException
     * @throws RuntimeException
     */
    public function findEndpoints(int $type = null, array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null): array
    {
        $stmt = $this->createFindQuery($type, $where, $parameters, $orderBy, $limit, $offset)
            ->select(array_keys($this->getEndpointColumns()))
            ->execute();

        return array_map(function (array $data) {
            return $this->buildEndpoint($data);
        }, $stmt->fetchAll());
    }

    /**
     * @param integer|null $type
     * @param array $where
     * @param array $parameters
     * @param array $orderBy
     * @param integer|null $limit
     * @param integer|null $offset
     * @return QueryBuilder
     * @throws DBALException
     * @throws RuntimeException
     */
    public function createFindQuery(int $type = null, array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder();

        if (!is_null($type)) {
            if (!$this->isResponsible($type)) {
                throw RuntimeException::unknownType($type);
            }

            $qb->andWhere(sprintf('%s = :%s', self::IDENTITY_TYPE, self::IDENTITY_TYPE))
                ->setParameter(self::IDENTITY_TYPE, $type);
        }

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
    public function filterMappedEndpoints(array $endpoints): array
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
     * @return AbstractTable
     */
    public function setEndpointDelimiter(string $endpointDelimiter): AbstractTable
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
        $data = $this->createEndpointData($this->explodeEndpoint($endpointId));
        $type = $data[self::IDENTITY_TYPE];
        if (!$this->isResponsible($type)) {
            throw RuntimeException::unknownType($type);
        }
        return $data;
    }

    /**
     * @param string $field
     * @param string $endpoint
     * @return mixed|null
     * @throws DBALException
     */
    public function extractValueFromEndpoint(string $field, string $endpoint)
    {
        if (empty($endpoint)) {
            return null;
        }
        $extracted = $this->extractEndpoint($endpoint);
        return $extracted[$field] ?? null;
    }

    /**
     * @param integer|null $type
     * @return TableProxy
     */
    public function createProxy(int $type = null): TableProxy
    {
        if (is_null($type)) {
            $types = $this->getTypes();
            $type = reset($types);
        }

        return new TableProxy($type, $this);
    }

    /**
     * @param integer $type
     * @return boolean
     */
    public function isResponsible(int $type): bool
    {
        return in_array($type, $this->getTypes());
    }

    /**
     * @param integer $type
     * @param integer $hostId
     * @return QueryBuilder
     * @throws RuntimeException
     */
    protected function createEndpointIdQuery(int $type, int $hostId): QueryBuilder
    {
        if (!$this->isResponsible($type)) {
            throw RuntimeException::unknownType($type);
        }

        $columns = array_keys($this->getEndpointColumns());
        return $this->createQueryBuilder()
            ->select($columns)
            ->from($this->getTableName())
            ->andWhere(sprintf('%s = :hostId', self::HOST_ID))
            ->setParameter('hostId', $hostId)
            ->andWhere(sprintf('%s = :identityType', self::IDENTITY_TYPE))
            ->setParameter('identityType', $type);
    }

    /**
     * @param string $endpointId
     * @return array
     */
    protected function explodeEndpoint(string $endpointId): array
    {
        if (empty($endpointId)) {
            throw RuntimeException::emptyEndpointId();
        }
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
        $dataCount = count($data);
        $columns = $this->getEndpointColumns();
        $columnsCount = count($columns);
        $columnNames = array_keys($columns);

        if ($dataCount !== $columnsCount) {
            throw RuntimeException::wrongEndpointPartsAmount($dataCount, $columnsCount);
        }

        return $this->convertToPhpValues(array_combine($columnNames, $data));
    }

    /**
     * @param string $name
     * @param string $type
     * @param mixed $options
     * @param boolean $primary
     * @return AbstractTable
     * @throws RuntimeException
     */
    protected function addEndpointColumn(string $name, string $type, array $options = [], bool $primary = true): AbstractTable
    {
        if ($this->hasEndpointColumn($name)) {
            throw RuntimeException::columnExists($name);
        }

        $this->endpointColumns[$name] = EndpointColumn::create($name, $type, $options, $primary);

        return $this;
    }

    /**
     * @param string $name
     * @return boolean
     */
    protected function hasEndpointColumn(string $name): bool
    {
        return isset($this->endpointColumns[$name]);
    }

    /**
     * @return array<EndpointColumn>
     */
    protected function getEndpointColumns(): array
    {
        return $this->endpointColumns;
    }

    /**
     * @return array<EndpointColumn>
     */
    protected function getPrimaryColumns(): array
    {
        return array_filter($this->endpointColumns, function (EndpointColumn $column) {
            return $column->isPrimary();
        });
    }
}
