<?php


namespace Jtl\Connector\Dbc\Console\Command;

use Drieschel\Oauth2\Server\Database\TableFactory;
use Jtl\Connector\Dbc\DbManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateDatabaseSchemaCommand extends AbstractDbManagerCommand
{
    public const
        OPTION_FORCE = 'force';

    protected static $defaultName = 'database:update-schema';

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setDescription('Creates/Updates the database schema')
            ->addOption(self::OPTION_FORCE, null, InputOption::VALUE_OPTIONAL, 'Execute statements directly, instead of returning them to the output.', false);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $force = $input->getOption(self::OPTION_FORCE) !== false;
        if ($this->dbManager->hasSchemaUpdates()) {
            if ($force) {
                $this->dbManager->updateDatabaseSchema();
                $output->writeln('Schema updated.');
            } else {
                foreach ($this->dbManager->getSchemaUpdates() as $statement) {
                    $output->writeln(sprintf('%s;', $statement));
                }
            }
        } else {
            $output->writeln('Nothing to update.');
        }

        return self::SUCCESS;
    }
}
