<?php

namespace XoopsConsole\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Xoops\Core\XoopsTpl;

class ActivateModuleCommand extends Command
{
    protected function configure()
    {
        $this->setName("activate-module")
            ->setDescription("Activate an installed module")
            ->setDefinition(array(
                new InputArgument('module', InputArgument::REQUIRED, 'Module directory name'),
            ))->setHelp(<<<EOT
The <info>activate-module</info> command activates a currently installed module.
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $module = $input->getArgument('module');
        $output->writeln(sprintf('Activating %s', $module));
        $xoops = \Xoops::getInstance();
        $moduleHandler = $xoops->getHandlerModule();
        $moduleObject = $moduleHandler->getByDirname($module);
        if (false === $moduleObject) {
            $output->writeln(sprintf('<error>%s is not an installed module!</error>', $module));
            return Command::FAILURE;
        }
        $moduleObject->setVar('isactive', true);
        $moduleHandler->insert($moduleObject, true);

        $blockHandler = $xoops->getHandlerBlock();
        $blocks = $blockHandler->getByModule($moduleObject->getVar('mid'));
        foreach ($blocks as $block) {
            /* @var $block \Xoops\Core\Kernel\Handlers\XoopsBlock */
            $block->setVar('isactive', true);
            $blockHandler->insert($block);
        }
        $output->writeln(sprintf('<info>Set %s module active</info>', $module));
        $xoops->cache()->delete('system');

        return Command::SUCCESS;
    }
}
