<?php

declare(strict_types=1);

namespace Jtl\Connector\MappingTables;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Jtl\Connector\Dbc\DbManager;

class TableStub extends AbstractTable implements TableInterface
{
    public const COL_ID1 = 'id1';
    public const COL_ID2 = 'id2';
    public const COL_VAR = 'strg';

    public const TYPE1      = 815;
    public const TYPE2      = 7;
    public const TABLE_NAME = 'mapping_table';

    public function __construct(DbManager $dbManager)
    {
        parent::__construct($dbManager, false);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::TABLE_NAME;
    }

    /**
     * @throws MappingTablesException
     * @throws Exception
     */
    public function defineEndpoint(): void
    {
        $this->setEndpointColumn(self::COL_ID1, Types::INTEGER);
        $this->setEndpointColumn(self::COL_ID2, Types::INTEGER);
        $this->setEndpointColumn(self::COL_VAR, Types::STRING, false);
    }

    public function setEndpointColumn(string $columnName, string $columnType, bool $primary = true): Column
    {
        return parent::setEndpointColumn($columnName, $columnType, $primary);
    }

    /**
     * @return integer[]
     */
    public function getTypes(): array
    {
        return [self::TYPE1, self::TYPE2];
    }

    public function createTableSchema(Table $tableSchema): void
    {
        parent::createTableSchema($tableSchema);
    }

    public function explodeEndpoint($endpointId): array
    {
        return parent::explodeEndpoint($endpointId);
    }

    public function implodeEndpoint(array $data): string
    {
        return parent::implodeEndpoint($data);
    }

    public function createEndpointData(array $data): array
    {
        return parent::createEndpointData($data);
    }

    public function hasEndpointColumn($name): bool
    {
        return parent::hasEndpointColumn($name);
    }

    public function getEndpointColumns(bool $onlyPrimaryColumns = false): array
    {
        return parent::getEndpointColumns($onlyPrimaryColumns);
    }
}
