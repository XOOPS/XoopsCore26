<?php

namespace XoopsConsole\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Xoops\Core\XoopsTpl;

class InstallModuleCommand extends Command
{
    /**
     * establish the command configuration
     * @return void
     */
    protected function configure()
    {
        $this->setName("install-module")
            ->setDescription("Install a module")
            ->setDefinition(array(
                new InputArgument('module', InputArgument::REQUIRED, 'Module directory name'),
            ))->setHelp(<<<EOT
The <info>install-module</info> command installs a module.
EOT
             );
    }

    /**
     * execute the command
     *
     * @param InputInterface  $input  input handler
     * @param OutputInterface $output output handler
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $xoops = \Xoops::getInstance();
        $module = $input->getArgument('module');
        if (false === \XoopsLoad::fileExists($xoops->path("modules/$module/xoops_version.php"))) {
            $output->writeln(sprintf('<error>No module named %s found!</error>', $module));
            return Command::FAILURE;
        }
        $output->writeln(sprintf('Installing %s', $module));
        if (false !== $xoops->getModuleByDirname($module)) {
            $output->writeln(sprintf('<error>%s module is already installed!</error>', $module));
            return Command::FAILURE;
        }
        $xoops->setTpl(new XoopsTpl());
        \XoopsLoad::load('module', 'system');
        $sysmod = new \SystemModule();
        $result = $sysmod->install($module);
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
            $output->writeln(sprintf('<error>Install of %s failed!</error>', $module));
        } else {
            $output->writeln(sprintf('<info>Install of %s completed.</info>', $module));
        }
        $xoops->cache()->delete('system');

        return Command::SUCCESS;
    }
}
