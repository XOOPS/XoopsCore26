<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

/**
 * Protector
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         protector
 * @author          trabis <lusopoemas@gmail.com>
 * @version         $Id$
 */

// GIJOE's Ticket Class (based on Marijuana's Oreteki XOOPS)
// nobunobu's suggestions are applied

if (!class_exists('XoopsGTicket')) {

    class XoopsGTicket
    {

        var $_errors = array();
        var $_latest_token = '';
        var $messages = array();

        function XoopsGTicket()
        {
            $xoops = Xoops::getInstance();
            $language = $xoops->getConfig('language');

            // language file
            if ($language && !strstr($language, '/')) {
                if (XoopsLoad::fileExists(dirname(__DIR__) . '/language/' . $language . '/gticket_messages.phtml')) {
                    include dirname(__DIR__) . '/language/' . $language . '/gticket_messages.phtml';
                }
            }

            // default messages
            if (empty($this->messages)) {
                $this->messages = array(
                    'err_general' => 'GTicket Error', 'err_nostubs' => 'No stubs found',
                    'err_noticket' => 'No ticket found', 'err_nopair' => 'No valid ticket-stub pair found',
                    'err_timeout' => 'Time out', 'err_areaorref' => 'Invalid area or referer',
                    'fmt_prompt4repost' => 'error(s) found:<br /><span style="background-color:red;font-weight:bold;color:white;">%s</span><br />Confirm it.<br />And do you want to post again?',
                    'btn_repost' => 'repost',
                );
            }
        }

        // render form as plain html
        function getTicketHtml($salt = '', $timeout = 1800, $area = '')
        {
            return '<input type="hidden" name="XOOPS_G_TICKET" value="' . $this->issue($salt, $timeout, $area) . '" />';
        }

        // returns an object of XoopsFormHidden including the ticket
        function getTicketXoopsForm($salt = '', $timeout = 1800, $area = '')
        {
            return new Xoops\Form\Hidden('XOOPS_G_TICKET', $this->issue($salt, $timeout, $area));
        }

        // add a ticket as Hidden Element into XoopsForm
        function addTicketXoopsFormElement(&$form, $salt = '', $timeout = 1800, $area = '')
        {
            $form->addElement(new Xoops\Form\Hidden('XOOPS_G_TICKET', $this->issue($salt, $timeout, $area)));
        }

        // returns an array for xoops_confirm() ;
        function getTicketArray($salt = '', $timeout = 1800, $area = '')
        {
            return array('XOOPS_G_TICKET' => $this->issue($salt, $timeout, $area));
        }

        // return GET parameter string.
        function getTicketParamString($salt = '', $noamp = false, $timeout = 1800, $area = '')
        {
            return ($noamp ? '' : '&amp;') . 'XOOPS_G_TICKET=' . $this->issue($salt, $timeout, $area);
        }

        // issue a ticket
        function issue($salt = '', $timeout = 1800, $area = '')
        {
            $xoops = Xoops::getInstance();

            // create a token
            list($usec, $sec) = explode(" ", microtime());
            $appendix_salt = empty($_SERVER['PATH']) ? \XoopsBaseConfig::get('db-name') : $_SERVER['PATH'];
            $token = crypt($salt . $usec . $appendix_salt . $sec);
            $this->_latest_token = $token;

            if (empty($_SESSION['XOOPS_G_STUBS'])) {
                $_SESSION['XOOPS_G_STUBS'] = array();
            }

            // limit max stubs 10
            if (sizeof($_SESSION['XOOPS_G_STUBS']) > 10) {
                $_SESSION['XOOPS_G_STUBS'] = array_slice($_SESSION['XOOPS_G_STUBS'], -10);
            }

            // record referer if browser send it
            $referer = empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['REQUEST_URI'];

            // area as module's dirname
            if (!$area && $xoops->isModule()) {
                $area = $xoops->module->getVar('dirname');
            }

            // store stub
            $_SESSION['XOOPS_G_STUBS'][] = array(
                'expire' => time() + $timeout, 'referer' => $referer, 'area' => $area, 'token' => $token
            );

            // paid md5ed token as a ticket
            return md5($token . \XoopsBaseConfig::get('db-prefix'));
        }

        // check a ticket
        function check($post = true, $area = '', $allow_repost = true)
        {
            $xoops = Xoops::getInstance();
            $this->_errors = array();

            // CHECK: stubs are not stored in session
            if (!is_array(@$_SESSION['XOOPS_G_STUBS'])) {
                $this->_errors[] = $this->messages['err_nostubs'];
                $_SESSION['XOOPS_G_STUBS'] = array();
            }

            // get key&val of the ticket from a user's query
            $ticket = $post ? @$_POST['XOOPS_G_TICKET'] : @$_GET['XOOPS_G_TICKET'];

            // CHECK: no tickets found
            if (empty($ticket)) {
                $this->_errors[] = $this->messages['err_noticket'];
            }

            // gargage collection & find a right stub
            $stubs_tmp = $_SESSION['XOOPS_G_STUBS'];
            $_SESSION['XOOPS_G_STUBS'] = array();
            foreach ($stubs_tmp as $stub) {
                // default lifetime 30min
                if ($stub['expire'] >= time()) {
                    if (md5($stub['token'] . \XoopsBaseConfig::get('db-prefix')) === $ticket) {
                        $found_stub = $stub;
                    } else {
                        // store the other valid stubs into session
                        $_SESSION['XOOPS_G_STUBS'][] = $stub;
                    }
                } else {
                    if (md5($stub['token'] . \XoopsBaseConfig::get('db-prefix')) === $ticket) {
                        // not CSRF but Time-Out
                        $timeout_flag = true;
                    }
                }
            }

            // CHECK: the right stub found or not
            if (empty($found_stub)) {
                if (empty($timeout_flag)) {
                    $this->_errors[] = $this->messages['err_nopair'];
                } else {
                    $this->_errors[] = $this->messages['err_timeout'];
                }
            } else {

                // set area if necessary
                // area as module's dirname
                if (!$area && $xoops->isModule()) {
                    $area = $xoops->module->getVar('dirname');
                }

                // check area or referer
                if (@$found_stub['area'] == $area) {
                    $area_check = true;
                }
                if (!empty($found_stub['referer']) && strstr(@$_SERVER['HTTP_REFERER'], $found_stub['referer'])) {
                    $referer_check = true;
                }

                if (empty($area_check) && empty($referer_check)) { // loose
                    $this->_errors[] = $this->messages['err_areaorref'];
                }
            }

            if (!empty($this->_errors)) {
                if ($allow_repost) {
                    // repost form
                    $this->draw_repost_form($area);
                    exit;
                } else {
                    // failed
                    $this->clear();
                    return false;
                }
            } else {
                // all green
                return true;
            }
        }

        // draw form for repost
        function draw_repost_form($area = '')
        {
            // Notify which file is broken
            if (headers_sent()) {
                restore_error_handler();
                set_error_handler(array(&$this, 'errorHandler4FindOutput'));
                header('Dummy: for warning');
                restore_error_handler();
                exit;
            }

            error_reporting(0);
            while (ob_get_level()) {
                ob_end_clean();
            }

            $table = '<table>';
            $form = '<form action="?' . htmlspecialchars(@$_SERVER['QUERY_STRING'], ENT_QUOTES) . '" method="post" >';
            foreach ($_POST as $key => $val) {
                if ($key === 'XOOPS_G_TICKET') {
                    continue;
                }
                if (is_array($val)) {
                    list($tmp_table, $tmp_form) = $this->extract_post_recursive(htmlspecialchars($key, ENT_QUOTES), $val);
                    $table .= $tmp_table;
                    $form .= $tmp_form;
                } else {
                    $table .= '<tr><th>' . htmlspecialchars($key, ENT_QUOTES) . '</th><td>' . htmlspecialchars($val, ENT_QUOTES) . '</td></tr>' . "\n";
                    $form .= '<input type="hidden" name="' . htmlspecialchars($key, ENT_QUOTES) . '" value="' . htmlspecialchars($val, ENT_QUOTES) . '" />' . "\n";
                }
            }
            $table .= '</table>';
            $form .= $this->getTicketHtml(__LINE__, 300, $area) . '<input type="submit" value="' . $this->messages['btn_repost'] . '" /></form>';

            echo '<html><head><title>' . $this->messages['err_general'] . '</title><style>table,td,th {border:solid black 1px; border-collapse:collapse;}</style></head><body>' . sprintf($this->messages['fmt_prompt4repost'], $this->getErrors()) . $table . $form . '</body></html>';
        }

        /**
         * @param string $key_name
         */
        function extract_post_recursive($key_name, $tmp_array)
        {
            $table = '';
            $form = '';
            foreach ($tmp_array as $key => $val) {
                if (is_array($val)) {
                    list($tmp_table, $tmp_form) = $this->extract_post_recursive($key_name . '[' . htmlspecialchars($key, ENT_QUOTES) . ']', $val);
                    $table .= $tmp_table;
                    $form .= $tmp_form;
                } else {
                    $table .= '<tr><th>' . $key_name . '[' . htmlspecialchars($key, ENT_QUOTES) . ']</th><td>' . htmlspecialchars($val, ENT_QUOTES) . '</td></tr>' . "\n";
                    $form .= '<input type="hidden" name="' . $key_name . '[' . htmlspecialchars($key, ENT_QUOTES) . ']" value="' . htmlspecialchars($val, ENT_QUOTES) . '" />' . "\n";
                }
            }
            return array($table, $form);
        }


        // clear all stubs
        function clear()
        {
            $_SESSION['XOOPS_G_STUBS'] = array();
        }


        // Ticket Using
        function using()
        {
            if (!empty($_SESSION['XOOPS_G_STUBS'])) {
                return true;
            } else {
                return false;
            }
        }


        // return errors
        function getErrors($ashtml = true)
        {
            if ($ashtml) {
                $ret = '';
                foreach ($this->_errors as $msg) {
                    $ret .= "$msg<br />\n";
                }
            } else {
                $ret = $this->_errors;
            }
            return $ret;
        }

        function errorHandler4FindOutput($errNo, $errStr, $errFile, $errLine)
        {
            if (preg_match('?' . preg_quote(\XoopsBaseConfig::get('root-path')) . '([^:]+)\:(\d+)?', $errStr, $regs)) {
                echo "Irregular output! check the file " . htmlspecialchars($regs[1]) . " line " . htmlspecialchars($regs[2]);
            } else {
                echo "Irregular output! check language files etc.";
            }
            return;
        }
        // end of class
    }

    // create a instance in global scope
    $GLOBALS['xoopsGTicket'] = new XoopsGTicket();

}

if (!function_exists('admin_refcheck')) {

    //Admin Referer Check By Marijuana(Rev.011)
    function admin_refcheck($chkref = "")
    {
        if (empty($_SERVER['HTTP_REFERER'])) {
            return true;
        } else {
            $ref = $_SERVER['HTTP_REFERER'];
        }
        $cr = \XoopsBaseConfig::get('url');
        if ($chkref != "") {
            $cr .= $chkref;
        }
        if (strpos($ref, $cr) !== 0) {
            return false;
        }
        return true;
    }

}
