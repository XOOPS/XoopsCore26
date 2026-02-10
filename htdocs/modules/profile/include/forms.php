<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xoops\Core\Kernel\Handlers\XoopsUser;

/**
 * Extended User Profile
 *
 * @copyright       2000-2016 XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         profile
 * @since           2.3.0
 * @author          Jan Pedersen
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 */

/**
 * Get {@link Xoops\Form\ThemeForm} for registering new users
 *
 * @param XoopsUser $user
 * @param $profile
 * @param null $step
 * @return Xoops\Form\ThemeForm
 */
function profile_getRegisterForm(XoopsUser $user, $profile, $step = null)
{
    $xoops = Xoops::getInstance();
    $action = $_SERVER['REQUEST_URI'];
    $step_no = $step['step_no'];
    $use_token = $step['step_no'] > 0 ? true : false;
    $reg_form = new Xoops\Form\ThemeForm($step['step_name'], 'regform', $action, 'post', $use_token);

    if ($step['step_desc']) {
        $reg_form->addElement(new Xoops\Form\Label('', $step['step_desc']));
    }

    if ($step_no == 1) {
        //$uname_size = $GLOBALS['xoopsConfigUser']['maxuname'] < 35 ? $GLOBALS['xoopsConfigUser']['maxuname'] : 35;

        $elements[0][] = array(
            'element' => new Xoops\Form\Text(XoopsLocale::USERNAME, 'uname', 40, $xoops->getConfig('maxuname'), $user->getVar('uname', 'e')),
            'required' => true
        );
        $weights[0][] = 0;

        $elements[0][] = array(
            'element' => new Xoops\Form\Text(XoopsLocale::EMAIL, 'email', 40, 160, $user->getVar('email', 'e')), 'required' => true
        );
        $weights[0][] = 0;

        $elements[0][] =
            array('element' => new Xoops\Form\Password(XoopsLocale::PASSWORD, 'pass'), 'required' => true);
        $weights[0][] = 0;

        $elements[0][] =
            array('element' => new Xoops\Form\Password(XoopsLocale::VERIFY_PASSWORD, 'vpass'), 'required' => true);
        $weights[0][] = 0;
    }

    // Dynamic fields
    /* @var $profile_handler ProfileProfileHandler */
    $profile_handler = \Xoops::getModuleHelper('profile')->getHandler('profile');
    $fields = $profile_handler->loadFields();
    $_SESSION['profile_required'] = array();
    $weights = array();
    /* @var ProfileField $field */
    foreach ($fields as $field) {
        if ($field->getVar('step_id') == $step['step_id']) {
            $fieldinfo['element'] = $field->getEditElement($user, $profile);
            //assign and check (=)
            if ($fieldinfo['required'] = $field->getVar('field_required')) {
                $_SESSION['profile_required'][$field->getVar('field_name')] = $field->getVar('field_title');
            }

            $key = $field->getVar('cat_id');
            $elements[$key][] = $fieldinfo;
            $weights[$key][] = $field->getVar('field_weight');
        }
    }
    ksort($elements);

    foreach (array_keys($elements) as $k) {
        array_multisort($weights[$k], SORT_ASC, array_keys($elements[$k]), SORT_ASC, $elements[$k]);
        foreach (array_keys($elements[$k]) as $i) {
            $reg_form->addElement($elements[$k][$i]['element'], $elements[$k][$i]['required']);
        }
    }
    //end of Dynamic User fields
    $myts = \Xoops\Core\Text\Sanitizer::getInstance();
    if ($step_no == 1 && $xoops->getConfig('reg_dispdsclmr') != 0 && $xoops->getConfig('reg_disclaimer') != '') {
        $disc_tray = new Xoops\Form\ElementTray(XoopsLocale::DISCLAIMER, '<br />');
        $disc_text = new Xoops\Form\Label("", "<div class=\"pad5\">" . $myts->displayTarea($xoops->getConfig('reg_disclaimer'), 1) . "</div>");
        $disc_tray->addElement($disc_text);
        $agree_chk = new Xoops\Form\Checkbox('', 'agree_disc');
        $agree_chk->addOption(1, XoopsLocale::I_AGREE_TO_THE_ABOVE);
        $disc_tray->addElement($agree_chk);
        $reg_form->addElement($disc_tray);
    }

    if ($step_no == 1) {
        $reg_form->addElement(new Xoops\Form\Captcha(), true);
    }

    $reg_form->addElement(new Xoops\Form\Hidden('uid', $user->getVar('uid')));
    $reg_form->addElement(new Xoops\Form\Hidden('step', $step_no));
    $reg_form->addElement(new Xoops\Form\Button('', 'submitButton', XoopsLocale::A_SUBMIT, 'submit'));
    return $reg_form;
}


/**
 * Get {@link Xoops\Form\ThemeForm} for editing a user
 *
 * @param XoopsUser $user
 * @param ProfileProfile|null $profile
 * @param bool $action
 * @return Xoops\Form\ThemeForm
 */
function profile_getUserForm(XoopsUser $user, ?ProfileProfile $profile = null, $action = false)
{
    $xoops = Xoops::getInstance();

    if ($action === false) {
        $action = $_SERVER['REQUEST_URI'];
    }

    $title = $user->isNew() ? _PROFILE_AM_ADDUSER : XoopsLocale::EDIT_PROFILE;

    $form = new Xoops\Form\ThemeForm($title, 'userinfo', $action, 'post', true);

    /* @var $profile_handler ProfileProfileHandler */
    $profile_handler = \Xoops::getModuleHelper('profile')->getHandler('profile');
    // Dynamic fields
    if (!$profile) {
        $profile = $profile_handler->getProfile($user->getVar('uid'));
    }
    // Get fields
    $fields = $profile_handler->loadFields();

    // Get ids of fields that can be edited
    $gperm_handler = $xoops->getHandlerGroupPermission();
    $editable_fields = $gperm_handler->getItemIds('profile_edit', $xoops->user->getGroups(), $xoops->module->getVar('mid'));

    if ($user->isNew() || $xoops->user->isAdmin()) {
        $elements[0][] = array(
            'element' => new Xoops\Form\Text(XoopsLocale::USERNAME, 'uname', 40, $xoops->user->isAdmin() ? 60
                    : $xoops->getConfig('maxuname'), $user->getVar('uname', 'e')), 'required' => 1
        );
        $email_text = new Xoops\Form\Text('', 'email', 40, 160, $user->getVar('email'));
    } else {
        $elements[0][] = array('element' => new Xoops\Form\Label(XoopsLocale::USERNAME, $user->getVar('uname')), 'required' => 0);
        $email_text = new Xoops\Form\Label('', $user->getVar('email'));
    }
    $email_tray = new Xoops\Form\ElementTray(XoopsLocale::EMAIL, '<br />');
    $email_tray->addElement($email_text, ($user->isNew() || $xoops->user->isAdmin()) ? 1 : 0);
    $weights[0][] = 0;
    $elements[0][] = array('element' => $email_tray, 'required' => 0);
    $weights[0][] = 0;

    if ($xoops->user->isAdmin() && $user->getVar('uid') != $xoops->user->getVar('uid')) {
        //If the user is an admin and is editing someone else
        $pwd_text = new Xoops\Form\Password('', 'password');
        $pwd_text2 = new Xoops\Form\Password('', 'vpass');
        $pwd_tray = new Xoops\Form\ElementTray(XoopsLocale::PASSWORD . '<br />' . XoopsLocale::TYPE_NEW_PASSWORD_TWICE_TO_CHANGE_IT);
        $pwd_tray->addElement($pwd_text);
        $pwd_tray->addElement($pwd_text2);
        $elements[0][] = array('element' => $pwd_tray, 'required' => 0); //cannot set an element tray required
        $weights[0][] = 0;

        $level_radio = new Xoops\Form\Radio(_PROFILE_MA_USERLEVEL, 'level', $user->getVar('level'));
        $level_radio->addOption(1, _PROFILE_MA_ACTIVE);
        $level_radio->addOption(0, _PROFILE_MA_INACTIVE);
        //$level_radio->addOption(-1, _PROFILE_MA_DISABLED);
        $elements[0][] = array('element' => $level_radio, 'required' => 0);
        $weights[0][] = 0;
    }

    $elements[0][] = array('element' => new Xoops\Form\Hidden('uid', $user->getVar('uid')), 'required' => 0);
    $weights[0][] = 0;
    $elements[0][] = array('element' => new Xoops\Form\Hidden('op', 'save'), 'required' => 0);
    $weights[0][] = 0;

    $cat_handler = \Xoops::getModuleHelper('profile')->getHandler('category');
    $categories = array();
    $all_categories = $cat_handler->getObjects(null, true, false);
    $count_fields = count($fields);
    /* @var ProfileField $field */
    foreach ($fields as $field) {
        if (in_array($field->getVar('field_id'), $editable_fields)) {
            // Set default value for user fields if available
            if ($user->isNew()) {
                $default = $field->getVar('field_default');
                if ($default !== '' && $default !== null) {
                    $user->setVar($field->getVar('field_name'), $default);
                }
            }

            if ($profile->getVar($field->getVar('field_name'), 'n') === null) {
                $default = $field->getVar('field_default', 'n');
                $profile->setVar($field->getVar('field_name'), $default);
            }

            $fieldinfo['element'] = $field->getEditElement($user, $profile);
            $fieldinfo['required'] = $field->getVar('field_required');

            $key = @$all_categories[$field->getVar('cat_id')]['cat_weight'] * $count_fields + $field->getVar('cat_id');
            $elements[$key][] = $fieldinfo;
            $weights[$key][] = $field->getVar('field_weight');
            $categories[$key] = @$all_categories[$field->getVar('cat_id')];
        }
    }

    if ($xoops->isUser() && $xoops->user->isAdmin()) {
        $xoops->loadLanguage('admin', 'profile');
        $gperm_handler = $xoops->getHandlerGroupPermission();
        //If user has admin rights on groups
        include_once $xoops->path('modules/system/constants.php');
        if ($gperm_handler->checkRight('system_admin', XOOPS_SYSTEM_GROUP, $xoops->user->getGroups(), 1)) {
            //add group selection
            $group_select = new Xoops\Form\SelectGroup(XoopsLocale::USER_GROUPS, 'groups', false, $user->getGroups(), 5, true);
            $elements[0][] = array('element' => $group_select, 'required' => 0);
            //set as latest;
            $weights[0][] = $count_fields + 1;
        }
    }

    ksort($elements);
    foreach (array_keys($elements) as $k) {
        array_multisort($weights[$k], SORT_ASC, array_keys($elements[$k]), SORT_ASC, $elements[$k]);
        $title = isset($categories[$k]) ? $categories[$k]['cat_title'] : _PROFILE_MA_DEFAULT;
        $desc = isset($categories[$k]) ? $categories[$k]['cat_description'] : "";
        //$form->addElement(new Xoops\Form\Label("<div class='break'>{$title}</div>", $desc), false);
        $desc = ($desc != '' ? ' - ' . $desc : '');
        $form->insertBreak($title . $desc);
        foreach (array_keys($elements[$k]) as $i) {
            $form->addElement($elements[$k][$i]['element'], $elements[$k][$i]['required']);
        }
    }

    $form->addElement(new Xoops\Form\Hidden('uid', $user->getVar('uid')));
    $form->addElement(new Xoops\Form\Button('', 'submit', XoopsLocale::SAVE_CHANGES, 'submit'));
    return $form;
}
