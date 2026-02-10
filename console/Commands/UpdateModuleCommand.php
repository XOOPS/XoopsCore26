<?php

namespace XoopsConsole\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Xoops\Core\XoopsTpl;

class UpdateModuleCommand extends Command
{
    protected function configure()
    {
        $this->setName("update-module")
            ->setDescription("Update a module")
            ->setDefinition(array(
                new InputArgument('module', InputArgument::REQUIRED, 'Module directory name'),
            ))->setHelp(<<<EOT
The <info>update-module</info> command updates a currently installed module.

This can be especially useful if the module configuration has changed, and
it is interfering with normal online operation.
EOT
             );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $module = $input->getArgument('module');
        $output->writeln(sprintf('Updating %s', $module));
        $xoops = \Xoops::getInstance();
        if (false === $xoops->getModuleByDirname($module)) {
            $output->writeln(sprintf('<error>%s is not an installed module!</error>', $module));
            return Command::FAILURE;
        }
        $xoops->setTpl(new XoopsTpl());
        \XoopsLoad::load('module', 'system');
        $sysmod = new \SystemModule();
        $result = $sysmod->update($module);
        foreach ($sysmod->trace as $message) {
            if (is_array($message)) {
                foreach ($message as $subMessage) {
                    if (!is_array($subMessage)) {
                        $output->writeln(strip_tags($subMessage));
                    }
                }
            } else {
                $output->writeln(strip_tags($message));
            }
        }
        if ($result===false) {
            $output->writeln(sprintf('<error>Update of %s failed!</error>', $module));
        } else {
            $output->writeln(sprintf('<info>Update of %s completed.</info>', $module));
        }
        $xoops->cache()->delete('system');

        return Command::SUCCESS;
    }
}
