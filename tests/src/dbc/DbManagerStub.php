<?php
/**
 * @author Immanuel Klinkenberg <immanuel.klinkenberg@jtl-software.com>
 * @copyright 2010-2017 JTL-Software GmbH
 */
namespace Jtl\Connector\Dbc;

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
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getSchemaTables(): array
    {
        return parent::getSchemaTables();
    }
}
