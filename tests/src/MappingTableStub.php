<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */
namespace Jtl\Connector\MappingTables;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;

class MappingTableStub extends AbstractMappingTable
{
    const COL_ID1 = 'id1';
    const COL_ID2 = 'id2';
    const COL_VAR = 'strg';

    const TYPE = 815;
    const TABLE_NAME = 'mapping_table';

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
            ->addEndpointColumn(self::COL_ID1, Types::INTEGER)
            ->addEndpointColumn(self::COL_ID2, Types::INTEGER)
            ->addEndpointColumn(self::COL_VAR, Types::STRING, [], false)
        ;
    }

    /**
     * @return integer
     */
    public function getType(): int
    {
        return self::TYPE;
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

    public function addEndpointColumn($name, $type, array $options = [], $primary = true): AbstractMappingTable
    {
        return parent::addEndpointColumn($name, $type, $options, $primary);
    }

    public function hasEndpointColumn($name): bool
    {
        return parent::hasEndpointColumn($name);
    }

    public function getEndpointColumns(): array
    {
        return parent::getEndpointColumns();
    }
}
