<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */
namespace jtl\Connector\MappingTables;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use jtl\Connector\CDBC\AbstractTable;
use jtl\Connector\CDBC\DBManager;
use jtl\Connector\CDBC\TableException;

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
     * @param DBManager $dbManager
     * @throws \Exception
     */
    public function __construct(DBManager $dbManager)
    {
        parent::__construct($dbManager);
        $this->defineEndpoint();
    }

    /**
     * @return Table
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getTableSchema()
    {
        $tableSchema = parent::getTableSchema();
        $tableSchema->addColumn(self::HOST_ID, Type::INTEGER, ['notnull' => false]);
        $tableSchema->addIndex([self::HOST_ID], $this->createIndexName(self::HOST_INDEX_NAME));
        return $tableSchema;
    }

    /**
     * @param string $name
     * @return string
     */
    public function createIndexName($name)
    {
        return $this->getTableName() . '_' . $name;
    }

    /**
     * @return void
     */
    abstract protected function defineEndpoint();

    /**
     * @param Table $tableSchema
     * @throws RuntimeException
     * @return void
     */
    protected function createTableSchema(Table $tableSchema)
    {
        $endpointColumns = $this->getEndpointColumns();
        $primaryColumns = $this->getPrimaryColumns();
        if(count($endpointColumns) === 0){
            throw RuntimeException::endpointColumnsNotDefined();
        }

        foreach($endpointColumns as $columnName => $columnData){
            $tableSchema->addColumn($columnName, $columnData['type'], $columnData['options']);
        }

        $tableSchema->setPrimaryKey(array_keys($primaryColumns));
        if(count($primaryColumns) < count($endpointColumns)) {
            $tableSchema->addIndex(array_keys($endpointColumns), $this->createIndexName(self::ENDPOINT_INDEX_NAME));
        }
    }

    /**
     * @param string $endpoint
     * @return int|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getHostId($endpoint)
    {
        $qb = $this->createQueryBuilder()
            ->select(self::HOST_ID)
            ->from($this->getTableName());

        $primaryColumns = array_keys($this->getPrimaryColumns());

        foreach($this->extractEndpoint($endpoint) as $column => $value) {
            if(in_array($column, $primaryColumns)) {
                $qb->andWhere($column . ' = :' . $column)
                    ->setParameter($column, $value);
            }
        }

        $hostId = $qb->execute()->fetchColumn(0);
        if($hostId !== false){
            return (int)$hostId;
        }

        return null;
    }

    /**
     * @param integer $hostId
     * @param string|null $relationType
     * @return null|string
     */
    public function getEndpoint($hostId, $relationType = null)
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
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function save($endpoint, $hostId)
    {
        $data = $this->extractEndpoint($endpoint);
        $data[self::HOST_ID] = $hostId;

        $types = array_map(function(array $column) {
            return $column['type'];
        }, $this->getEndpointColumns());

        return $this->getConnection()->insert($this->getTableName(), $data, $types);
    }

    /**
     * @param null $endpoint
     * @param null $hostId
     * @return \Doctrine\DBAL\Driver\Statement|int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function remove($endpoint = null, $hostId = null)
    {
        $qb = $this->createQueryBuilder();
        $qb->delete($this->getTableName());

        $primaryColumns = array_keys($this->getPrimaryColumns());

        if($endpoint !== null){
            foreach($this->extractEndpoint($endpoint) as $column => $value){
                if(in_array($column, $primaryColumns)) {
                    $qb->andWhere($column . ' = :' . $column)
                        ->setParameter($column, $value);
                }
            }
        }

        if($hostId !== null) {
            $qb->andWhere(self::HOST_ID . ' = :' . self::HOST_ID)
               ->setParameter(self::HOST_ID, $hostId);
        }

        return $qb->execute();
    }

    /**
     * @return boolean
     */
    public function clear()
    {
       $rows = $this->createQueryBuilder()
           ->delete($this->getTableName())
           ->execute();

       return is_int($rows) ? $rows : 0;
    }

    /**
     * @param array $where
     * @param array $parameters
     * @return integer
     * @throws \Doctrine\DBAL\DBALException
     */
    public function count(array $where = [], array $parameters = [])
    {
        $qb = $this->createQueryBuilder()
            ->select($this->getDbManager()->getConnection()->getDatabasePlatform()->getCountExpression('*'))
            ->from($this->getTableName())
        ;

        foreach($where as $condition){
            $qb->andWhere($condition);
        }

        foreach($parameters as $param => $value){
            $qb->setParameter($param, $value);
        }

        return (int)$qb->execute()->fetchColumn(0);
    }

    /**
     * @param string[] $where
     * @param mixed[] $parameters
     * @return mixed[]
     */
    public function findEndpoints(array $where = [], array $parameters = [])
    {
        $columns = array_keys($this->getEndpointColumns());

        $qb = $this->createQueryBuilder()
            ->select($columns)
            ->from($this->getTableName())
        ;

        foreach($where as $condition){
            $qb->andWhere($condition);
        }

        foreach($parameters as $param => $value){
            $qb->setParameter($param, $value);
        }

        $stmt = $qb->execute();

        $result = [];
        foreach($stmt->fetchAll() as $endpointData)
        {
            $result[] = $this->buildEndpoint($endpointData);
        }
        return $result;
    }

    /**
     * @param array $endpoints
     * @return array|string[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findNotFetchedEndpoints(array $endpoints)
    {
        $platform = $this->getConnection()->getDatabasePlatform();
        $primaryColumns = array_keys($this->getPrimaryColumns());

        $concatArray = [];
        foreach($primaryColumns as $column)
        {
            $concatArray[] = $column;
            if($column !== end($primaryColumns)){
                $concatArray[] = $this->getConnection()->quote($this->endpointDelimiter);
            }
        }

        $preparedEndpoints = [];
        foreach($endpoints as $endpoint) {
            $extracted = array_filter($this->extractEndpoint($endpoint), function($key) use ($primaryColumns){
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
        if(is_array($fetchedEndpoints) && !empty($fetchedEndpoints)){
            foreach($preparedEndpoints as $prepared => $endpoint) {
                if(in_array($prepared, $fetchedEndpoints)) {
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
    public function getEndpointDelimiter()
    {
        return $this->endpointDelimiter;
    }

    /**
     * @param string $endpointDelimiter
     */
    public function setEndpointDelimiter($endpointDelimiter)
    {
        $this->endpointDelimiter = $endpointDelimiter;
    }

    /**
     * @param mixed[] $data
     * @return string
     */
    public function buildEndpoint(array $data)
    {
        return $this->implodeEndpoint($data);
    }

    /**
     * @param string $endpointId
     * @return mixed[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function extractEndpoint($endpointId)
    {
        $data = $this->explodeEndpoint($endpointId);
        return $this->createEndpointData($data);
    }

    /**
     * @param int $hostId
     * @return \jtl\Connector\CDBC\Query\QueryBuilder
     */
    protected function createEndpointIdQuery($hostId)
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
    protected function explodeEndpoint($endpointId)
    {
        return explode($this->endpointDelimiter, $endpointId);
    }

    /**
     * @param mixed[] $data
     * @return string
     */
    protected function implodeEndpoint(array $data)
    {
        return implode($this->endpointDelimiter, $data);
    }

    /**
     * @param mixed[] $data
     * @return mixed[]
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function createEndpointData(array $data)
    {
        $columns = $this->getEndpointColumns();
        $dataCount = count($data);
        $columnNames = array_keys($columns);
        if($dataCount < count($columns)){
            throw RuntimeException::columnDataMissing($columnNames[$dataCount]);
        }
        return $this->mapRow(array_combine($columnNames, $data), $columnNames);
    }

    /**
     * @param string $name
     * @param string $type
     * @param mixed $options
     * @param boolean $primary
     * @return AbstractMappingTable
     * @throws RuntimeException
     */
    protected function addEndpointColumn($name, $type, array $options = [], $primary = true)
    {
        if($this->hasEndpointColumn($name)){
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
    protected function hasEndpointColumn($name)
    {
        return isset($this->columns[$name]);
    }

    /**
     * @return string[]
     */
    protected function getEndpointColumns()
    {
        return $this->columns;
    }

    /**
     * @return string[]
     */
    protected function getPrimaryColumns()
    {
        return array_filter($this->columns, function(array $column) {
            return isset($column['primary']) && $column['primary'] === true;
        });
    }
}
