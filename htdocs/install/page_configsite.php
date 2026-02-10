<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xoops\Core\Kernel\Criteria;
use Xoops\Core\Kernel\CriteriaCompo;

/**
 * @copyright   XOOPS Project (http://xoops.org)
 * @license     GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package     installer
 * @since       2.3.0
 * @author      Haruki Setoyama  <haruki@planewave.org>
 * @author      Kazumi Ono <webmaster@myweb.ne.jp>
 * @author      Skalpa Keo <skalpa@xoops.org>
 * @author      Taiwen Jiang <phppp@users.sourceforge.net>
 * @author      DuGris (aka L. JEN) <dugris@frxoops.org>
 * @version     $Id$
 */

$xoopsOption['checkadmin'] = true;
$xoopsOption['hascommon'] = true;

require_once __DIR__ . '/include/common.inc.php';

$xoops = Xoops::getInstance();

// setup legacy db support
$GLOBALS['xoopsDB'] = \XoopsDatabaseFactory::getDatabaseConnection(true);

/* @var $wizard XoopsInstallWizard */
$wizard = $_SESSION['wizard'];
$xoops->loadLocale('system');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config_handler = $xoops->getHandlerConfig();
    if (array_key_exists('conf_ids', $_REQUEST)) {
        foreach ($_REQUEST['conf_ids'] as $key => $conf_id) {
            $config = $config_handler->getConfig($conf_id);
            if ($config === null) {
                continue;
            }
            $new_value = $_REQUEST[$config->getVar('conf_name')];
            $config->setConfValueForInput($new_value);
            $config_handler->insertConfig($config);
        }
    }
    $wizard->redirectToPage('+1');
}

$config_handler = $xoops->getHandlerConfig();
$criteria = new CriteriaCompo();
$criteria->add(new Criteria('conf_modid', 1));

$criteria2 = new CriteriaCompo();
foreach ($wizard->configs['conf_names'] as $conf_name) {
    $criteria2->add(new Criteria('conf_name', $conf_name), 'OR');
}
$criteria->add($criteria2);
$criteria->setSort('conf_catid ASC, conf_order');
$configs = $config_handler->getConfigs($criteria);

include XOOPS_INSTALL_PATH . '/include/createconfigform.php';
$wizard->form = createConfigform($configs);

$_SESSION['pageHasHelp'] = true;
$_SESSION['pageHasForm'] = true;
$_SESSION['content'] = $wizard->CreateForm();
include XOOPS_INSTALL_PATH . '/include/install_tpl.php';
