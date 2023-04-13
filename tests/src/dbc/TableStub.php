<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ForwardCompatibility\Result;
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
     * @param mixed  $value
     *
     * @return AbstractTable
     * @throws DBALException
     * @throws DbcRuntimeException
     * @throws SchemaException|\RuntimeException
     */
    public function restrict(string $column, $value): AbstractTable
    {
        return parent::restrict($column, $value);
    }

    /**
     * @param int                     $fetchType
     * @param array<int, string>|null $columns
     *
     * @return array<int, array<string>>
     * @throws DBALException
     * @throws Exception
     * @throws DbcRuntimeException|\RuntimeException
     */
    public function findAll(int $fetchType = \PDO::FETCH_ASSOC, array $columns = null): array
    {
        if (\is_null($columns)) {
            $columns = $this->getColumnNames();
        }

        $stmt = $this->createQueryBuilder()->select($columns)
                     ->from($this->getTableName())
                     ->execute();

        if ($stmt instanceof Result === false) {
            throw new \RuntimeException('$stmt must be instance of ' . Result::class);
        }

        /** @var array<int, array<string>> $result */
        $result = $stmt->fetchAll($fetchType);

        return $this->convertAllToPhpValues($result);
    }

    /**
     * @param array<string, mixed> $identifier
     * @param int                  $fetchType
     * @param array<string>|null   $columns
     *
     * @return array<int, array<string>>
     * @throws DBALException
     * @throws Exception
     * @throws DbcRuntimeException
     * @throws \RuntimeException
     */
    public function find(array $identifier, int $fetchType = \PDO::FETCH_ASSOC, array $columns = null): array
    {
        if (\is_null($columns)) {
            $columns = $this->getColumnNames();
        }

        $qb = $this->createQueryBuilder()->select($columns)
                   ->from($this->getTableName());

        foreach ($identifier as $column => $value) {
            $qb->andWhere(\sprintf('%s = :%s', $column, $column))
               ->setParameter($column, $value);
        }

        $stmt = $qb->execute();
        if ($stmt instanceof Result === false) {
            throw new \RuntimeException('$stmt must be instance of ' . Result::class);
        }

        /** @var array<int, array<string>> $result */
        $result = $stmt->fetchAll($fetchType);

        return $this->convertAllToPhpValues($result);
    }

    /**
     * @param Table $tableSchema
     *
     * @return void
     * @throws Exception
     * @throws SchemaException
     */
    protected function createTableSchema(Table $tableSchema): void
    {
        $tableSchema->addColumn(self::ID, Type::INTEGER, ['autoincrement' => true]);
        $tableSchema->addColumn(self::A, Type::INTEGER, ['notnull' => false]);
        $tableSchema->addColumn(self::B, Type::STRING, ['length' => 64]);
        $tableSchema->addColumn(self::C, Type::DATETIME_IMMUTABLE);
        $tableSchema->setPrimaryKey([self::ID]);
    }
}
