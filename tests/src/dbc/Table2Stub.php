<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\SchemaException;
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
     *
     * @return void
     * @throws Exception
     * @throws SchemaException
     */
    protected function createTableSchema(Table $tableSchema): void
    {
        $tableSchema->addColumn(self::ID, Type::INTEGER, ['autoincrement' => true]);
        $tableSchema->addColumn(self::A, Type::INTEGER, ['notnull' => false]);
        $tableSchema->setPrimaryKey([self::ID]);
    }
}
