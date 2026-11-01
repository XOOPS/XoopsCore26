<?php

namespace XoopsModules\Publisher\Form;

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

use Xoops;
use Xoops\Core\Kernel\Criteria;
use Xoops\Core\Lists\ImageFile;
use Xoops\Form\Button;
use Xoops\Form\Checkbox;
use Xoops\Form\Editor;
use Xoops\Form\ElementTray;
use Xoops\Form\File;
use Xoops\Form\Hidden;
use Xoops\Form\Label;
use Xoops\Form\Select;
use Xoops\Form\SelectEditor;
use Xoops\Form\SelectUser;
use Xoops\Form\Text;
use Xoops\Form\TextArea;
use Xoops\Form\ThemeForm;
use XoopsBaseConfig;
use XoopsModules\Publisher;
use XoopsModules\Publisher\Helper;
use XoopsObjectTree;

/**
 *  Publisher form class
 *
 * @copyright       The XUUPS Project http://sourceforge.net/projects/xuups/
 * @license         GNU GPL V2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         Publisher
 * @since           1.0
 * @author          trabis <lusopoemas@gmail.com>
 * @version         $Id$
 */
require_once \dirname(\dirname(__DIR__)) . '/include/common.php';

/**
 * Class CategoryForm
 * @package XoopsModules\Publisher\Form
 */
class CategoryForm extends ThemeForm
{
    /**
     * @var int
     */
    private $_subCatsCount = 4;

    /**
     * @param int $count
     */
    public function setSubCatsCount($count): void
    {
        $this->_subCatsCount = (int)$count;
    }

    public function __construct(Publisher\Category $obj)
    {
        $xoops = Xoops::getInstance();
        $helper = Helper::getInstance();

        $memberHandler = $xoops->getHandlerMember();
        $userGroups = $memberHandler->getGroupList();

        parent::__construct(_AM_PUBLISHER_CATEGORY, 'form', $xoops->getEnv('PHP_SELF'), 'post');
        // long-lived token: editing a category can take longer than the 300 s default
        $this->addElement(new \Xoops\Form\Token('XOOPS_TOKEN', 3600));
        $this->setExtra('enctype="multipart/form-data"');

        // Category
        $criteria = new Criteria(null);
        $criteria->setSort('weight');
        $criteria->setOrder('ASC');
        $categories = $helper->getCategoryHandler()->getObjects($criteria);
        $mytree = new XoopsObjectTree($categories, 'categoryid', 'parentid');
        $cat_select = $mytree->makeSelBox('parentid', 'name', '--', $obj->getVar('parentid'), true);
        $this->addElement(new Label(_AM_PUBLISHER_PARENT_CATEGORY_EXP, $cat_select));

        // Name
        $this->addElement(new Text(_AM_PUBLISHER_CATEGORY, 'name', 50, 255, $obj->getVar('name', 'e')), true);

        // Description
        $this->addElement(new TextArea(_AM_PUBLISHER_COLDESCRIPT, 'description', $obj->getVar('description', 'e'), 7, 60));

        // EDITOR
        $groups = $xoops->getUserGroups();
        $gpermHandler = $helper->getGrouppermHandler();
        $module_id = $helper->getModule()->mid();
        $allowed_editors = Publisher\Utils::getEditors($gpermHandler->getItemIds('editors', $groups, $module_id));
        $nohtml = false;
        if (\count($allowed_editors) > 0) {
            $editor = @$_POST['editor'];
            if (!empty($editor)) {
                Publisher\Utils::setCookieVar('publisher_editor', $editor);
            } else {
                $editor = Publisher\Utils::getCookieVar('publisher_editor');
                if (empty($editor) && $xoops->isUser()) {
                    $editor = $xoops->user->getVar('publisher_editor'); // Need set through user profile
                }
            }
            $editor = (empty($editor) || !\in_array($editor, $allowed_editors)) ? $helper->getConfig('submit_editor') : $editor;
            $form_editor = new SelectEditor($this, 'editor', $editor, $nohtml, $allowed_editors);
            $this->addElement($form_editor);
        } else {
            $editor = $helper->getConfig('submit_editor');
        }

        $editor_configs = [];
        $editor_configs['rows'] = '' == $helper->getConfig('submit_editor_rows') ? 35 : $helper->getConfig('submit_editor_rows');
        $editor_configs['cols'] = '' == $helper->getConfig('submit_editor_cols') ? 60 : $helper->getConfig('submit_editor_cols');
        $editor_configs['width'] = '' == $helper->getConfig('submit_editor_width') ? '100%' : $helper->getConfig('submit_editor_width');
        $editor_configs['height'] = '' == $helper->getConfig('submit_editor_height') ? '400px' : $helper->getConfig('submit_editor_height');

        $editor_configs['name'] = 'header';
        $editor_configs['value'] = $obj->getVar('header', 'e');

        $text_header = new Editor(_AM_PUBLISHER_CATEGORY_HEADER, $editor, $editor_configs, $nohtml, $onfailure = null);
        $text_header->setDescription(_AM_PUBLISHER_CATEGORY_HEADER_DSC);
        $this->addElement($text_header);

        // IMAGE
        $image_select = new Select('', 'image', $obj->image());
        ImageFile::setOptionsArray($image_select, Publisher\Utils::getImageDir('category'));
        $image_select->setExtra("onchange='showImgSelected(\"image3\", \"image\", \"" . 'uploads/' . \PUBLISHER_DIRNAME . '/images/category/' . '", "", "' . XoopsBaseConfig::get('url') . "\")'");
        $image_tray = new ElementTray(_AM_PUBLISHER_IMAGE, '&nbsp;');
        $image_tray->addElement($image_select);
        $image_tray->addElement(new Label('', "<br><br><img src='" . Publisher\Utils::getImageDir('category', false) . $obj->image() . "' name='image3' id='image3' alt=''>"));
        $image_tray->setDescription(_AM_PUBLISHER_IMAGE_DSC);
        $this->addElement($image_tray);

        // IMAGE UPLOAD
        //$max_size = 5000000;
        $file_box = new File(_AM_PUBLISHER_IMAGE_UPLOAD, 'image_file');
        $file_box->set('size', 45);
        $file_box->setDescription(_AM_PUBLISHER_IMAGE_UPLOAD_DSC);
        $this->addElement($file_box);

        // Short url
        $text_short_url = new Text(_AM_PUBLISHER_CATEGORY_SHORT_URL, 'short_url', 50, 255, $obj->getVar('short_url', 'e'));
        $text_short_url->setDescription(_AM_PUBLISHER_CATEGORY_SHORT_URL_DSC);
        $this->addElement($text_short_url);

        // Meta Keywords
        $text_meta_keywords = new TextArea(_AM_PUBLISHER_CATEGORY_META_KEYWORDS, 'meta_keywords', $obj->getVar('meta_keywords', 'e'), 7, 60);
        $text_meta_keywords->setDescription(_AM_PUBLISHER_CATEGORY_META_KEYWORDS_DSC);
        $this->addElement($text_meta_keywords);

        // Meta Description
        $text_meta_description = new TextArea(_AM_PUBLISHER_CATEGORY_META_DESCRIPTION, 'meta_description', $obj->getVar('meta_description', 'e'), 7, 60);
        $text_meta_description->setDescription(_AM_PUBLISHER_CATEGORY_META_DESCRIPTION_DSC);
        $this->addElement($text_meta_description);

        // Weight
        $this->addElement(new Text(_AM_PUBLISHER_COLPOSIT, 'weight', 4, 4, $obj->getVar('weight')));

        // Added by skalpa: custom template support
        //todo, check this
        $this->addElement(new Text('Custom template', 'template', 50, 255, $obj->getVar('template', 'e')), false);

        // READ PERMISSIONS
        $groups_read_checkbox = new Checkbox(_AM_PUBLISHER_PERMISSIONS_CAT_READ, 'groups_read[]', $obj->getGroups_read());
        foreach ($userGroups as $group_id => $group_name) {
            $groups_read_checkbox->addOption($group_id, $group_name);
        }
        $this->addElement($groups_read_checkbox);

        // SUBMIT PERMISSIONS
        $groups_submit_checkbox = new Checkbox(_AM_PUBLISHER_PERMISSIONS_CAT_SUBMIT, 'groups_submit[]', $obj->getGroups_submit());
        $groups_submit_checkbox->setDescription(_AM_PUBLISHER_PERMISSIONS_CAT_SUBMIT_DSC);
        foreach ($userGroups as $group_id => $group_name) {
            $groups_submit_checkbox->addOption($group_id, $group_name);
        }
        $this->addElement($groups_submit_checkbox);

        // MODERATION PERMISSIONS
        $groups_moderation_checkbox = new Checkbox(_AM_PUBLISHER_PERMISSIONS_CAT_MODERATOR, 'groups_moderation[]', $obj->getGroups_moderation());
        $groups_moderation_checkbox->setDescription(_AM_PUBLISHER_PERMISSIONS_CAT_MODERATOR_DSC);
        foreach ($userGroups as $group_id => $group_name) {
            $groups_moderation_checkbox->addOption($group_id, $group_name);
        }
        $this->addElement($groups_moderation_checkbox);

        $moderator = new SelectUser(_AM_PUBLISHER_CATEGORY_MODERATOR, 'moderator', true, $obj->getVar('moderator', 'e'), 1, false);
        $moderator->setDescription(_AM_PUBLISHER_CATEGORY_MODERATOR_DSC);
        $this->addElement($moderator);

        $cat_tray = new ElementTray(_AM_PUBLISHER_SCATEGORYNAME, '<br><br>');
        for ($i = 0; $i < $this->_subCatsCount; ++$i) {
            if ($i < (isset($_POST['scname']) ? \count($_POST['scname']) : 0)) {
                $subname = isset($_POST['scname']) ? $_POST['scname'][$i] : '';
            } else {
                $subname = '';
            }
            $cat_tray->addElement(new Text('', 'scname[' . $i . ']', 50, 255, $subname));
        }
        $t = new Text('', 'nb_subcats', 3, 2);
        $l = new Label('', \sprintf(_AM_PUBLISHER_ADD_OPT, $t->render()));
        $b = new Button('', 'submit_subcats', _AM_PUBLISHER_ADD_OPT_SUBMIT, 'submit');

        if (!$obj->getVar('categoryid')) {
            $b->setExtra('onclick="this.form.elements.op.value=\'addsubcats\'"');
        } else {
            $b->setExtra('onclick="this.form.elements.op.value=\'mod\'"');
        }

        $r = new ElementTray('');
        $r->addElement($l);
        $r->addElement($b);
        $cat_tray->addElement($r);
        $this->addElement($cat_tray);

        $this->addElement(new Hidden('categoryid', $obj->getVar('categoryid')));
        $this->addElement(new Hidden('nb_sub_yet', $this->_subCatsCount));

        // Action buttons tray
        $buttonTray = new ElementTray('', '');

        // No ID for category -- then it's new category, button says 'Create'
        if (!$obj->getVar('categoryid')) {
            $buttonTray->addElement(new Button('', 'addcategory', _AM_PUBLISHER_CREATE, 'submit'));

            $buttonClear = new Button('', '', _AM_PUBLISHER_CLEAR, 'reset');
            $buttonTray->addElement($buttonClear);

            $buttonCancel = new Button('', '', _AM_PUBLISHER_CANCEL, 'button');
            $buttonCancel->setExtra('onclick="history.go(-1)"');
            $buttonTray->addElement($buttonCancel);

            $this->addElement($buttonTray);
        } else {
            $buttonTray->addElement(new Button('', 'addcategory', _AM_PUBLISHER_MODIFY, 'submit'));

            $buttonCancel = new Button('', '', _AM_PUBLISHER_CANCEL, 'button');
            $buttonCancel->setExtra('onclick="history.go(-1)"');
            $buttonTray->addElement($buttonCancel);

            $this->addElement($buttonTray);
        }
    }
}
