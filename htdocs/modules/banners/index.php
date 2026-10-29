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

/**
 * banners module
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         banners
 * @since           2.6.0
 * @author          Mage Grégory (AKA Mage)
 * @version         $Id: $
 */

include dirname(dirname(__DIR__)) . '/mainfile.php';

$xoops = Xoops::getInstance();
$helper = Banners::getInstance();
// Get banners handler
$banner_Handler = $helper->getHandlerBanner();
$client_Handler = $helper->getHandlerBannerclient();
// Get member handler
$member_handler = $xoops->getHandlerMember();
// Parameters
$nb_banners = $helper->getConfig('banners_pager');
// Get Action type
$op = Request::getCmd('op', 'list');

switch ($op) {

    case 'list':
    default:
        $access = false;
        $admin = false;
        if ($xoops->isUser()) {
            $uid = $xoops->user->getVar('uid');
        } else {
            $uid = 0;
        }
        if ($uid == 0) {
            $access = false;
        } else {
            // bannerclient_uid 0 means "no linked account", never the anonymous visitor
            $criteria = new CriteriaCompo();
            $criteria->add(new Criteria('bannerclient_uid', $uid));
            $client_count = $client_Handler->getCount($criteria);
            if ($client_count != 0) {
                $access = true;
            }
        }
        if ($xoops->userIsAdmin) {
            $access = true;
            $admin = true;
        }
        if ($access == false) {
            $xoops->redirect(\XoopsBaseConfig::get('url'), 2, XoopsLocale::E_NO_ACCESS_PERMISSION);
        }
        // Get start pager
        $start = Request::getInt('start', 0);
        $startF = Request::getInt('startF', 0);
        // Call header
        $xoops->header('module:banners/banners_client.tpl');
        // Define Stylesheet
        $xoops->theme()->addBaseStylesheetAssets('modules/system/css/admin.css');
        $xoops->theme()->addBaseStylesheetAssets('@jqueryuicss');
        // Define scripts
        $xoops->theme()->addBaseScriptAssets(array('@jquery', '@jqueryui'));
        $xoops->theme()->addScript('modules/system/js/admin.js');

        // Display banner
        if ($admin == false) {
            $client_arr = $client_Handler->getAll($criteria);
            foreach (array_keys($client_arr) as $i) {
                $cid[] = $client_arr[$i]->getVar("bannerclient_cid");
            }
        }

        // Display banner
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('banner_status', 0, '!='));
        if ($admin == false) {
            $criteria->add(new Criteria('banner_cid', '(' . implode(',', $cid) . ')', 'IN'));
        }
        $criteria->setSort("banner_cid");
        $criteria->setOrder("ASC");
        $criteria->setStart($start);
        $criteria->setLimit($nb_banners);

        $banner_count = $banner_Handler->getCount($criteria);
        $banner_arr = $banner_Handler->getAll($criteria);

        $xoops->tpl()->assign('banner_count', $banner_count);

        if ($banner_count > 0) {
            foreach (array_keys($banner_arr) as $i) {
                $imptotal = $banner_arr[$i]->getVar("banner_imptotal");
                $impmade = $banner_arr[$i]->getVar("banner_impmade");
                $imageurl = $banner_arr[$i]->getVar("banner_imageurl");
                $clicks = $banner_arr[$i]->getVar("banner_clicks");
                $htmlbanner = $banner_arr[$i]->getVar("banner_htmlbanner");
                $htmlcode = $banner_arr[$i]->getVar("banner_htmlcode");
                $client = $client_Handler->get($banner_arr[$i]->getVar("banner_cid"));
                if (is_object($client)) {
                    $client_name = $client->getVar("bannerclient_name");
                    $client_uid = $client->getVar("bannerclient_uid");
                } else {
                    $client_name = '';
                    $client_uid = 0;
                }
                if ($impmade == 0) {
                    $percent = 0;
                } else {
                    $percent = substr(100 * $clicks / $impmade, 0, 5);
                }
                if ($imptotal == 0) {
                    $left = "" . _AM_BANNERS_BANNERS_UNLIMIT . "";
                } else {
                    $left = $imptotal - $impmade;
                }
                $img = '';
                if ($htmlbanner) {
                    $img .= html_entity_decode($htmlcode);
                } else {
                    $img = '';
                    if (stristr($imageurl, '.swf')) {
                        $img .= '<object type="application/x-shockwave-flash" width="468" height="60" data="' . $imageurl . '" style="z-index:100;">' . '<param name="movie" value="' . $imageurl . '" />' . '<param name="wmode" value="opaque" />' . '</object>';
                    } else {
                        $img .= '<img src="' . $imageurl . '" alt="" />';
                    }
                }

                $banner['bid'] = $banner_arr[$i]->getVar("banner_bid");
                $banner['impmade'] = $impmade;
                $banner['clicks'] = $clicks;
                $banner['left'] = $left;
                $banner['percent'] = $percent;
                $banner['imageurl'] = $img;
                $banner['name'] = $client_name;
                $banner['uid'] = $client_uid;
                if ($banner_arr[$i]->getVar("banner_clickurl") == '') {
                    $banner['clickurl'] = '#';
                } else {
                    $banner['clickurl'] = $banner_arr[$i]->getVar("banner_clickurl");
                }
                $xoops->tpl()->appendByRef('banner', $banner);
                $xoops->tpl()->appendByRef('popup_banner', $banner);
                unset($banner);
            }
        } else {
            $xoops->tpl()->assign('error_msg', $xoops->alert('error', _MD_BANNERS_INDEX_NOBANNER));
        }
        // Display Page Navigation
        if ($banner_count > $nb_banners) {
            $nav = new XoopsPageNav($banner_count, $nb_banners, $start, 'start', 'startF=' . $startF);
            $xoops->tpl()->assign('nav_menu_banner', $nav->renderNav(4));
        }
        // Display Finished Banners
        // Criteria
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('banner_status', 0));
        if ($admin == false) {
            $criteria->add(new Criteria('banner_cid', '(' . implode(',', $cid) . ')', 'IN'));
        }
        $criteria->setSort("banner_datestart");
        $criteria->setOrder("DESC");
        $criteria->setStart($startF);
        $criteria->setLimit($nb_banners);

        $banner_finish_count = $banner_Handler->getCount($criteria);
        $banner_finish_arr = $banner_Handler->getAll($criteria);

        $xoops->tpl()->assign('banner_finish_count', $banner_finish_count);

        if ($banner_finish_count > 0) {
            foreach (array_keys($banner_finish_arr) as $i) {
                $bid = $banner_finish_arr[$i]->getVar("banner_bid");
                $imageurl = $banner_finish_arr[$i]->getVar("banner_imageurl");
                $htmlbanner = $banner_finish_arr[$i]->getVar("banner_htmlbanner");
                $htmlcode = $banner_finish_arr[$i]->getVar("banner_htmlcode");
                $impressions = $banner_finish_arr[$i]->getVar("banner_impmade");
                $clicks = $banner_finish_arr[$i]->getVar("banner_clicks");
                $client = $client_Handler->get($banner_finish_arr[$i]->getVar("banner_cid"));
                if (is_object($client)) {
                    $client_name = $client->getVar("bannerclient_name");
                    $client_uid = $client->getVar("bannerclient_uid");
                } else {
                    $client_name = '';
                    $client_uid = 0;
                }
                if ($impressions != 0) {
                    $percent = substr(100 * $clicks / $impressions, 0, 5);
                } else {
                    $percent = 0;
                }
                $img = '';
                if ($htmlbanner) {
                    $img .= html_entity_decode($htmlcode);
                } else {
                    $img = '<div id="xo-bannerfix">';
                    if (stristr($imageurl, '.swf')) {
                        $img .= '<object type="application/x-shockwave-flash" width="468" height="60" data="' . $imageurl . '" style="z-index:100;">' . '<param name="movie" value="' . $imageurl . '" />' . '<param name="wmode" value="opaque" />' . '</object>';
                    } else {
                        $img .= '<img src="' . $imageurl . '" alt="" />';
                    }

                    $img .= '</div>';
                }
                $banner_finish['bid'] = $bid;
                $banner_finish['impressions'] = $impressions;
                $banner_finish['clicks'] = $clicks;
                $banner_finish['percent'] = $percent;
                $banner_finish['imageurl'] = $img;
                $banner_finish['datestart'] = XoopsLocale::formatTimestamp($banner_finish_arr[$i]->getVar("banner_datestart"), "s");
                $banner_finish['dateend'] = XoopsLocale::formatTimestamp($banner_finish_arr[$i]->getVar("banner_dateend"), "s");
                $banner_finish['clickurl'] = $banner_finish_arr[$i]->getVar("banner_clickurl");
                $banner_finish['name'] = $client_name;
                $banner_finish['uid'] = $client_uid;
                $xoops->tpl()->appendByRef('banner_finish', $banner_finish);
                $xoops->tpl()->appendByRef('popup_banner_finish', $banner_finish);
                unset($banner_finish);
            }
        }
        // Display Page Navigation
        if ($banner_finish_count > $nb_banners) {
            $nav = new XoopsPageNav($banner_finish_count, $nb_banners, $startF, 'startF', 'start=' . $start);
            $xoops->tpl()->assign('nav_menu_bannerF', $nav->renderNav(4));
        }
        $xoops->footer();
        break;

    case 'edit':
        $access = false;
        $admin = false;
        if ($xoops->isUser()) {
            $uid = $xoops->user->getVar('uid');
        } else {
            $uid = 0;
        }
        if ($uid == 0) {
            $access = false;
        } else {
            // bannerclient_uid 0 means "no linked account", never the anonymous visitor
            $criteria = new CriteriaCompo();
            $criteria->add(new Criteria('bannerclient_uid', $uid));
            $client_count = $client_Handler->getCount($criteria);
            if ($client_count != 0) {
                $access = true;
            }
        }
        if ($xoops->userIsAdmin) {
            $access = true;
            $admin = true;
        }
        if ($access == false) {
            $xoops->redirect(\XoopsBaseConfig::get('url'), 2, XoopsLocale::E_NO_ACCESS_PERMISSION);
        }
        $bid = Request::getInt('bid', 0);
        if ($bid > 0) {
            // Call header
            $xoops->header('module:banners/banners_client.tpl');
            $obj = $banner_Handler->get($bid);
            $form = new Xoops\Form\ThemeForm(_AM_BANNERS_CLIENTS_EDIT, 'form', 'index.php', 'post', true);
            $form->addElement(new Xoops\Form\Text(_AM_BANNERS_BANNERS_CLICKURL, 'clickurl', 80, 255, $obj->getVar('banner_clickurl')), false);
            $form->addElement(new Xoops\Form\Hidden('op', 'save'));
            $form->addElement(new Xoops\Form\Hidden('bid', $obj->getVar('banner_bid')));
            $form->addElement(new Xoops\Form\Button('', 'submit', XoopsLocale::A_SUBMIT, 'submit'));
            $xoops->tpl()->assign('form', $form->render());
            $xoops->footer();
        } else {
            $xoops->redirect(\XoopsBaseConfig::get('url'), 1, _MD_BANNERS_INDEX_DBERROR);
        }
        break;

    case 'save':
        $access = true;
        $admin = false;
        if ($xoops->isUser()) {
            $uid = $xoops->user->getVar('uid');
        } else {
            $uid = 0;
        }
        if ($uid == 0) {
            $access = false;
        }
        if ($xoops->userIsAdmin) {
            $access = true;
            $admin = true;
        }
        if ($access == false) {
            $xoops->redirect(\XoopsBaseConfig::get('url'), 2, XoopsLocale::E_NO_ACCESS_PERMISSION);
        }
        if (!$xoops->security()->check()) {
            $xoops->redirect("index.php", 3, implode(",", $xoops->security()->getErrors()));
        }
        $bid = $Request::getInt('bid', 0);
        if ($bid > 0) {
            $obj = $banner_Handler->get($bid);
            $criteria = new CriteriaCompo();
            $criteria->add(new Criteria('bannerclient_cid', $obj->getVar("banner_cid")));
            $client_arr = $client_Handler->getAll($criteria);
            foreach (array_keys($client_arr) as $i) {
                if ($admin == false) {
                    if ($client_arr[$i]->getVar("uid") != $uid) {
                        $xoops->redirect(\XoopsBaseConfig::get('url'), 2, XoopsLocale::E_NO_ACCESS_PERMISSION);
                    }
                }
            }
            $obj->setVar("banner_clickurl", Request::getString('clickurl', ''));
            if ($banner_Handler->insert($obj)) {
                $xoops->redirect("index.php", 2, _AM_BANNERS_DBUPDATED);
            }
            echo $xoops->alert('error', $obj->getHtmlErrors());
        } else {
            $xoops->redirect(\XoopsBaseConfig::get('url'), 1, _MD_BANNERS_INDEX_NO_ID);
        }
        break;

    case 'EmailStats':
        $access = true;
        $admin = false;
        if ($xoops->isUser()) {
            $uid = $xoops->user->getVar('uid');
        } else {
            $uid = 0;
        }
        if ($uid == 0) {
            $access = false;
        }
        if ($xoops->userIsAdmin) {
            $access = true;
            $admin = true;
        }
        if ($access == false) {
            $xoops->redirect(\XoopsBaseConfig::get('url'), 2, XoopsLocale::E_NO_ACCESS_PERMISSION);
        }
        $bid = Request::getInt('bid', 0);
        if ($bid > 0) {
            $banner = $banner_Handler->get($bid);
            $criteria = new CriteriaCompo();
            $criteria->add(new Criteria('bannerclient_cid', $banner->getVar("banner_cid")));
            $client_arr = $client_Handler->getAll($criteria);
            foreach (array_keys($client_arr) as $i) {
                if ($admin == false) {
                    if ($client_arr[$i]->getVar("bannerclient_uid") != $uid) {
                        $xoops->redirect(\XoopsBaseConfig::get('url'), 2, XoopsLocale::E_NO_ACCESS_PERMISSION);
                    }
                }
                $client_uid = $client_arr[$i]->getVar("bannerclient_uid");
                $client_name = $client_arr[$i]->getVar("bannerclient_name");
            }
            $user = $member_handler->getUser($client_arr[$i]->getVar("bannerclient_uid"));
            $email = $user->getVar("email", 'n');
            if ($email != '') {
                if ($banner->getVar('banner_impmade') == 0) {
                    $percent = 0;
                } else {
                    $percent = substr(100 * $banner->getVar('banner_clicks') / $banner->getVar('banner_impmade'), 0, 5);
                }
                if ($banner->getVar('banner_imptotal') == 0) {
                    $left = _AM_BANNERS_BANNERS_UNLIMIT;
                    $banner->setVar('banner_imptotal', _AM_BANNERS_BANNERS_UNLIMIT);
                } else {
                    $left = $banner->getVar('banner_imptotal') - $banner->getVar('banner_impmade');
                }
                $date = date("F jS Y, h:iA.");
                $subject = sprintf(_MD_BANNERS_INDEX_MAIL_SUBJECT, $xoops->getConfig('sitename'));
                $message = sprintf(_MD_BANNERS_INDEX_MAIL_MESSAGE, $xoops->getConfig('sitename'), $client_name, $bid, $banner->getVar('banner_imageurl'), $banner->getVar('banner_clickurl'), $banner->getVar('banner_imptotal'), $banner->getVar('banner_impmade'), $left, $banner->getVar('banner_clicks'), $percent, $date);
                $xoopsMailer = $xoops->getMailer();
                $xoopsMailer->useMail();
                $xoopsMailer->setToEmails($email);
                $xoopsMailer->setFromEmail($xoops->getConfig('adminmail'));
                $xoopsMailer->setFromName($xoops->getConfig('sitename'));
                $xoopsMailer->setSubject($subject);
                $xoopsMailer->setBody($message);
                if (!$xoopsMailer->send()) {
                    $xoops->redirect("index.php", 2, sprintf(XoopsLocale::EF_EMAIL_NOT_SENT_TO, $email));
                }
                $xoops->redirect("index.php", 2, _MD_BANNERS_INDEX_MAIL_OK);
            } else {
                $xoops->redirect("index.php", 2, _MD_BANNERS_INDEX_NOMAIL);
            }
        } else {
            $xoops->redirect(\XoopsBaseConfig::get('url'), 1, _MD_BANNERS_INDEX_NO_ID);
        }
        break;

    case 'click':
        $bid = Request::getInt('bid', 0);
        if ($bid > 0) {
            $banner = $banner_Handler->get($bid);
            if ($banner) {
                if ($xoops->security()->checkReferer()) {
                    $banner->setVar('banner_clicks', $banner->getVar('banner_clicks') + 1);
                    $banner_Handler->insert($banner);
                    header('Location: ' . $banner->getVar('banner_clickurl'));
                    exit();
                } else {
                    //No valid referer found so some javascript error or direct access found
                    echo _MD_BANNERS_INDEX_NO_REFERER;
                }
            }
        }
        $xoops->redirect(\XoopsBaseConfig::get('url'), 3, _MD_BANNERS_INDEX_NO_ID);
        break;
}
