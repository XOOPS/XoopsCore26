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

class XoopsInstallWizard
{
    /**
     * @var string
     */
    public $language = 'en_US';

    /**
     * @var array
     */
    public $pages = array();

    /**
     * @var string
     */
    public $currentPage = 'langselect';

    /**
     * @var int
     */
    public $pageIndex = 0;

    /**
     * @var array
     */
    public $configs = array();

    /**
     * @var array of Xoops\Form\Form
     */
    public $form;

    public function xoInit()
    {
        if (@empty($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
        }

        // Load the main language file
        $this->initLanguage(!empty($_COOKIE['xo_install_lang']) ? $_COOKIE['xo_install_lang'] : 'en_US');
        // Setup pages
        $pages = array();
        include_once dirname(__DIR__) . '/include/page.php';
        $this->pages = $pages;

        // Load default configs
        $configs = array();
        include_once dirname(__DIR__) . '/include/config.php';
        $this->configs = $configs;

        if (!$this->checkAccess()) {
            return false;
        }

        $pagename = preg_replace('~(page_)(.*)~', '$2', basename($_SERVER['PHP_SELF'], ".php"));
        $this->setPage($pagename);

        // Prevent client caching
        header("Cache-Control: no-store, no-cache, must-revalidate", false);
        header("Pragma: no-cache");
        return true;
    }

    /**
     * @return bool
     */
    public function checkAccess()
    {
        global $xoopsOption;
        if (INSTALL_USER != '' && INSTALL_PASSWORD != '') {
            if (!isset($_SERVER['PHP_AUTH_USER'])) {
                header('WWW-Authenticate: Basic realm="XOOPS Installer"');
                header('HTTP/1.0 401 Unauthorized');
                echo 'You can not access this XOOPS installer.';
                return false;
            }
            if (INSTALL_USER != '' && $_SERVER['PHP_AUTH_USER'] != INSTALL_USER) {
                header('HTTP/1.0 401 Unauthorized');
                echo 'You can not access this XOOPS installer.';
                return false;
            }
            if (INSTALL_PASSWORD != $_SERVER['PHP_AUTH_PW']) {
                header('HTTP/1.0 401 Unauthorized');
                echo 'You can not access this XOOPS installer.';
                return false;
            }
        }

        if (!isset($xoopsOption['checkadmin']) || !$xoopsOption['checkadmin']) {
            return true;
        }

        $xoops = Xoops::getInstance();
        if (!$xoops->isUser() && !empty($_COOKIE["xo_install_user"])) {
            install_acceptUser($_COOKIE["xo_install_user"]);
        }

        if (!$xoops->isUser()) {
            $xoops->redirect($xoops->url('user.php'));
        }
        if (!$xoops->isAdmin()) {
            return false;
        }
        return true;
    }

    /**
     * @param string $file
     *
     * @return void
     */
    public function loadLangFile($file)
    {
        if (file_exists($file = XOOPS_INSTALL_PATH . "/locale/{$this->language}/{$file}.php")) {
            include_once $file;
        } else {
            $file = XOOPS_INSTALL_PATH . "/locale/en_US/{$file}.php";
            include_once $file;
        }
    }

    /**
     * @param string $language
     *
     * @return void
     */
    public function initLanguage($language)
    {
        $language = preg_replace("/[^a-z0-9_\-]/i", "", $language);
        if (!file_exists(XOOPS_INSTALL_PATH . "/locale/{$language}/install.php")) {
            $language = 'en_US';
        }
        $this->language = $language;
        $this->loadLangFile('install');
    }

    /**
     * @param string $page
     *
     * @return bool|int
     */
    public function setPage($page)
    {
        $pages = array_keys($this->pages);
        if ((int)$page && $page >= 0 && $page < count($pages)) {
            $this->pageIndex = $page;
            $this->currentPage = $pages[$page];
        } else {
            if (isset($this->pages[$page])) {
                $this->currentPage = $page;
                $this->pageIndex = array_search($this->currentPage, $pages);
            } else {
                return false;
            }
        }

        if ($this->pageIndex > 0 && !isset($_COOKIE['xo_install_lang'])) {
            header('Location: index.php');
        }

        return $this->pageIndex;
    }

    /**
     * @return string
     */
    public function baseLocation()
    {
        $proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $base = substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/'));
        return $proto . '://' . $host . $base;
    }

    /**
     * @param $page
     *
     * @return string
     */
    public function pageURI($page)
    {
        $pages = array_keys($this->pages);
        $pageIndex = $this->pageIndex;
        if (!(int)$page[0]) {
            if ($page[0] == '+') {
                $pageIndex += substr($page, 1);
            } else {
                if ($page[0] == '-') {
                    $pageIndex -= substr($page, 1);
                } else {
                    $pageIndex = (int)array_search($page, $pages);
                }
            }
        }
        if (!isset($pages[$pageIndex])) {
            if (defined("XOOPS_URL")) {
                return XOOPS_URL . '/';
            } else {
                return $this->baseLocation();
            }
        }
        $page = $pages[$pageIndex];
        return $this->baseLocation() . "/page_{$page}.php";
    }

    /**
     * @param        string $page
     * @param int    $status
     * @param string $message
     *
     * @return void
     */
    public function redirectToPage($page, $status = 303, $message = 'See other')
    {
        $location = $this->pageURI($page);
        $proto = !@empty($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
        header("{$proto} {$status} {$message}");
        //header( "Status: $status $message" );
        header("Location: {$location}");
    }

    /**
     * @return string
     */
    public function CreateForm()
    {
        $hidden = '';
        $ret = '';

        /* @var Xoops\Form\Form $form */
        foreach ($this->form as $form) {
            $ret .= "<fieldset><legend>" . $form->getTitle() . "</legend>\n";

            /* @var Xoops\Form\Element $ele */
            foreach ($form->getElements() as $ele) {
                //todo, ain't this always a object on 2.6?
                if ($ele instanceof Xoops\Form\Element) {
                    if (!$ele->isHidden()) {
                        if (($caption = $ele->getCaption()) != '') {
                            $ret .= "<label class='xolabel' for='" . $ele->getName() . "'>" . $caption . "</label>";
                            if (($desc = $ele->getDescription()) != '') {
                                $ret .= "<div class='xoform-help'>";
                                $ret .= $desc;
                                $ret .= "</div>";
                            }
                        }
                        $ret .= $ele->render() . "\n";
                    } else {
                        $hidden .= $ele->render() . "\n";
                    }
                }
            }
            $ret .= "</fieldset>\n" . $hidden . "\n" . $form->renderValidationJS(true);
        }
        return $ret;
    }

    function cleanCache($cacheFolder) {
        $cache = array(1,2,3);
        if (!empty($cache)) {
            for ($i = 0; $i < count($cache); ++$i) {
                switch ($cache[$i]) {
                    case 1:
                        $files = glob($cacheFolder. '/caches/smarty_cache/*.*');
                        foreach ($files as $filename) {
                            if (basename(strtolower($filename)) !== 'index.html') {
                                unlink($filename);
                            }
                        }
                        break;

                    case 2:
                        $files = glob($cacheFolder . '/caches/smarty_compile/*.*');
                        foreach ($files as $filename) {
                            if (basename(strtolower($filename)) !== 'index.html') {
                                unlink($filename);
                            }
                        }
                        break;

                    case 3:
                        $files = glob($cacheFolder . '/caches/xoops_cache/*.*');
                        foreach ($files as $filename) {
                            if (basename(strtolower($filename)) !== 'index.html') {
                                unlink($filename);
                            }
                        }
                        break;
                }
            }
            return true;
        } else {
            return false;
        }
    }
}
