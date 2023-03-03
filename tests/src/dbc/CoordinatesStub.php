<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

class CoordinatesStub extends AbstractTable
{
    public const
        COL_X = 'x',
        COL_Y = 'y',
        COL_Z = 'z';

    public const
        TABLE_NAME = 'coordinates';

    public function getName(): string
    {
        return self::TABLE_NAME;
    }

    /**
     * @param float $x
     * @param float $y
     * @param float $z
     *
     * @return boolean
     * @throws DBALException
     */
    public function addCoordinate(float $x, float $y, float $z): bool
    {
        $data  = [self::COL_X => $x, self::COL_Y => $y, self::COL_Z => $z];
        $types = [self::COL_X => Type::FLOAT, self::COL_Y => Type::FLOAT, self::COL_Z => Type::FLOAT];

        return $this->getConnection()
                    ->insert($this->getTableName(), $data, $types) > 0;
    }

    /**
     * @return float[]
     * @throws Exception
     */
    public function findAll(): array
    {
        return $this->findBy();
    }

    /**
     * @param float[] $parameters
     *
     * @return float[]
     * @throws Exception
     */
    protected function findBy(array $parameters = []): array
    {
        $qb = $this->createQueryBuilder();
        $qb->select(self::COL_X, self::COL_Y, self::COL_Z)
           ->from($this->getTableName());

        foreach ($parameters as $column => $value) {
            $qb->where($column . ' = :' . $column)->setParameter($column, $value);
        }
        $stmt = $qb->execute();
        return $stmt->fetchAll();
    }

    /**
     * @param float $x
     *
     * @return float[]
     * @throws Exception
     */
    public function findByX(float $x): array
    {
        return $this->findBy([self::COL_X => $x]);
    }

    /**
     * @param float $y
     *
     * @return float[]
     * @throws Exception
     */
    public function findByY(float $y): array
    {
        return $this->findBy([self::COL_Y => $y]);
    }

    /**
     * @param float $z
     *
     * @return float[]
     * @throws Exception
     */
    public function findByZ(float $z): array
    {
        return $this->findBy([self::COL_Z => $z]);
    }

    protected function createTableSchema(Table $tableSchema): void
    {
        $tableSchema->addColumn(self::COL_X, Type::FLOAT, ['default' => 0.0]);
        $tableSchema->addColumn(self::COL_Y, Type::FLOAT, ['default' => 0.0]);
        $tableSchema->addColumn(self::COL_Z, Type::FLOAT, ['default' => 0.0]);
        $tableSchema->setPrimaryKey([self::COL_X, self::COL_Y, self::COL_Z]);
    }
}
