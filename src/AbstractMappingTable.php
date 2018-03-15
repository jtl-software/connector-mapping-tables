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
     * @throws TableException
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
     * @throws MappingTableException
     * @return void
     */
    protected function createTableSchema(Table $tableSchema)
    {
        $endpointColumns = $this->getEndpointColumns();
        $primaryColumns = $this->getPrimaryColumns();
        if(count($endpointColumns) === 0){
            throw MappingTableException::endpointColumnsNotDefined();
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
     * @param string $endpointId
     * @return null|integer
     */
    public function getHostId($endpointId)
    {
        $qb = $this->createQueryBuilder()
            ->select(self::HOST_ID)
            ->from($this->getTableName());

        foreach($this->extractEndpoint($endpointId) as $column => $value) {
            $qb->andWhere($column . ' = :' . $column)
               ->setParameter($column, $value);
        }

        $column = $qb->execute()->fetchColumn(0);
        if($column !== false){
            return (int)$column;
        }
        return null;
    }

    /**
     * @param integer $hostId
     * @return null|string
     */
    public function getEndpointId($hostId)
    {
        $columns = array_keys($this->getEndpointColumns());
        $endpointData = $this->createQueryBuilder()
            ->select($columns)
            ->from($this->getTableName())
            ->where(self::HOST_ID . ' = :' . self::HOST_ID)
            ->setParameter(self::HOST_ID, $hostId)
            ->execute()
            ->fetch();

        if(is_array($endpointData)){
            return $this->buildEndpoint($endpointData);
        }
        return null;
    }

    /**
     * @param string $endpointId
     * @param integer $hostId
     * @return integer
     */
    public function save($endpointId, $hostId)
    {
        $data = $this->extractEndpoint($endpointId);
        $data[self::HOST_ID] = $hostId;
        return $this->getConnection()->insert($this->getTableName(), $data);
    }

    /**
     * @param string|null $endpointId
     * @param integer|null $hostId
     * @return integer
     */
    public function remove($endpointId = null, $hostId = null)
    {
        $qb = $this->createQueryBuilder();
        $qb->delete($this->getTableName());

        if($endpointId !== null){
            foreach($this->extractEndpoint($endpointId) as $column => $value){
                $qb->andWhere($column . ' = :' . $column)
                   ->setParameter($column, $value);
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

       return is_int($rows) && $rows >= 0;
    }

    /**
     * @param string[] $where
     * @param mixed[] $parameters
     * @return integer
     * @throws TableException
     */
    public function count(array $where = [], array $parameters = [])
    {
        $qb = $this->createQueryBuilder();
        $qb
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
    public function findAllEndpoints(array $where = [], array $parameters = [])
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
     * @param mixed[] $endpoints
     * @return mixed[]
     */
    public function findNotFetchedEndpoints(array $endpoints)
    {
        $platform = $this->getConnection()->getDatabasePlatform();
        $columns = array_keys($this->getEndpointColumns());
        $concatArray = [];
        foreach($columns as $column)
        {
            $concatArray[] = $column;
            if($column !== end($columns)){
                $concatArray[] = $this->getConnection()->quote($this->endpointDelimiter);
            }
        }

        $concatExpression = call_user_func_array([$platform, 'getConcatExpression'], $concatArray);
        $qb = $this->createQueryBuilder()
            ->select($concatExpression)
            ->from($this->getTableName())
            ->where($this->getConnection()->getExpressionBuilder()->in($concatExpression, ':endpoints'))
            ->setParameter('endpoints', $endpoints, Connection::PARAM_STR_ARRAY);

        $fetchedEndpoints = $qb->execute()->fetchAll(\PDO::FETCH_COLUMN);
        if(is_array($fetchedEndpoints)){
            return array_diff($endpoints, $fetchedEndpoints);
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
     */
    public function extractEndpoint($endpointId)
    {
        $data = $this->explodeEndpoint($endpointId);
        return $this->createEndpointData($data);
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
     * @throws MappingTableException
     */
    protected function createEndpointData(array $data)
    {
        $columns = $this->getEndpointColumns();
        $dataCount = count($data);
        $columnNames = array_keys($columns);
        if($dataCount < count($columns)){
            throw MappingTableException::columnDataMissing($columnNames[$dataCount]);
        }
        return $this->mapRow(array_combine($columnNames, $data), $columnNames);
    }

    /**
     * @param string $name
     * @param string $type
     * @param mixed $options
     * @param boolean $primary
     * @return AbstractMappingTable
     * @throws MappingTableException
     */
    protected function addEndpointColumn($name, $type, array $options = [], $primary = true)
    {
        if($this->hasEndpointColumn($name)){
            throw MappingTableException::columnExists($name);
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
     * @return mixed[]
     */
    protected function getEndpointColumns()
    {
        return $this->columns;
    }

    /**
     * @return mixed[]
     */
    protected function getPrimaryColumns()
    {
        return array_filter($this->columns, function(array $column) {
            return isset($column['primary']) && $column['primary'] === true;
        });
    }
}