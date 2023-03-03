<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */
namespace Jtl\Connector\Dbc;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

class TableStub extends AbstractTable
{
    public const ID = 'id';
    public const A  = 'a';
    public const B  = 'b';
    public const C  = 'c';

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'table';
    }

    /**
     * @param string $column
     * @param mixed $value
     * @return AbstractTable
     * @throws DBALException
     * @throws SchemaException
     */
    public function restrict($column, $value): AbstractTable
    {
        return parent::restrict($column, $value);
    }

    /**
     * @param Table $tableSchema
     * @return void
     */
    protected function createTableSchema(Table $tableSchema): void
    {
        $tableSchema->addColumn(self::ID, Type::INTEGER, ['autoincrement' => true]);
        $tableSchema->addColumn(self::A, Type::INTEGER, ['notnull' => false]);
        $tableSchema->addColumn(self::B, Type::STRING, ['length' => 64]);
        $tableSchema->addColumn(self::C, Type::DATETIME_IMMUTABLE);
        $tableSchema->setPrimaryKey([self::ID]);
    }

    /**
     * @param int $fetchType
     * @param array|null $columns
     * @return mixed[]
     * @throws DBALException
     */
    public function findAll($fetchType = \PDO::FETCH_ASSOC, array $columns = null)
    {
        if (is_null($columns)) {
            $columns = $this->getColumnNames();
        }

        $stmt = $this->createQueryBuilder()->select($columns)
                                           ->from($this->getTableName())
                                           ->execute();

        return $this->convertAllToPhpValues($stmt->fetchAll($fetchType));
    }

    /**
     * @param array $identifier
     * @param int $fetchType
     * @param array $columns
     * @return array|mixed[]
     * @throws DBALException
     */
    public function find(array $identifier, $fetchType = \PDO::FETCH_ASSOC, array $columns = null)
    {
        if (is_null($columns)) {
            $columns = $this->getColumnNames();
        }

        $qb = $this->createQueryBuilder()->select($columns)
            ->from($this->getTableName());

        foreach ($identifier as $column => $value) {
            $qb->andWhere(sprintf('%s = :%s', $column, $column))
               ->setParameter($column, $value);
        }

        $stmt = $qb->execute();

        return $this->convertAllToPhpValues($stmt->fetchAll($fetchType));
    }
}
