<?php

declare(strict_types=1);

namespace Jtl\Connector\Dbc\Console\Command;

use Jtl\Connector\Dbc\DbManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;

abstract class AbstractDbManagerCommand extends Command
{
    /**
     * @var array<string, mixed>
     */
    protected array $dbParams;

    /**
     * @var DbManager
     */
    protected DbManager $dbManager;

    /**
     * AbstractDbManagerCommand constructor.
     *
     * @param DbManager   $dbManager
     * @param string|null $name
     *
     * @throws LogicException
     */
    public function __construct(DbManager $dbManager, string $name = null)
    {
        $this->dbManager = $dbManager;
        $this->dbParams  = $dbManager->getConnection()->getParams();
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
