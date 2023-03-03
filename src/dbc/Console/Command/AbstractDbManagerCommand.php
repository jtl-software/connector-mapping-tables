<?php

namespace Jtl\Connector\Dbc\Console\Command;

use Jtl\Connector\Dbc\DbManager;
use Symfony\Component\Console\Command\Command;

abstract class AbstractDbManagerCommand extends Command
{
    /**
     * @var string[]
     */
    protected $dbParams;

    /**
     * @var DbManager
     */
    protected $dbManager;

    /**
     * AbstractDbManagerCommand constructor.
     * @param DbManager $dbManager
     * @param string|null $name
     */
    public function __construct(DbManager $dbManager, string $name = null)
    {
        $this->dbManager = $dbManager;
        $this->dbParams = $dbManager->getConnection()->getParams();
        parent::__construct($name);
    }

    /**
     * @param callable $callback
     */
    public function registerTables(callable $callback): void
    {
        $callback($this->dbManager);
    }
}
