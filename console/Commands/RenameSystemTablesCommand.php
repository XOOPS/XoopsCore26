<?php

namespace XoopsConsole\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class RenameSystemTablesCommand extends Command
{
    protected function configure()
    {
        $this->setName('rename-system-tables')
            ->setDescription('Update the XOOPS Kernel tables')
            ->addOption('undo', null, InputOption::VALUE_NONE, 'Revert to 2.5.x style names')
            ->setHelp(<<<EOT
The <info>rename-system-tables</info> command updates the XOOPS kernel
database tables that are managed by the system module.
EOT
             );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $tableNames = [
            'newblocks'        => 'system_block',
            'block_module_link'=> 'system_blockmodule',
            'config'           => 'system_config',
            'configoption'     => 'system_configoption',
            'groups'           => 'system_group',
            'group_permission' => 'system_permission',
            'groups_users_link'=> 'system_usergroup',
            'modules'          => 'system_module',
            'online'           => 'system_online',
            'priv_msgs'        => 'system_privatemessage',
            'ranks'            => 'userrank_rank',
            'tplfile'          => 'system_tplfile',
            'tplset'           => 'system_tplset',
            'tplsource'        => 'system_tplsource',
            'users'            => 'system_user',
        ];

        $undo = false;
        if ($input->getOption('undo')) {
            $output->writeln('<info>undo option selected.</info>');
            $undo = true;
        }

        $migrate = new \Xmf\Database\Tables();

        $renameTable = function ($existingName, $newName) use ($migrate) {
            $status = $migrate->useTable($newName);
            if (!$status) {
                $status = $migrate->useTable($existingName);
                if ($status) {
                    $migrate->renameTable($existingName, $newName);
                }
            }
            return $status;
        };

        foreach ($tableNames as $oldName => $newName) {
            if ($undo) {
                $renameTable($newName, $oldName);
            } else {
                $renameTable($oldName, $newName);
            }
        }

        //var_dump($migrate->dumpQueue());
        $migrate->executeQueue(true);

        return Command::SUCCESS;
    }
}
