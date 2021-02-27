<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */

namespace Jtl\Connector\MappingTables;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Jtl\Connector\Dbc\AbstractTable as AbstractDbcTable;
use Jtl\Connector\Dbc\DbManager;
use Jtl\Connector\Dbc\Query\QueryBuilder;
use Jtl\Connector\Dbc\TableException;
use Jtl\Connector\MappingTables\Schema\EndpointColumn;

abstract class AbstractTable extends AbstractDbcTable implements TableInterface
{
    public const
        ENDPOINT_INDEX_NAME = 'endpoint_idx',
        HOST_INDEX_NAME = 'host_idx',
        HOST_ID = 'host_id',
        IDENTITY_TYPE = 'identity_type';

    /**
     * @var string
     */
    protected $endpointDelimiter = '||';

    /**
     * @var boolean
     */
    protected $singleIdentity = true;

    /**
     * @var EndpointColumn[]
     */
    private $endpointColumns = [];

    /**
     * AbstractTable constructor.
     * @param DbManager $dbManager
     * @param bool $isSingleIdentity
     * @throws \Exception
     */
    public function __construct(DbManager $dbManager, bool $isSingleIdentity = true)
    {
        if (count($this->getTypes()) === 0) {
            throw RuntimeException::typesEmpty();
        }

        foreach ($this->getTypes() as $type) {
            if (!is_int($type)) {
                throw RuntimeException::wrongTypes();
            }
        }

        $this->singleIdentity = $isSingleIdentity;
        parent::__construct($dbManager);
        $this->defineEndpoint();

        if (!$this->singleIdentity) {
            $this->addEndpointColumn(new Column(self::IDENTITY_TYPE, Type::getType(Types::INTEGER)));
        }
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
     * @return Table
     * @throws Exception
     */
    protected function createSchemaTable(): Table
    {
        return new Table($this->getTableName(), $this->getEndpointColumns());
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
        $endpointColumnNames = $this->getEndpointColumnNames();
        $primaryColumnNames = $this->getEndpointColumnNames(true);
        if (count($endpointColumnNames) === 0) {
            throw RuntimeException::endpointColumnsNotDefined();
        }

        $tableSchema->addColumn(self::HOST_ID, Types::INTEGER)
            ->setNotnull(false);
        
        $tableSchema->addIndex([self::HOST_ID], $this->createIndexName(self::HOST_INDEX_NAME));

        $tableSchema->setPrimaryKey($primaryColumnNames);
        if (count($primaryColumnNames) < count($endpointColumnNames)) {
            $tableSchema->addIndex($endpointColumnNames, $this->createIndexName(self::ENDPOINT_INDEX_NAME));
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

        $primaryColumnNames = $this->getEndpointColumnNames(true);

        foreach ($this->extractEndpoint($endpoint) as $column => $value) {
            if (in_array($column, $primaryColumnNames, true)) {
                $qb->andWhere($column . ' = :' . $column)
                    ->setParameter($column, $value, $this->endpointColumns[$column]->getColumn()->getType()->getName());
            }
        }

        $hostId = $qb->execute()->fetchColumn(0);
        if ($hostId !== false) {
            return (int)$hostId;
        }

        return null;
    }

    /**
     * @param int $hostId
     * @param int|null $type
     * @return string|null
     * @throws Exception
     */
    public function getEndpoint(int $hostId, int $type = null): ?string
    {
        $endpointData = $this->createEndpointIdQuery($hostId, $type)
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
            $primaryColumnNames = $this->getEndpointColumnNames(true);

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
     * @param string|null $endpoint
     * @param integer|null $hostId
     * @param integer|null $type
     * @return integer
     * @throws DBALException
     * @throws RuntimeException
     */
    public function remove(string $endpoint = null, int $hostId = null, int $type = null): int
    {
        $qb = $this->createQueryBuilder()
            ->delete($this->getTableName());

        $primaryColumnNames = $this->getEndpointColumnNames(true);

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

            if (!$this->singleIdentity) {
                $qb->andWhere(sprintf('%s = :%s', self::IDENTITY_TYPE, self::IDENTITY_TYPE))
                    ->setParameter(self::IDENTITY_TYPE, $type);
            }
        }

        if ($hostId !== null) {
            $qb->andWhere(self::HOST_ID . ' = :' . self::HOST_ID)
                ->setParameter(self::HOST_ID, $hostId);
        }

        return $qb->execute();
    }

    /**
     * @param integer|null $type
     * @return integer
     * @throws RuntimeException|Exception
     */
    public function clear(int $type = null): int
    {
        $qb = $this->createQueryBuilder()
            ->delete($this->getTableName());

        if (!is_null($type)) {
            if (!$this->isResponsible($type)) {
                throw RuntimeException::unknownType($type);
            }

            if (!$this->singleIdentity) {
                $qb->andWhere(sprintf('%s = :%s', self::IDENTITY_TYPE, self::IDENTITY_TYPE))
                    ->setParameter(self::IDENTITY_TYPE, $type);
            }
        }

        return $qb->execute();
    }

    /**
     * @param string[] $where
     * @param mixed[] $parameters
     * @param string[] $orderBy
     * @param integer|null $limit
     * @param integer|null $offset
     * @param integer|null $type
     * @return integer
     * @throws DBALException
     */
    public function count(array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null, int $type = null): int
    {
        $result = $this->createFindQuery($where, $parameters, $orderBy, $limit, $offset, $type)
            ->select($this->getDbManager()->getConnection()->getDatabasePlatform()->getCountExpression('*'))
            ->execute()
            ->fetchColumn(0);

        return $result;
    }

    /**
     * @param string[] $where
     * @param mixed[] $parameters
     * @param string[] $orderBy
     * @param integer|null $limit
     * @param integer|null $offset
     * @param integer|null $type
     * @return string[]
     * @throws DBALException
     * @throws RuntimeException
     */
    public function findEndpoints(array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null, int $type = null): array
    {
        $stmt = $this->createFindQuery($where, $parameters, $orderBy, $limit, $offset, $type)
            ->select($this->getEndpointColumnNames())
            ->execute();

        return array_map(function (array $data) {
            return $this->buildEndpoint($data);
        }, $stmt->fetchAll());
    }

    /**
     * @param array $where
     * @param array $parameters
     * @param array $orderBy
     * @param integer|null $limit
     * @param integer|null $offset
     * @param integer|null $type
     * @return QueryBuilder
     * @throws DBALException
     * @throws RuntimeException
     */
    public function createFindQuery(array $where = [], array $parameters = [], array $orderBy = [], int $limit = null, int $offset = null, int $type = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder();

        if (!is_null($type)) {
            if (!$this->isResponsible($type)) {
                throw RuntimeException::unknownType($type);
            }

            if (!$this->singleIdentity) {
                $qb->andWhere(sprintf('%s = :%s', self::IDENTITY_TYPE, self::IDENTITY_TYPE))
                    ->setParameter(self::IDENTITY_TYPE, $type);
            }
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
        $primaryColumnNames = $this->getEndpointColumnNames(true);

        $concatArray = [];
        foreach ($primaryColumnNames as $i => $column) {
            $concatArray[] = $column;
            if (isset($primaryColumnNames[$i + 1])) {
                $concatArray[] = $this->getConnection()->quote($this->endpointDelimiter);
            }
        }

        $preparedEndpoints = [];
        foreach ($endpoints as $endpoint) {
            $extracted = array_filter($this->extractEndpoint($endpoint), function ($key) use ($primaryColumnNames) {
                return in_array($key, $primaryColumnNames);
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
        if (!$this->singleIdentity) {
            $type = $data[self::IDENTITY_TYPE];
            if (!$this->isResponsible($type)) {
                throw RuntimeException::unknownType($type);
            }
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
     * @param integer|null $type
     * @return boolean
     */
    public function isResponsible(?int $type): bool
    {
        return $this->singleIdentity || in_array($type, $this->getTypes(), true);
    }

    /**
     * @return bool
     */
    public function isSingleIdentity(): bool
    {
        return $this->singleIdentity;
    }

    /**
     * @param integer $hostId
     * @param integer|null $type
     * @return QueryBuilder
     * @throws RuntimeException
     */
    protected function createEndpointIdQuery(int $hostId, int $type = null): QueryBuilder
    {
        if (!$this->isResponsible($type)) {
            throw RuntimeException::unknownType($type);
        }

        $columnNames = $this->getEndpointColumnNames();

        $qb = $this->createQueryBuilder()
            ->select($columnNames)
            ->from($this->getTableName())
            ->andWhere(sprintf('%s = :hostId', self::HOST_ID))
            ->setParameter('hostId', $hostId);

        if (!$this->singleIdentity) {
            $qb->andWhere(sprintf('%s = :identityType', self::IDENTITY_TYPE))
                ->setParameter('identityType', $type);
        }

        return $qb;
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
        $columnNames = $this->getEndpointColumnNames();
        $columnsCount = count($columnNames);

        if ($dataCount !== $columnsCount) {
            throw RuntimeException::wrongEndpointPartsAmount($dataCount, $columnsCount);
        }

        return $this->convertToPhpValues(array_combine($columnNames, $data));
    }

    /**
     * @param Column $column
     * @param bool $primary
     * @return AbstractTable
     */
    protected function addEndpointColumn(Column $column, bool $primary = true): self
    {
        if ($this->hasEndpointColumn($column->getName())) {
            throw RuntimeException::columnExists($column->getName());
        }

        $this->endpointColumns[$column->getName()] = EndpointColumn::create($column, $primary);

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
     * @param boolean $onlyPrimaryColumns
     * @return array<Column>
     */
    protected function getEndpointColumns(bool $onlyPrimaryColumns = false): array
    {
        $endpointColumns = array_filter($this->endpointColumns, function (EndpointColumn $endpointColumn) use ($onlyPrimaryColumns) {
            return in_array($endpointColumn->isPrimary(), [true, $onlyPrimaryColumns], true);
        });

        return array_values(array_map(function(EndpointColumn $endpointColumn) {
            return $endpointColumn->getColumn();
        }, $endpointColumns));
    }

    /**
     * @param boolean $onlyPrimaryColumns
     * @return array<string>
     */
    protected function getEndpointColumnNames(bool $onlyPrimaryColumns = false): array
    {
        return array_map(function(Column $column) {
            return $column->getName();
        }, $this->getEndpointColumns($onlyPrimaryColumns));
    }
}
