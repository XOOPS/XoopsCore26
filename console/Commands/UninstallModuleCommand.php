<?php

namespace XoopsConsole\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Xoops\Core\XoopsTpl;

class UninstallModuleCommand extends Command
{
    protected function configure()
    {
        $this->setName("uninstall-module")
            ->setDescription("Uninstall a module")
            ->setDefinition(array(
                new InputArgument('module', InputArgument::REQUIRED, 'Module directory name'),
            ))->setHelp(<<<EOT
The <info>uninstall-module</info> command uninstalls a currently installed module.
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $module = $input->getArgument('module');
        $output->writeln(sprintf('Uninstalling %s', $module));
        $xoops = \Xoops::getInstance();
        if (false === $xoops->getModuleByDirname($module)) {
            $output->writeln(sprintf('<error>%s is not an installed module!</error>', $module));
            return Command::FAILURE;
        }
        $xoops->setTpl(new XoopsTpl());
        \XoopsLoad::load('module', 'system');
        $sysmod = new \SystemModule();
        $result = $sysmod->uninstall($module);
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
            $output->writeln(sprintf('<error>Uninstall of %s failed!</error>', $module));
        } else {
            $output->writeln(sprintf('<info>Uninstall of %s completed.</info>', $module));
        }
        $xoops->cache()->delete('system');

        return Command::SUCCESS;
    }
}
