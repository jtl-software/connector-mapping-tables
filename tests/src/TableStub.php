<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */
namespace Jtl\Connector\MappingTables;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Jtl\Connector\Dbc\DbManager;

class TableStub extends AbstractTable
{
    const COL_ID1 = 'id1';
    const COL_ID2 = 'id2';
    const COL_VAR = 'strg';

    const TYPE1 = 815;
    const TYPE2 = 7;
    const TABLE_NAME = 'mapping_table';

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

    public function defineEndpoint(): void
    {
        $this
            ->addEndpointColumn(new Column(self::COL_ID1, Type::getType(Types::INTEGER)))
            ->addEndpointColumn(new Column(self::COL_ID2, Type::getType(Types::INTEGER)))
            ->addEndpointColumn(new Column(self::COL_VAR, Type::getType(Types::STRING)), false)
        ;
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

    public function addEndpointColumn(Column $column, $primary = true): AbstractTable
    {
        return parent::addEndpointColumn($column, $primary);
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
