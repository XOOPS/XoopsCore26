<?php

namespace XoopsConsole\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Xoops\Core\XoopsTpl;

class CiInstallCommand extends Command
{
    /**
     * establish the command configuration
     * @return void
     */
    protected function configure()
    {
        $this->setName("ci-install")
            ->setDescription("Install a minimal XOOPS for CI processes")
            ->setDefinition(array())
            ->setHelp(<<<EOT
The <info>ci-install</info> command installs a default XOOPS system for use in the
travis-ci continuous integration environment. This command expects on an
appropriate mainfile.php to have been previously created, possibly using the
<info>ci-bootstrap</info> command (only available if mainfile.php does not exist.)
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
        // install the 'system' module
        $xoops = \Xoops::getInstance();
        $module = 'system';
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
            $output->writeln(sprintf('<error>Install of %s module failed!</error>', $module));
        } else {
            $output->writeln(sprintf('<info>Install of %s module completed.</info>', $module));
        }
        $xoops->cache()->delete('system');

        // add an admin user
        $adminname = 'admin';
        $adminpass = password_hash($adminname, PASSWORD_DEFAULT); // user: admin pass: admin
        $regdate = time();
        $result = $xoops->db()->insertPrefix(
            'system_user',
            array(
            //  'uid'             => 1,             // mediumint(8) unsigned NOT NULL auto_increment,
                'uname'           => $adminname,    // varchar(25) NOT NULL default '',
                'email'           => 'nobody@localhost',    // varchar(60) NOT NULL default '',
                'user_regdate'    => $regdate,      // int(10) unsigned NOT NULL default '0',
                'user_viewemail'  => 1,             // tinyint(1) unsigned NOT NULL default '0',
                'pass'            => $adminpass,    // varchar(255) NOT NULL default '',
                'rank'            => 7,             // smallint(5) unsigned NOT NULL default '0',
                'level'           => 5,             // tinyint(3) unsigned NOT NULL default '1',
                'last_login'      => $regdate,      // int(10) unsigned NOT NULL default '0',
            )
        );
        $output->writeln(sprintf('<info>Inserted %d user.</info>', $result));

        return Command::SUCCESS;
    }
}
