<?php
/**
 * Xcaptcha extension module
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         xcaptcha
 * @since           2.6.0
 * @author          Laurent JEN (Aka DuGris)
 * @version         $Id$
 */

require_once dirname(dirname(dirname(__DIR__))) . '/include/cp_header.php';

$op = \Xmf\Request::getCmd('op', '');
$type = \Xmf\Request::getCmd('type', 'config');

XoopsLoad::load('system', 'system');

$xoops = Xoops::getInstance();
$system = System::getInstance();

include_once dirname(__DIR__) . '/class/xcaptcha.php';
$xcaptcha_handler = new Xcaptcha();
