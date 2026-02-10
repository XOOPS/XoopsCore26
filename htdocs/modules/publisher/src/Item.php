<?php

namespace XoopsModules\Publisher;

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

use Images;
use Notifications;
use Xmf\Highlighter;
use Xmf\Request;
use Xoops;
use Xoops\Core\Kernel\Criteria;
use Xoops\Core\Kernel\CriteriaCompo;
use Xoops\Core\Kernel\XoopsObject;
use Xoops\Core\Text\Sanitizer;
use XoopsBaseConfig;
use XoopsLoad;
use XoopsLocale;
use XoopsModules\Publisher;
use XoopsUserUtility;

/**
 * @copyright       The XUUPS Project http://sourceforge.net/projects/xuups/
 * @license         GNU GPL V2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         Publisher
 * @since           1.0
 * @author          trabis <lusopoemas@gmail.com>
 * @author          The SmartFactory <www.smartfactory.ca>
 * @version         $Id$
 */
require_once \dirname(__DIR__) . '/include/common.php';

/**
 * Class Item
 * @package XoopsModules\Publisher
 */
class Item extends XoopsObject
{
    /**
     * @var Helper
     * @access public
     */
    public $helper = null;

    /**
     * @var Publisher\Category
     * @access public
     */
    public $_category = null;

    /**
     * @param int|null $id
     */
    public function __construct($id = null)
    {
        $this->helper = Helper::getInstance();
        $this->initVar('itemid', \XOBJ_DTYPE_INT, 0);
        $this->initVar('categoryid', \XOBJ_DTYPE_INT, 0, false);
        $this->initVar('title', \XOBJ_DTYPE_TXTBOX, '', true, 255);
        $this->initVar('subtitle', \XOBJ_DTYPE_TXTBOX, '', false, 255);
        $this->initVar('summary', \XOBJ_DTYPE_TXTAREA, '', false);
        $this->initVar('body', \XOBJ_DTYPE_TXTAREA, '', false);
        $this->initVar('uid', \XOBJ_DTYPE_INT, 0, false);
        $this->initVar('author_alias', \XOBJ_DTYPE_TXTBOX, '', false, 255);
        $this->initVar('datesub', \XOBJ_DTYPE_INT, '', false);
        $this->initVar('status', \XOBJ_DTYPE_INT, -1, false);
        $this->initVar('image', \XOBJ_DTYPE_INT, 0, false);
        $this->initVar('images', \XOBJ_DTYPE_TXTBOX, '', false, 255);
        $this->initVar('counter', \XOBJ_DTYPE_INT, 0, false);
        $this->initVar('rating', \XOBJ_DTYPE_OTHER, 0, false);
        $this->initVar('votes', \XOBJ_DTYPE_INT, 0, false);
        $this->initVar('weight', \XOBJ_DTYPE_INT, 0, false);
        $this->initVar('dohtml', \XOBJ_DTYPE_INT, 1, true);
        $this->initVar('dosmiley', \XOBJ_DTYPE_INT, 1, true);
        $this->initVar('doimage', \XOBJ_DTYPE_INT, 1, true);
        $this->initVar('dobr', \XOBJ_DTYPE_INT, 1, false);
        $this->initVar('doxcode', \XOBJ_DTYPE_INT, 1, true);
        $this->initVar('cancomment', \XOBJ_DTYPE_INT, 1, true);
        $this->initVar('comments', \XOBJ_DTYPE_INT, 0, false);
        $this->initVar('notifypub', \XOBJ_DTYPE_INT, 1, false);
        $this->initVar('meta_keywords', \XOBJ_DTYPE_TXTAREA, '', false);
        $this->initVar('meta_description', \XOBJ_DTYPE_TXTAREA, '', false);
        $this->initVar('short_url', \XOBJ_DTYPE_TXTBOX, '', false, 255);
        $this->initVar('item_tag', \XOBJ_DTYPE_TXTAREA, '', false);
        // Non consistent values
        $this->initVar('pagescount', \XOBJ_DTYPE_INT, 0, false);
        if (isset($id)) {
            $item = $this->helper->getItemHandler()->get($id);
            foreach ($item->vars as $k => $v) {
                $this->assignVar($k, $v['value']);
            }
        }
    }

    /**
     * @return null|Publisher\Category
     */
    public function category(): ?Category
    {
        if (!isset($this->_category)) {
            $this->_category = $this->helper->getCategoryHandler()->get($this->getVar('categoryid'));
        }

        return $this->_category;
    }

    /**
     * @param int    $maxLength
     * @param string $format
     */
    public function title($maxLength = 0, $format = 'S'): string
    {
        $ret = $this->getVar('title', $format);
        if (0 != $maxLength) {
            if (!XoopsLocale::isMultiByte()) {
                if (\mb_strlen($ret) >= $maxLength) {
                    $ret = Publisher\Utils::substr($ret, 0, $maxLength);
                }
            }
        }

        return $ret;
    }

    /**
     * @param int    $maxLength
     * @param string $format
     *
     * @return mixed|string
     */
    public function subtitle($maxLength = 0, $format = 'S')
    {
        $ret = $this->getVar('subtitle', $format);
        if (0 != $maxLength) {
            if (!XoopsLocale::isMultiByte()) {
                if (\mb_strlen($ret) >= $maxLength) {
                    $ret = Publisher\Utils::substr($ret, 0, $maxLength);
                }
            }
        }

        return $ret;
    }

    /**
     * @param int    $maxLength
     * @param string $format
     * @param string $stripTags
     *
     * @return mixed|string
     */
    public function summary($maxLength = 0, $format = 'S', $stripTags = '')
    {
        $ret = $this->getVar('summary', $format);
        if (!empty($stripTags)) {
            $ret = \strip_tags($ret, $stripTags);
        }
        if (0 != $maxLength) {
            if (!XoopsLocale::isMultiByte()) {
                if (\mb_strlen($ret) >= $maxLength) {
                    //$ret = Publisher\Utils::substr($ret , 0, $maxLength);
                    $ret = Publisher\Utils::truncateTagSafe($ret, $maxLength, $etc = '...', $break_words = false);
                }
            }
        }

        return $ret;
    }

    /**
     * @param int  $maxLength
     * @param bool $fullSummary
     *
     * @return mixed|string
     */
    public function getBlockSummary($maxLength = 0, $fullSummary = false)
    {
        if ($fullSummary) {
            $ret = $this->summary(0, 's', '<br><br>');
        } else {
            $ret = $this->summary($maxLength, 's', '<br><br>');
        }
        //no summary? get body!
        if ('' == $ret) {
            $ret = $this->body($maxLength, 's', '<br><br>');
        }

        return $ret;
    }

    /**
     * @param string $file_name
     */
    public function wrappage($file_name): string
    {
        $content = '';
        $page = Publisher\Utils::getUploadDir(true, 'content') . $file_name;
        if (XoopsLoad::fileExists($page)) {
            // this page uses smarty template
            \ob_start();
            include $page;
            $content = \ob_get_clean();
            // Cleaning the content
            $body_start_pos = \mb_strpos($content, '<body>');
            if ($body_start_pos) {
                $body_end_pos = \mb_strpos($content, '</body>', $body_start_pos);
                $content = \mb_substr($content, $body_start_pos + \mb_strlen('<body>'), $body_end_pos - \mb_strlen('<body>') - $body_start_pos);
            }
            // Check if ML Hack is installed, and if yes, parse the $content in formatForML
            $myts = Sanitizer::getInstance();
            if (\method_exists($myts, 'formatForML')) {
                $content = $myts->formatForML($content);
            }
        }

        return $content;
    }

    /**
     * This method returns the body to be displayed. Not to be used for editing
     *
     * @param int    $maxLength
     * @param string $format
     * @param string $stripTags
     *
     * @return mixed|string
     */
    public function body($maxLength = 0, $format = 'S', $stripTags = '')
    {
        $ret = $this->getVar('body', $format);
        $wrap_pos = \mb_strpos($ret, '[pagewrap=');
        if (!(false === $wrap_pos)) {
            $wrap_pages = [];
            $wrap_code_length = \mb_strlen('[pagewrap=');
            while (!(false === $wrap_pos)) {
                $end_wrap_pos = \mb_strpos($ret, ']', $wrap_pos);
                if ($end_wrap_pos) {
                    $wrap_page_name = \mb_substr($ret, $wrap_pos + $wrap_code_length, $end_wrap_pos - $wrap_code_length - $wrap_pos);
                    $wrap_pages[] = $wrap_page_name;
                }
                $wrap_pos = \mb_strpos($ret, '[pagewrap=', $end_wrap_pos - 1);
            }
            foreach ($wrap_pages as $page) {
                $wrap_page_content = $this->wrappage($page);
                $ret = \str_replace("[pagewrap={$page}]", $wrap_page_content, $ret);
            }
        }
        if ($this->helper->getConfig('item_disp_blocks_summary')) {
            $summary = $this->summary($maxLength, $format, $stripTags);
            if ($summary) {
                $ret = $this->summary() . '<br><br>' . $ret;
            }
        }
        if (!empty($stripTags)) {
            $ret = \strip_tags($ret, $stripTags);
        }
        if (0 != $maxLength) {
            if (!XoopsLocale::isMultiByte()) {
                if (\mb_strlen($ret) >= $maxLength) {
                    //$ret = Publisher\Utils::substr($ret , 0, $maxLength);
                    $ret = Publisher\Utils::truncateTagSafe($ret, $maxLength, $etc = '...', $break_words = false);
                }
            }
        }

        return $ret;
    }

    /**
     * @param string $dateFormat
     * @param string $format
     */
    public function datesub($dateFormat = '', $format = 'S'): string
    {
        if (empty($dateformat)) {
            $dateFormat = $this->helper->getConfig('format_date');
        }

        return XoopsLocale::formatTimestamp($this->getVar('datesub', $format), $dateFormat);
    }

    /**
     * @param int $realName
     */
    public function posterName($realName = -1): string
    {
        if (-1 == $realName) {
            $realName = $this->helper->getConfig('format_realname');
        }
        $ret = $this->getVar('author_alias');
        if ('' == $ret) {
            $ret = XoopsUserUtility::getUnameFromId($this->getVar('uid'), $realName);
        }

        return $ret;
    }

    public function posterAvatar(): string
    {
        $xoops = Xoops::getInstance();
        $ret = 'blank.gif';
        $memberHandler = $xoops->getHandlerMember();
        $thisUser = $memberHandler->getUser($this->getVar('uid'));
        if (\is_object($thisUser)) {
            $ret = $xoops->service('avatar')->getAvatarUrl($thisUser)->getValue();
        }

        return $ret;
    }

    public function linkedPosterName(): string
    {
        $ret = $this->getVar('author_alias');
        if ('' == $ret) {
            $ret = XoopsUserUtility::getUnameFromId($this->getVar('uid'), $this->helper->getConfig('format_realname'), true);
        }

        return $ret;
    }

    /**
     * @return mixed
     */
    public function updateCounter()
    {
        return $this->helper->getItemHandler()->updateCounter($this->getVar('itemid'));
    }

    /**
     * @param bool $force
     */
    public function store($force = true): bool
    {
        $xoops = Xoops::getInstance();
        $isNew = $this->isNew();
        if (!$this->helper->getItemHandler()->insert($this, $force)) {
            return false;
        }
        if ($isNew && \_PUBLISHER_STATUS_PUBLISHED == $this->getVar('status')) {
            // Increment user posts
            $userHandler = $xoops->getHandlerUser();
            $memberHandler = $xoops->getHandlerMember();
            $poster = $userHandler->get($this->getVar('uid'));
            if (\is_object($poster) && !$poster->isNew()) {
                $poster->setVar('posts', $poster->getVar('posts') + 1);
                if (!$memberHandler->insertUser($poster, true)) {
                    $this->setErrors('Article created but could not increment user posts.');

                    return false;
                }
            }
        }

        return true;
    }

    public function getCategoryName(): string
    {
        return $this->category()->getVar('name');
    }

    public function getCategoryUrl(): string
    {
        return $this->category()->getCategoryUrl();
    }

    public function getCategoryLink(): string
    {
        return $this->category()->getCategoryLink();
    }

    /**
     * @param bool $withAllLink
     */
    public function getCategoryPath($withAllLink = true): string
    {
        return $this->category()->getCategoryPath($withAllLink);
    }

    public function getCategoryImagePath(): string
    {
        return Publisher\Utils::getImageDir('category', false) . $this->category()->image();
    }

    /**
     * @return mixed
     */
    public function getFiles()
    {
        return $this->helper->getFileHandler()->getAllFiles($this->getVar('itemid'), _PUBLISHER_STATUS_FILE_ACTIVE);
    }

    public function getAdminLinks(): string
    {
        $xoops = Xoops::getInstance();
        $adminLinks = '';
        if ($xoops->isUser() && (Publisher\Utils::IsUserAdmin() || Publisher\Utils::IsUserAuthor($this) || $this->helper->getPermissionHandler()->isGranted('item_submit', $this->getVar('categoryid')))) {
            if (Publisher\Utils::IsUserAdmin() || Publisher\Utils::IsUserAuthor($this) || Publisher\Utils::IsUserModerator($this)) {
                if ($this->helper->getConfig('perm_edit') || Publisher\Utils::IsUserModerator($this) || Publisher\Utils::IsUserAdmin()) {
                    // Edit button
                    $adminLinks .= "<a href='" . \PUBLISHER_URL . '/submit.php?itemid=' . $this->getVar('itemid') . "'><img src='" . \PUBLISHER_URL . "/images/links/edit.gif'" . " title='" . _CO_PUBLISHER_EDIT . "' alt='" . _CO_PUBLISHER_EDIT . "'></a>";
                    $adminLinks .= ' ';
                }
                if ($this->helper->getConfig('perm_delete') || Publisher\Utils::IsUserModerator($this) || Publisher\Utils::IsUserAdmin()) {
                    // Delete button
                    $adminLinks .= "<a href='" . \PUBLISHER_URL . '/submit.php?op=del&amp;itemid=' . $this->getVar('itemid') . "'><img src='" . \PUBLISHER_URL . "/images/links/delete.png'" . " title='" . _CO_PUBLISHER_DELETE . "' alt='" . _CO_PUBLISHER_DELETE . "'></a>";
                    $adminLinks .= ' ';
                }
            }
            if ($this->helper->getConfig('perm_clone') || Publisher\Utils::IsUserModerator($this) || Publisher\Utils::IsUserAdmin()) {
                // Duplicate button
                $adminLinks .= "<a href='" . \PUBLISHER_URL . '/submit.php?op=clone&amp;itemid=' . $this->getVar('itemid') . "'><img src='" . \PUBLISHER_URL . "/images/links/clone.gif'" . " title='" . _CO_PUBLISHER_CLONE . "' alt='" . _CO_PUBLISHER_CLONE . "'></a>";
                $adminLinks .= ' ';
            }
        }
        // PDF button
        if ($xoops->service('htmltopdf')->isAvailable()) {
            $adminLinks .= "<a href='" . \PUBLISHER_URL . '/makepdf.php?itemid=' . $this->getVar('itemid') . "' rel='nofollow' target='_blank'><img src='" . \PUBLISHER_URL . "/images/links/pdf.gif' title='" . _CO_PUBLISHER_PDF . "' alt='" . _CO_PUBLISHER_PDF . "'></a>";
            $adminLinks .= ' ';
        }
        // Print button
        $adminLinks .= "<a href='" . Publisher\Utils::seoGenUrl('print', $this->getVar('itemid'), $this->getVar('short_url')) . "' rel='nofollow' target='_blank'><img src='" . \PUBLISHER_URL . "/images/links/print.gif' title='" . _CO_PUBLISHER_PRINT . "' alt='" . _CO_PUBLISHER_PRINT . "'></a>";
        $adminLinks .= ' ';
        // Email button
        if ($xoops->isActiveModule('tellafriend')) {
            $subject = \sprintf(_CO_PUBLISHER_INTITEMFOUND, $xoops->getConfig('sitename'));
            $subject = $this->_convert_for_japanese($subject);
            $maillink = Publisher\Utils::tellafriend($subject);
            $adminLinks .= '<a href="' . $maillink . '"><img src="' . \PUBLISHER_URL . '/images/links/friend.gif" title="' . _CO_PUBLISHER_MAIL . '" alt="' . _CO_PUBLISHER_MAIL . '"></a>';
            $adminLinks .= ' ';
        }

        return $adminLinks;
    }

    /**
     * @param array $notifications
     */
    public function sendNotifications($notifications = []): void
    {
        $xoops = Xoops::getInstance();
        if ($xoops->isActiveModule('notifications')) {
            $notificationHandler = Notifications::getInstance()->getHandlerNotification();
            $tags = [];
            $tags['MODULE_NAME'] = $this->helper->getModule()->getVar('name');
            $tags['ITEM_NAME'] = $this->title();
            $tags['CATEGORY_NAME'] = $this->getCategoryName();
            $tags['CATEGORY_URL'] = \PUBLISHER_URL . '/category.php?categoryid=' . $this->getVar('categoryid');
            $tags['ITEM_BODY'] = $this->body();
            $tags['DATESUB'] = $this->datesub();
            foreach ($notifications as $notification) {
                switch ($notification) {
                    case \_PUBLISHER_NOT_ITEM_PUBLISHED:
                        $tags['ITEM_URL'] = \PUBLISHER_URL . '/item.php?itemid=' . $this->getVar('itemid');
                        $notificationHandler->triggerEvent('global', 0, 'published', $tags, [], $this->helper->getModule()->getVar('mid'));
                        $notificationHandler->triggerEvent('category', $this->getVar('categoryid'), 'published', $tags, [], $this->helper->getModule()->getVar('mid'));
                        $notificationHandler->triggerEvent('item', $this->getVar('itemid'), 'approved', $tags, [], $this->helper->getModule()->getVar('mid'));
                        break;
                    case \_PUBLISHER_NOT_ITEM_SUBMITTED:
                        $tags['WAITINGFILES_URL'] = \PUBLISHER_URL . '/admin/item.php?itemid=' . $this->getVar('itemid');
                        $notificationHandler->triggerEvent('global', 0, 'submitted', $tags, [], $this->helper->getModule()->getVar('mid'));
                        $notificationHandler->triggerEvent('category', $this->getVar('categoryid'), 'submitted', $tags, [], $this->helper->getModule()->getVar('mid'));
                        break;
                    case \_PUBLISHER_NOT_ITEM_REJECTED:
                        $notificationHandler->triggerEvent('item', $this->getVar('itemid'), 'rejected', $tags, [], $this->helper->getModule()->getVar('mid'));
                        break;
                    case -1:
                    default:
                        break;
                }
            }
        }
    }

    public function notLoaded(): bool
    {
        return -1 == $this->getVar('itemid');
    }

    public function getItemUrl(): string
    {
        return Publisher\Utils::seoGenUrl('item', $this->getVar('itemid'), $this->getVar('short_url'));
    }

    /**
     * @param bool $class
     * @param int  $maxsize
     *
     * @return string
     */
    public function getItemLink($class = false, $maxsize = 0): ?string
    {
        if ($class) {
            return '<a class=' . $class . ' href="' . $this->getItemUrl() . '">' . $this->title($maxsize) . '</a>';
        }

        return '<a href="' . $this->getItemUrl() . '">' . $this->title($maxsize) . '</a>';
    }

    public function getWhoAndWhen(): string
    {
        $posterName = $this->linkedPosterName();
        $postdate = $this->datesub();

        return \sprintf(_CO_PUBLISHER_POSTEDBY, $posterName, $postdate);
    }

    /**
     * @param null|string $body
     */
    public function plain_maintext($body = null): string
    {
        $ret = '';
        if (!$body) {
            $body = $this->body();
        }
        $ret .= \str_replace('[pagebreak]', '<br><br>', $body);

        return $ret;
    }

    /**
     * @param int         $item_page_id
     * @param null|string $body
     */
    public function buildmaintext($item_page_id = -1, $body = null): string
    {
        if (!$body) {
            $body = $this->body();
        }
        $body_parts = \explode('[pagebreak]', $body);
        $this->setVar('pagescount', \count($body_parts));
        if (\count($body_parts) <= 1) {
            return $this->plain_maintext($body);
        }
        $ret = '';
        if (-1 == $item_page_id) {
            $ret .= \trim($body_parts[0]);

            return $ret;
        }
        if ($item_page_id >= \count($body_parts)) {
            $item_page_id = \count($body_parts) - 1;
        }
        $ret .= \trim($body_parts[$item_page_id]);

        return $ret;
    }

    /**
     * @return mixed
     */
    public function getImages()
    {
        static $ret;

        $xoops = Xoops::getInstance();
        if (!$xoops->isActiveModule('images')) {
            return [];
        }
        $itemid = $this->getVar('itemid');
        if (!isset($ret[$itemid])) {
            $ret[$itemid]['main'] = '';
            $ret[$itemid]['others'] = [];
            $images_ids = [];
            $image = $this->getVar('image');
            $images = $this->getVar('images');
            if ('' != $images) {
                $images_ids = \explode('|', $images);
            }
            if ($image > 0) {
                $images_ids[] = $image;
            }
            $imageObjs = [];
            if (\count($images_ids) > 0) {
                $imageHandler = Images::getInstance()->getHandlerImages();
                $criteria = new CriteriaCompo(new Criteria('image_id', '(' . \implode(',', $images_ids) . ')', 'IN'));
                $imageObjs = $imageHandler->getObjects($criteria, true);
                unset($criteria);
            }
            foreach ($imageObjs as $id => $imageObj) {
                if ($id == $image) {
                    $ret[$itemid]['main'] = $imageObj;
                } else {
                    $ret[$itemid]['others'][] = $imageObj;
                }
                unset($imageObj);
            }
            unset($imageObjs);
        }

        return $ret[$itemid];
    }

    /**
     * @param string $display
     * @param int    $max_char_title
     * @param int    $max_char_summary
     * @param bool   $full_summary
     *
     * @return array
     */
    public function toArray($display = 'default', $max_char_title = 0, $max_char_summary = 0, $full_summary = false)
    {
        $item_page_id = -1;
        if (\is_numeric($display)) {
            $item_page_id = $display;
            $display = 'all';
        }
        $item['itemid'] = $this->getVar('itemid');
        $item['uid'] = $this->getVar('uid');
        $item['titlelink'] = $this->getItemLink(false, $max_char_title);
        $item['subtitle'] = $this->subtitle();
        $item['datesub'] = $this->datesub();
        $item['counter'] = $this->getVar('counter');
        switch ($display) {
            case 'summary':
            case 'list':
                break;
            case 'full':
            case 'wfsection':
            case 'default':
                $summary = $this->summary($max_char_summary);
                if (!$summary) {
                    $summary = $this->body($max_char_summary);
                }
                $item['summary'] = $summary;
                $item = $this->toArrayFull($item);
                break;
            case 'all':
                $item = $this->toArrayFull($item);
                $item = $this->toArrayAll($item, $item_page_id);
                break;
        }
        // Highlighting searched words
        $highlight = true;
        if ($highlight && isset($_GET['keywords'])) {
            $myts = Sanitizer::getInstance();
            $keywords = $myts->htmlSpecialChars(\trim(\urldecode($_GET['keywords'])));
            $fields = ['title', 'maintext', 'summary'];
            foreach ($fields as $field) {
                if (isset($item[$field])) {
                    $item[$field] = $this->highlight($item[$field], $keywords);
                }
            }
        }

        return $item;
    }

    /**
     * @param array $item
     */
    public function toArrayFull($item): array
    {
        $item['title'] = $this->title();
        $item['clean_title'] = $this->title();
        $item['itemurl'] = $this->getItemUrl();
        $item['cancomment'] = $this->getVar('cancomment');
        $item['comments'] = $this->getVar('comments');
        $item['adminlink'] = $this->getAdminLinks();
        $item['categoryPath'] = $this->getCategoryPath($this->helper->getConfig('format_linked_path'));
        $item['who_when'] = $this->getWhoAndWhen();
        $item = $this->getMainImage($item);

        return $item;
    }

    /**
     * @param array $item
     * @param int   $item_page_id
     */
    public function toArrayAll($item, $item_page_id): array
    {
        $item['maintext'] = $this->buildmaintext($item_page_id, $this->body());
        $item = $this->getOtherImages($item);

        return $item;
    }

    /**
     * @param array $item
     */
    public function getMainImage($item = []): array
    {
        $images = $this->getImages();
        $item['image_path'] = '';
        $item['image_name'] = '';
        if (\is_object($images['main'])) {
            /* @var \ImagesImage $image */
            $image = $images['main'];
            $dimensions = \getimagesize(XoopsBaseConfig::get('root-path') . '/uploads/' . $image->getVar('image_name'));
            $item['image_width'] = $dimensions[0];
            $item['image_height'] = $dimensions[1];
            $item['image_path'] = XoopsBaseConfig::get('url') . '/uploads/' . $image->getVar('image_name');
            // pass this on since some consumers build custom thumbnails
            $item['image_vpath'] = 'uploads/' . $image->getVar('image_name');
            $item['image_thumb'] = Xoops::getInstance()->service('thumbnail')->getImgUrl($item['image_vpath'], 0, 180)->getValue();
            $item['image_name'] = $image->getVar('image_nicename');
        }

        return $item;
    }

    /**
     * @param array $item
     */
    public function getOtherImages($item = []): array
    {
        $thumbService = Xoops::getInstance()->service('thumbnail');
        $images = $this->getImages();
        $item['images'] = [];
        $i = 0;
        /* @var \ImagesImage $image */
        foreach ($images['others'] as $image) {
            $dimensions = \getimagesize(XoopsBaseConfig::get('root-path') . '/uploads/' . $image->getVar('image_name'));
            $item['images'][$i]['width'] = $dimensions[0];
            $item['images'][$i]['height'] = $dimensions[1];
            $item['images'][$i]['path'] = XoopsBaseConfig::get('url') . '/uploads/' . $image->getVar('image_name');
            $item['images'][$i]['thumb'] = $thumbService->getImgUrl('uploads/' . $image->getVar('image_name'), 240, 0)->getValue();
            $item['images'][$i]['name'] = $image->getVar('image_nicename');
            ++$i;
        }

        return $item;
    }

    /**
     * @param string       $content
     * @param string|array $keywords
     *
     * @return string Text
     */
    public function highlight($content, $keywords): string
    {
        $color = $this->helper->getConfig('format_highlight_color');
        if (0 !== \mb_strpos($color, '#')) {
            $color = '#' . $color;
        }
        $pre = '<span style="font-weight: bolder; background-color: ' . $color . ';">';
        $post = '</span>';

        return Highlighter::apply($keywords, $content, $pre, $post);
    }

    /**
     *  Create metada and assign it to template
     */
    public function createMetaTags(): void
    {
        $publisher_metagen = new Publisher\Metagen($this->title(), $this->getVar('meta_keywords'), $this->getVar('meta_description'), $this->_category->_categoryPath);
        $publisher_metagen->createMetaTags();
    }

    /**
     * @param string $str
     */
    public function _convert_for_japanese($str): string
    {
        global $xoopsConfig;
        // no action, if not flag
        if (!\defined('_PUBLISHER_FLAG_JP_CONVERT')) {
            return $str;
        }
        // no action, if not Japanese
        if ('japanese' !== $xoopsConfig['language']) {
            return $str;
        }
        // presume OS Browser
        $agent = $_SERVER['HTTP_USER_AGENT'];
        $os = '';
        $browser = '';
        if (false !== \mb_stripos($agent, 'Win')) {
            $os = 'win';
        }
        if (false !== \mb_stripos($agent, 'MSIE')) {
            $browser = 'msie';
        }
        // if msie
        if (('win' === $os) && ('msie' === $browser)) {
            // if multibyte
            if (\function_exists('mb_convert_encoding')) {
                $str = mb_convert_encoding($str, 'SJIS', 'EUC-JP');
                $str = \rawurlencode($str);
            }
        }

        return $str;
    }

    /**
     * Checks if a user has access to a selected item. if no item permissions are
     * set, access permission is denied. The user needs to have necessary category
     * permission as well.
     * Also, the item needs to be Published
     *
     * @return bool : TRUE if the no errors occured
     */
    public function accessGranted(): bool
    {
        if (Publisher\Utils::IsUserAdmin()) {
            return true;
        }
        if (\_PUBLISHER_STATUS_PUBLISHED != $this->getVar('status')) {
            return false;
        }
        // Do we have access to the parent category
        if ($this->helper->getPermissionHandler()->isGranted('category_read', $this->getVar('categoryid'))) {
            return true;
        }

        return false;
    }

    /**
     * The name says it all
     */
    public function setVarsFromRequest(): void
    {
        $xoops = Xoops::getInstance();
        //Required fields
        if (isset($_REQUEST['categoryid'])) {
            $this->setVar('categoryid', Request::getInt('categoryid'));
        }
        if (isset($_REQUEST['title'])) {
            $this->setVar('title', Request::getString('title'));
        }
        if (isset($_REQUEST['body'])) {
            $this->setVar('body', Request::getText('body'));
        }
        //Not required fields
        if (isset($_REQUEST['summary'])) {
            $this->setVar('summary', Request::getText('summary'));
        }
        if (isset($_REQUEST['subtitle'])) {
            $this->setVar('subtitle', Request::getString('subtitle'));
        }
        if (isset($_REQUEST['item_tag'])) {
            $this->setVar('item_tag', Request::getString('item_tag'));
        }
        if (isset($_REQUEST['image_featured'])) {
            $image_item = Request::getArray('image_item');
            $image_featured = Request::getString('image_featured');
            //Todo: get a better image class for xoops!
            //Image hack
            $image_item_ids = [];

            $qb = Xoops::getInstance()->db()->createXoopsQueryBuilder();
            $qb->select('i.image_id', 'i.image_name')->fromPrefix('image', 'i')->orderBy('i.image_id');
            $result = $qb->execute();

            while (false !== ($myrow = $result->fetchAssociative())) {
                $image_name = $myrow['image_name'];
                $id = $myrow['image_id'];
                if ($image_name == $image_featured) {
                    $this->setVar('image', $id);
                }
                if (\in_array($image_name, $image_item)) {
                    $image_item_ids[] = $id;
                }
            }
            $this->setVar('images', \implode('|', $image_item_ids));
        }
        if (isset($_REQUEST['uid'])) {
            $this->setVar('uid', Request::getInt('uid'));
        } elseif ($this->isNew()) {
            $this->setVar('uid', $xoops->isUser() ? $xoops->user->getVar('uid') : 0);
        }
        if (isset($_REQUEST['author_alias'])) {
            $this->setVar('author_alias', Request::getString('author_alias'));
            if ('' != $this->getVar('author_alias')) {
                $this->setVar('uid', 0);
            }
        }
        if (isset($_REQUEST['datesub'])) {
            $this->setVar('datesub', \strtotime($_REQUEST['datesub']['date']) + $_REQUEST['datesub']['time']);
        } elseif ($this->isNew()) {
            $this->setVar('datesub', \time());
        }
        if (isset($_REQUEST['item_short_url'])) {
            $this->setVar('short_url', Request::getString('item_short_url'));
        }
        if (isset($_REQUEST['item_meta_keywords'])) {
            $this->setVar('meta_keywords', Request::getString('item_meta_keywords'));
        }
        if (isset($_REQUEST['item_meta_description'])) {
            $this->setVar('meta_description', Request::getString('item_meta_description'));
        }
        if (isset($_REQUEST['weight'])) {
            $this->setVar('weight', Request::getInt('weight'));
        }
        if (isset($_REQUEST['allowcomments'])) {
            $this->setVar('cancomment', Request::getInt('allowcomments'));
        } elseif ($this->isNew()) {
            $this->setVar('cancoment', $this->helper->getConfig('submit_allowcomments'));
        }
        if (isset($_REQUEST['status'])) {
            $this->setVar('status', Request::getInt('status'));
        } elseif ($this->isNew()) {
            $this->setVar('status', $this->helper->getConfig('submit_status'));
        }
        if (isset($_REQUEST['dohtml'])) {
            $this->setVar('dohtml', Request::getInt('dohtml'));
        } elseif ($this->isNew()) {
            $this->setVar('dohtml', $this->helper->getConfig('submit_dohtml'));
        }
        if (isset($_REQUEST['dosmiley'])) {
            $this->setVar('dosmiley', Request::getInt('dosmiley'));
        } elseif ($this->isNew()) {
            $this->setVar('dosmiley', $this->helper->getConfig('submit_dosmiley'));
        }
        if (isset($_REQUEST['doxcode'])) {
            $this->setVar('doxcode', Request::getInt('doxcode'));
        } elseif ($this->isNew()) {
            $this->setVar('doxcode', $this->helper->getConfig('submit_doxcode'));
        }
        if (isset($_REQUEST['doimage'])) {
            $this->setVar('doimage', Request::getInt('doimage'));
        } elseif ($this->isNew()) {
            $this->setVar('doimage', $this->helper->getConfig('submit_doimage'));
        }
        if (isset($_REQUEST['dolinebreak'])) {
            $this->setVar('dobr', Request::getInt('dolinebreak'));
        } elseif ($this->isNew()) {
            $this->setVar('dobr', $this->helper->getConfig('submit_dobr'));
        }
        if (isset($_REQUEST['notify'])) {
            $this->setVar('notifypub', Request::getInt('notify'));
        }
    }
}
