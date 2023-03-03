<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc\Event;

use Doctrine\DBAL\Schema\Table;
use Symfony\Contracts\EventDispatcher\Event;

class TableEvent extends Event
{
    /**
     * @var Table
     */
    protected Table $table;

    /**
     * TableEvent constructor.
     *
     * @param Table $table
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    /**
     * @return Table
     */
    public function getTable(): Table
    {
        return $this->table;
    }
}
