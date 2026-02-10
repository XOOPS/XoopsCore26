<?php

/**
 * Xlanguage module
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       2010-2014 XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         Xlanguage
 * @since           2.6.0
 * @author          Laurent JEN (Aka DuGris)
 */

class XlanguageLanguageForm extends Xoops\Form\ThemeForm
{
    /**
     * @param XlanguageLanguage|null $obj
     */
    public function __construct(?XlanguageLanguage $obj = null)
    {
        $xoops = Xoops::getInstance();

        parent::__construct('', 'xlanguage_form', $xoops->getEnv('PHP_SELF'), 'post', true, 'horizontal');

        // language name
        $xlanguageSelect = new Xoops\Form\SelectLocale(
            _AM_XLANGUAGE_NAME,
            'xlanguage_name',
            $obj->getVar('xlanguage_name')
        );
        $this->addElement($xlanguageSelect, true);

        // language description
        $this->addElement(new Xoops\Form\Text(
            _AM_XLANGUAGE_DESCRIPTION,
            'xlanguage_description',
            5,
            30,
            $obj->getVar('xlanguage_description')
        ), true);

        // language charset
        $autoload = XoopsLoad::loadConfig('xlanguage');
        $charset_select = new Xoops\Form\Select(_AM_XLANGUAGE_CHARSET, 'xlanguage_charset', $obj->getVar('xlanguage_charset'));
        $charset_select->addOptionArray($autoload['charset']);
        $this->addElement($charset_select);

        // language code
        $this->addElement(new Xoops\Form\Text(_AM_XLANGUAGE_CODE, 'xlanguage_code', 5, 10, $obj->getVar('xlanguage_code')), true);

        // language weight
        $this->addElement(new Xoops\Form\Text(_AM_XLANGUAGE_WEIGHT, 'xlanguage_weight', 1, 4, $obj->getVar('xlanguage_weight')));

        // language image
        $image_option_tray = new Xoops\Form\ElementTray(_AM_XLANGUAGE_IMAGE, '');
        $flagPath = 'media/xoops/images/flags/' . \Xoops\Module\Helper::getHelper('xlanguage')->getConfig('theme') . '/';
        $image_select = new Xoops\Form\Select('', 'xlanguage_image', $obj->getVar('xlanguage_image'));
        \Xoops\Core\Lists\ImageFile::setOptionsArray($image_select, $xoops->path($flagPath));
        $image_select->setExtra("onchange='showImgSelected(\"image\", \"xlanguage_image\", \"/media/xoops/images/flags/" . \Xoops\Module\Helper::getHelper('xlanguage')->getConfig('theme') . "/\", \"\", \"" . \XoopsBaseConfig::get('url') . "\")'");
        $image_tray = new Xoops\Form\ElementTray('', '&nbsp;');
        $image_tray->addElement($image_select);
        $image_tray->addElement(new Xoops\Form\Label('', "<div style='padding: 8px;'><img style='width:24px; height:24px; ' src='" . \XoopsBaseConfig::get('url') . "/media/xoops/images/flags/" . \Xoops\Module\Helper::getHelper('xlanguage')->getConfig('theme') . "/" . $obj->getVar("xlanguage_image") . "' name='image' id='image' alt='' /></div>"));
        $image_option_tray->addElement($image_tray);
        $this->addElement($image_option_tray);

        $this->addElement(new Xoops\Form\Hidden('xlanguage_id', $obj->getVar('xlanguage_id')));

        /**
         * Buttons
         */
        $buttonTray = new Xoops\Form\ElementTray('', '');
        $buttonTray->addElement(new Xoops\Form\Hidden('op', 'save'));

        $buttonSubmit = new Xoops\Form\Button('', 'submit', XoopsLocale::A_SUBMIT, 'submit');
        $buttonSubmit->setClass('btn btn-success');
        $buttonTray->addElement($buttonSubmit);

        $buttonReset = new Xoops\Form\Button('', 'reset', XoopsLocale::A_RESET, 'reset');
        $buttonReset->setClass('btn btn-warning');
        $buttonTray->addElement($buttonReset);

        switch (basename($xoops->getEnv('PHP_SELF'), '.php')) {
            case 'xoops_xlanguage':
                $buttonCancel = new Xoops\Form\Button('', 'button', XoopsLocale::A_CLOSE, 'button');
                $buttonCancel->setExtra('onclick="tinyMCEPopup.close();"');
                $buttonCancel->setClass('btn btn-danger');
                $buttonTray->addElement($buttonCancel);
                break;

            case 'index':
            default:
                $buttonCancel = new Xoops\Form\Button('', 'cancel', XoopsLocale::A_CANCEL, 'button');
                $buttonCancel->setExtra("onclick='javascript:history.go(-1);'");
                $buttonCancel->setClass('btn btn-danger');
                $buttonTray->addElement($buttonCancel);
                break;
        }

        $this->addElement($buttonTray);
    }
}
