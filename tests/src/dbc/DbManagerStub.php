<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc;

use Doctrine\DBAL\DBALException;
use RuntimeException;

class DbManagerStub extends DbManager
{
    /**
     * @return array<int, AbstractTable>
     */
    public function getTables(): array
    {
        return parent::getTables();
    }

    /**
     * @return array<int, \Doctrine\DBAL\Schema\Table>
     * @throws DBALException
     * @throws DbcRuntimeException
     * @throws DbcRuntimeException
     */
    public function getSchemaTables(): array
    {
        return parent::getSchemaTables();
    }
}
