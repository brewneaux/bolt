<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to perform a database consistency check command
 */
class DatabaseCheck extends BaseCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('database:check')
            ->setDescription('Check the database for missing tables and/or columns.')
            ->addOption('show-changes', 's', InputOption::VALUE_NONE, 'Show proposed schema changes')
        ;
    }

    /**
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $response \Bolt\Storage\Database\Schema\SchemaCheck */
        $response = $this->app['schema']->check();

        if (!$response->hasResponses()) {
            $output->writeln('<info>The database is OK.</info>');
        } else {
            $output->writeln('<comment>Modifications required:</comment>');
            foreach ($response->getResponseStrings() as $messages) {
                $output->writeln('<info> - ' . $messages . '</info>');
            }
            $output->writeln("<comment>One or more fields/tables are missing from the Database. Please run 'nut database:update' to fix this.</comment>");
        }

        if ($input->getOption('show-changes')) {
            $output->writeln('<comment>Proposed modifications:</comment>');
            $output->writeln("\n");
            $this->showDiffs($output);
        }
    }

    /**
     * @param OutputInterface $output
     */
    protected function showDiffs(OutputInterface $output)
    {
        $this->showCreates($output);
        $this->showAlterations($output);        
    }

    /**
     * @param OutputInterface $output
     */
    protected function showCreates($output)
    {
        $creates = $this->app['schema.comparator']->getCreates();
        if ($creates) {
            foreach ($creates as $tableName => $sql) {
                $output->writeln('Table: ' . $tableName);
                $output->writeln("    " . $sql[0]);
                $output->writeln("\n");
            }
        }
    }

    /**
     * @param OutputInterface $output
     */
    protected function showAlterations($output)
    {
        $creates = $this->app['schema.comparator']->getAlters();
        if ($creates) {
            foreach ($creates as $tableName => $sql) {
                $output->writeln('Table: ' . $tableName);
                $output->writeln("    " . $sql[0]);
                $output->writeln("\n");
            }
        }
    }
}
