<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc;

use Doctrine\DBAL\DBALException;

class DbManagerStub extends DbManager
{
    /**
     * @return array
     */
    public function getTables(): array
    {
        return parent::getTables();
    }

    /**
     * @return array
     * @throws DBALException
     */
    public function getSchemaTables(): array
    {
        return parent::getSchemaTables();
    }
}
