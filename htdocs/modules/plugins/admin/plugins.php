<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xmf\Request;
use PluginsLocale as t;

/**
 * plugins module
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         plugins
 * @since           2.6.0
 * @author          trabis <lusopoemas@gmail.com>
 * @version         $Id$
 */
include __DIR__ . '/header.php';

// Get main instance
$xoops = Xoops::getInstance();
$helper = Plugins::getInstance();
$handler = $helper->getHandlerPlugin();

//Adds new plugins if available and remove them if modules were deactivated
PluginsManager::updatePlugins();

// Call header
$xoops->header('admin:plugins/plugins_admin_plugins.tpl');
// Get Action type
$op = Request::getCmd('op', 'list');
// Get start pager
$start = Request::getInt('start', 0);
// Listener
$listener = Request::getCmd('listener');
// Caller
$caller = Request::getCmd('caller');

$admin_page = new \Xoops\Module\Admin();
$admin_page->renderNavigation('plugins.php');

switch ($op) {
    case 'update':
        if ('POST' !== Request::getMethod() || !$xoops->security()->check()) {
            $xoops->tpl()->assign(
                'errorMsg',
                $xoops->alert('error', $xoops->security()->getErrors() ?: XoopsLocale::E_NO_ACCESS_PERMISSION)
            );
        } else {
            $orders = Request::getArray('order', [], 'POST');
            foreach ($orders as $id => $order) {
                $handler->updateOrder((int) $id, (int) $order);
            }
            $statuses = Request::getArray('status', [], 'POST');
            foreach ($statuses as $id => $status) {
                $handler->updateStatus((int) $id, (int) $status);
            }
            $xoops->tpl()->assign('infoMsg', $xoops->alert('success', XoopsLocale::S_DATA_UPDATED));
        }
        //No break;
    case 'list':
    default:
        $admin_page->addTips(t::TIPS_PLUGINS);
        $admin_page->renderTips();

        if ($listener) {
            $objects = $handler->getByListener($listener);
        } else if ($caller) {
            $objects = $handler->getByCaller($caller);
        } else {
            $objects = $handler->getThemAll();
        }

        //In the impossible case no plugins were found, we alert the user
        if (!$objects) {
            $xoops->tpl()->assign('errorMsg', $xoops->alert('info', t::NO_PLUGINS_FOUND));
            break;
        }

        $plugins = array();
        foreach ($objects as $object) {
            /* @var $object XoopsObject */
            $plugin = $object->toArray();
            $plugin['plugin_caller_name'] = $xoops->getModuleByDirname($plugin['plugin_caller'])->getVar('name');
            $plugin['plugin_listener_name'] = $xoops->getModuleByDirname($plugin['plugin_listener'])->getVar('name');

            //Add order field
            $order = new \Xoops\Form\Text('', 'order[' . $plugin['plugin_id'] . ']', 2, 2, $plugin['plugin_order']);
            $order->set('style', 'width:3em');
            $plugin['plugin_order_field'] = $order->render();
            //Add status field
            $status = new \Xoops\Form\RadioYesNo('', 'status[' . $plugin['plugin_id'] . ']', $plugin['plugin_status'], false);
            $plugin['plugin_status_field'] = $status->render();
            $plugins[] = $plugin;
        }
        $xoops->tpl()->assign('pluginsCount', count($plugins));
        $xoops->tpl()->assign('plugins', $plugins);

        //Listeners form
        $objects = $handler->getListeners();
        $form = new \Xoops\Form\ThemeForm('', 'listenersForm', 'plugins.php', 'post');
        $form->addElement(new \Xoops\Form\Hidden('op', 'list'));
        $select = new \Xoops\Form\Select('', 'listener', $listener);
        $select->set("onchange", 'submit()');
        $select->addOption('', XoopsLocale::ALL_TYPES);
        foreach ($objects as $l => $name) {
            $select->addOption($l, $name);
        }
        $form->addElement($select);
        $xoops->tpl()->assign('listenersForm', $form->render());

        //Callers form
        $objects = $handler->getCallers();
        $form = new \Xoops\Form\ThemeForm('', 'callersForm', 'plugins.php', 'post');
        $form->addElement(new \Xoops\Form\Hidden('op', 'list'));
        $select = new \Xoops\Form\Select('', 'caller', $caller);
        $select->set("onchange", 'submit()');
        $select->addOption('', XoopsLocale::ALL_TYPES);
        foreach ($objects as $c => $name) {
            $select->addOption($c, $name);
        }
        $form->addElement($select);
        $xoops->tpl()->assign('callersForm', $form->render());

        //Submit button
        $submitButton = new \Xoops\Form\Button(XoopsLocale::A_SUBMIT, 'submit', XoopsLocale::A_SUBMIT, 'submit');
        $xoops->tpl()->assign('submitButton', $submitButton->render());

        //Hidden fields
        $hiddenFields = '';
        $hidden = new \Xoops\Form\Hidden('op', 'update');
        $hiddenFields .= $hidden->render();
        $hidden = new \Xoops\Form\Hidden('listener', $listener);
        $hiddenFields .= $hidden->render();
        $hidden = new \Xoops\Form\Hidden('caller', $caller);
        $hiddenFields .= $hidden->render();
        $hiddenFields .= $xoops->security()->getTokenHTML();
        $xoops->tpl()->assign('hiddenFields', $hiddenFields);
        break;
}
$xoops->footer();