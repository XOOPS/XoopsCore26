<?php

namespace XoopsConsole\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;

class Utf8mb4ModuleCommand extends Command
{
    protected function configure()
    {
        $this->setName("utf8mb4-module")
            ->setDescription("Update a module's tables to utf8mb4")
            ->setDefinition(array(
                new InputArgument('module', InputArgument::REQUIRED, 'Module directory name'),
            ))
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show but do not execute DDL.')
            ->setHelp(<<<EOT
The <info>utf8mb4-module</info> command updates the tables that are owned by an installed module
to use MySQL's <info>utf8mb4</info> character set, and <info>utf8mb4_unicode_ci</info> collation.
EOT
             );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dirname = $input->getArgument('module');

        $dryRun = false;
        if ($input->getOption('dry-run')) {
            $output->writeln('<info>dry-run option selected.</info>');
            $dryRun = true;
        }

        $output->writeln(sprintf('Updating %s tables', $dirname));
        $xoops = \Xoops::getInstance();
        $module = $xoops->getModuleByDirname($dirname);
        if (false === $module) {
            $output->writeln(sprintf('<error>%s is not an installed module!</error>', $dirname));
            return Command::FAILURE;
        }
        $module->loadInfo($dirname, false);
        $modVersion = $module->modinfo;
        $tableList =  isset($modVersion['tables']) ? $modVersion['tables'] : [];
        //\Kint::dump($modVersion, $tableList);
        $sql = [];

        $manager = $xoops->db()->getSchemaManager();
        $platform = $xoops->db()->getDatabasePlatform();

        if ('mysql' !== $platform->getName()) {
            $output->writeln('<error>This command only works on a MySQL platform.</error>');
            return Command::FAILURE;
        }

        foreach ($tableList as $tableIn) {
            $table = $xoops->db()->prefix($tableIn);

            $sql[] = sprintf(
                'ALTER TABLE %s CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;',
                $platform->quoteIdentifier($table)
            );
            $columns = $manager->listTableColumns($table);
            foreach ($columns as $column) {
                $type = $column->getType()->getName();
                if ($type === Types::STRING || $type === Types::TEXT) {
                    //$column->setPlatformOption('collation', 'utf8mb4_unicode_ci');
                    $sql[] = sprintf(
                        'ALTER TABLE %s MODIFY %s %s COLLATE utf8mb4_unicode_ci;',
                        $platform->quoteIdentifier($table),
                        $platform->quoteIdentifier($column->getName()),
                        $column->getType()->getSQLDeclaration($column->toArray(), $platform)
                    );
                }
            }
        }
        foreach ($sql as $alterSql) {
            $output->writeln(sprintf('<info>Executing:</info> %s', $alterSql));
            if (!$dryRun) {
                $xoops->db()->setForce(true);
                $result = $xoops->db()->query($alterSql);
                if ($result === false) {
                    $output->writeln(sprintf(
                        '<error>Execution failed: %d - %s</error>',
                        $xoops->db()->errorCode(),
                        implode(' - ', $xoops->db()->errorInfo())
                    ));
                    \Kint::dump($xoops->db()->errorInfo());
                }
            }
        }

        return Command::SUCCESS;
    }
}
