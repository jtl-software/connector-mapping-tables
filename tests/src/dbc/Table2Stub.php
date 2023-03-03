<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */
namespace Jtl\Connector\Dbc;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

class Table2Stub extends AbstractTable
{
    public const ID = 'id';
    public const A  = 'a';

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'table2';
    }


    /**
     * @param Table $tableSchema
     * @return void
     */
    protected function createTableSchema(Table $tableSchema): void
    {
        $tableSchema->addColumn(self::ID, Type::INTEGER, ['autoincrement' => true]);
        $tableSchema->addColumn(self::A, Type::INTEGER, ['notnull' => false]);
        $tableSchema->setPrimaryKey([self::ID]);
    }
}
