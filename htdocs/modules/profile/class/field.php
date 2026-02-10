<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xoops\Core\Database\Connection;
use Xoops\Core\Kernel\Dtype;
use Xoops\Core\Kernel\Handlers\XoopsUser;
use Xoops\Core\Kernel\XoopsObject;
use Xoops\Core\Kernel\XoopsPersistableObjectHandler;

/**
 * Extended User Profile
 *
 * @package         Profile
 * @author          Jan Pedersen
 * @author          Taiwen Jiang <phppp@users.sourceforge.net>
 * @copyright       2000-2016 XOOPS Project (www.xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @since           2.3.0
 */
class ProfileField extends XoopsObject
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initVar('field_id', Dtype::TYPE_INTEGER, null);
        $this->initVar('cat_id', Dtype::TYPE_INTEGER, null, true);
        $this->initVar('field_type', Dtype::TYPE_TEXT_BOX);
        $this->initVar('field_valuetype', Dtype::TYPE_INTEGER, null, true);
        $this->initVar('field_name', Dtype::TYPE_TEXT_BOX, null, true);
        $this->initVar('field_title', Dtype::TYPE_TEXT_BOX);
        $this->initVar('field_description', Dtype::TYPE_TEXT_AREA);
        $this->initVar('field_required', Dtype::TYPE_INTEGER, 0); //0 = no, 1 = yes
        $this->initVar('field_maxlength', Dtype::TYPE_INTEGER, 0);
        $this->initVar('field_weight', Dtype::TYPE_INTEGER, 0);
        $this->initVar('field_default', Dtype::TYPE_TEXT_AREA, "");
        $this->initVar('field_notnull', Dtype::TYPE_INTEGER, 1);
        $this->initVar('field_edit', Dtype::TYPE_INTEGER, 0);
        $this->initVar('field_show', Dtype::TYPE_INTEGER, 0);
        $this->initVar('field_config', Dtype::TYPE_INTEGER, 0);
        $this->initVar('field_options', Dtype::TYPE_ARRAY, array());
        $this->initVar('step_id', Dtype::TYPE_INTEGER, 0);
    }

    /**
     * Extra treatment dealing with non latin encoding
     * Tricky solution
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     *
     * @todo evaluate removing this. New considerations: full UTF-8 system, new Dtype::TYPE_JSON
     */
    public function setVar($key, $value)
    {
        if ($key === 'field_options' && is_array($value)) {
            foreach (array_keys($value) as $idx) {
                $value[$idx] = base64_encode($value[$idx]);
            }
        }
        parent::setVar($key, $value);
    }

    /**
     * @param string $key
     * @param string $format
     * @return array|mixed
     */
    public function getVar($key, $format = 's')
    {
        $value = parent::getVar($key, $format);
        if ($key === 'field_options' && !empty($value)) {
            foreach (array_keys($value) as $idx) {
                $value[$idx] = base64_decode($value[$idx]);
            }
        }
        return $value;
    }

    /**
     * Returns a {@link Xoops\Form\Element} for editing the value of this field
     *
     * @param XoopsUser $user {@link XoopsUser} object to edit the value of
     * @param ProfileProfile $profile {@link ProfileProfile} object to edit the value of
     *
     * @return Xoops\Form\Element
     **/
    public function getEditElement(XoopsUser $user, ProfileProfile $profile)
    {
        $xoops = Xoops::getInstance();
        $value = in_array($this->getVar('field_name'), $this->getUserVars())
                ? $user->getVar($this->getVar('field_name'), 'e') : $profile->getVar($this->getVar('field_name'), 'e');

        $caption = $this->getVar('field_title');
        $caption = defined($caption) ? constant($caption) : $caption;
        $name = $this->getVar('field_name', 'e');
        $options = $this->getVar('field_options');
        if (is_array($options)) {
            //asort($options);

            foreach (array_keys($options) as $key) {
                $optval = defined($options[$key]) ? constant($options[$key]) : $options[$key];
                $optkey = defined($key) ? constant($key) : $key;
                unset($options[$key]);
                $options[$optkey] = $optval;
            }
        }
        switch ($this->getVar('field_type')) {
            default:
            case "autotext":
                //autotext is not for editing
                $element = new Xoops\Form\Label($caption, $this->getOutputValue($user, $profile));
                break;

            case "textbox":
                $element = new Xoops\Form\Text($caption, $name, 35, $this->getVar('field_maxlength'), $value);
                break;

            case "textarea":
                $element = new Xoops\Form\TextArea($caption, $name, $value, 4, 30);
                break;

            case "dhtml":
                $element = new Xoops\Form\DhtmlTextArea($caption, $name, $value, 10, 30);
                break;

            case "select":
                $element = new Xoops\Form\Select($caption, $name, $value);
                // If options do not include an empty element, then add a blank option to prevent any default selection
                if (!in_array('', array_keys($options))) {
                    $element->addOption('', XoopsLocale::NONE);

                    $eltmsg = empty($caption) ? sprintf(XoopsLocale::F_ENTER, $name) : sprintf(XoopsLocale::F_ENTER, $caption);
                    $eltmsg = str_replace('"', '\"', stripslashes($eltmsg));
                    $element->addCustomValidationCode("\nvar hasSelected = false; var selectBox = myform.{$name};" . "for (i = 0; i < selectBox.options.length; i++  ) { if ( selectBox.options[i].selected == true && selectBox.options[i].value != '' ) { hasSelected = true; break; } }" . "if ( !hasSelected ) { window.alert(\"{$eltmsg}\"); selectBox.focus(); return false; }");
                }
                $element->addOptionArray($options);
                break;

            case "select_multi":
                $element = new Xoops\Form\Select($caption, $name, $value, 5, true);
                $element->addOptionArray($options);
                break;

            case "radio":
                $element = new Xoops\Form\Radio($caption, $name, $value);
                $element->addOptionArray($options);
                break;

            case "checkbox":
                $element = new Xoops\Form\Checkbox($caption, $name, $value);
                $element->addOptionArray($options);
                break;

            case "yesno":
                $element = new Xoops\Form\RadioYesNo($caption, $name, $value);
                break;

            case "group":
                $element = new Xoops\Form\SelectGroup($caption, $name, true, $value);
                break;

            case "group_multi":
                $element = new Xoops\Form\SelectGroup($caption, $name, true, $value, 5, true);
                break;

            case "language":
                $element = new Xoops\Form\SelectLanguage($caption, $name, $value);
                break;

            case "date":
                $element = new Xoops\Form\DateSelect($caption, $name, $value);
                break;

            case "longdate":
                $element = new Xoops\Form\DateSelect($caption, $name, str_replace("-", "/", $value));
                break;

            case "datetime":
                $element = new Xoops\Form\DateTimeSelect($caption, $name, $value);
                break;

            case "timezone":
                $element = new Xoops\Form\SelectTimeZone($caption, $name, $value);
                break;

            case "rank":
                $ranklist = $xoops->service('userrank')->getAssignableUserRankList()->getValue();
                if ($ranklist !== null) {
                    $element = new Xoops\Form\Select($caption, $name, $value);
                    $element->addOption(0, "--------------");
                    $element->addOptionArray($ranklist);
                } else {
                    $element = new Xoops\Form\Hidden($name, $value);
                }
                break;

            case 'theme':
                $element = new Xoops\Form\Select($caption, $name, $value);
                $element->addOption("0", _PROFILE_MA_SITEDEFAULT);
                $handle = opendir(\XoopsBaseConfig::get('themes-path') . '/');
                $dirlist = array();
                while (false !== ($file = readdir($handle))) {
                    if (is_dir(\XoopsBaseConfig::get('themes-path') . '/' . $file) && !preg_match("/^[.]{1,2}$/", $file) && strtolower($file) !== 'cvs') {
                        if (XoopsLoad::fileExists(\XoopsBaseConfig::get('themes-path') . "/" . $file . "/theme.tpl") && in_array($file, $xoops->getConfig('theme_set_allowed'))) {
                            $dirlist[$file] = $file;
                        }
                    }
                }
                closedir($handle);
                if (!empty($dirlist)) {
                    asort($dirlist);
                    $element->addOptionArray($dirlist);
                }
                break;
        }
        if ($this->getVar('field_description') != "") {
            $element->setDescription($this->getVar('field_description'));
        }
        return $element;
    }

    /**
     * Returns a value for output of this field
     *
     * @param XoopsUser $user {@link XoopsUser} object to get the value of
     * @param profileProfile $profile object to get the value of
     *
     * @return string
     **/
    public function getOutputValue(XoopsUser $user, ProfileProfile $profile)
    {
        $xoops = Xoops::getInstance();
        $xoops->loadLanguage('modinfo', 'profile');

        $value = in_array($this->getVar('field_name'), $this->getUserVars())
                ? $user->getVar($this->getVar('field_name')) : $profile->getVar($this->getVar('field_name'));

        switch ($this->getVar('field_type')) {
            default:
            case "textbox":
                if ($this->getVar('field_name') === 'url' && $value != '') {
                    return '<a href="' . $xoops->formatURL($value) . '" rel="external">' . $value . '</a>';
                } else {
                    return $value;
                }
                break;
            case "textarea":
            case "dhtml":
            case 'theme':
            case "language":
            case "list":
                return $value;
                break;

            case "select":
            case "radio":
                $options = $this->getVar('field_options');
                if (isset($options[$value])) {
                    $value = htmlspecialchars(
                        defined($options[$value]) ? constant($options[$value]) : $options[$value]
                    );
                } else {
                    $value = "";
                }
                return $value;
                break;

            case "select_multi":
            case "checkbox":
                $options = $this->getVar('field_options');
                $ret = array();
                if (count($options) > 0) {
                    foreach (array_keys($options) as $key) {
                        if (in_array($key, $value)) {
                            $ret[$key] = htmlspecialchars(
                                defined($options[$key]) ? constant($options[$key]) : $options[$key]
                            );
                        }
                    }
                }
                return $ret;
                break;

            case "group":
                $member_handler = $xoops->getHandlerMember();
                $options = $member_handler->getGroupList();
                $ret = isset($options[$value]) ? $options[$value] : '';
                return $ret;
                break;

            case "group_multi":
                $member_handler = $xoops->getHandlerMember();
                $options = $member_handler->getGroupList();
                $ret = array();
                foreach (array_keys($options) as $key) {
                    if (in_array($key, $value)) {
                        $ret[$key] = htmlspecialchars($options[$key]);
                    }
                }
                return $ret;
                break;

            case "longdate":
                //return YYYY/MM/DD format - not optimal as it is not using local date format, but how do we do that
                //when we cannot convert it to a UNIX timestamp?
                return str_replace("-", "/", $value);

            case "date":
                return XoopsLocale::formatTimestamp($value, 's');
                break;

            case "datetime":
                if (!empty($value)) {
                    return XoopsLocale::formatTimestamp($value, 'm');
                } else {
                    return _PROFILE_MI_NEVER_LOGGED_IN;
                }
                break;

            case "autotext":
                $value = $user->getVar($this->getVar('field_name'), 'n'); //autotext can have HTML in it
                $value = str_replace("{X_UID}", $user->getVar("uid"), $value);
                $value = str_replace("{X_URL}", \XoopsBaseConfig::get('url'), $value);
                $value = str_replace("{X_UNAME}", $user->getVar("uname"), $value);
                return $value;
                break;

            case "rank":
                $userrank = $user->rank();
                $user_rankimage = "";
                if (isset($userrank['image']) && $userrank['image'] != "") {
                    $user_rankimage = '<img src="' . $userrank['image'] . '" alt="' . $userrank['title'] . '" /><br />';
                }
                return $user_rankimage . $userrank['title'];
                break;

            case "yesno":
                return $value ? XoopsLocale::YES : XoopsLocale::NO;
                break;

            case "timezone":
                return $value->getName();
                break;
        }
    }

    /**
     * Returns a value ready to be saved in the database
     *
     * @param mixed $value Value to format
     *
     * @return mixed
     */
    public function getValueForSave($value)
    {
        switch ($this->getVar('field_type')) {
            default:
            case "textbox":
            case "textarea":
            case "dhtml":
            case "yesno":
            case 'theme':
            case "language":
            case "list":
            case "select":
            case "radio":
            case "select_multi":
            case "group":
            case "group_multi":
            case "longdate":
                return $value;

            case "timezone":
                return $value;

            case "checkbox":
                return (array)$value;

            case "date":
                if ($value != "") {
                    return strtotime($value);
                }
                return $value;
                break;

            case "datetime":
                if (!empty($value)) {
                    return strtotime($value['date']) + (int)($value['time']);
                }
                return $value;
                break;
        }
    }

    /**
     * Get names of user variables
     *
     * @return array
     */
    public function getUserVars()
    {
        /* @var $profile_handler ProfileProfileHandler */
        $profile_handler = \Xoops::getModuleHelper('profile')->getHandler('profile');
        return $profile_handler->getUserVars();
    }
}

class ProfileFieldHandler extends XoopsPersistableObjectHandler
{
    /**
     * @param null|Connection $db database
     */
    public function __construct(?Connection $db = null)
    {
        parent::__construct($db, 'profile_field', 'ProfileField', 'field_id', 'field_title');
    }

    /**
     * Read field information from cached storage
     *
     * @param bool   $force_update   read fields from database and not cached storage
     *
     * @return array
     */
    public function loadFields($force_update = false)
    {
        static $fields = array();
        if (!empty($force_update) || count($fields) == 0) {
            $this->table_link = $this->db2->prefix('profile_category');
            $criteria = new Criteria('o.field_id', 0, "!=");
            $criteria->setSort('l.cat_weight ASC, o.field_weight');
            $field_objs = $this->getByLink($criteria, array('o.*'), true, 'cat_id', 'cat_id');
            /* @var ProfileField $field */
            foreach ($field_objs as $field) {
                $fields[$field->getVar('field_name')] = $field;
            }
        }
        return $fields;
    }

    /**
     * save a profile field in the database
     *
     * @param XoopsObject|ProfileField $obj reference to the object
     * @param bool $force whether to force the query execution despite security settings
     * @return bool FALSE if failed, TRUE if already present and unchanged or successful
     */
    public function insertFields(XoopsObject $obj, $force = false)
    {
        $profile_handler = \Xoops::getModuleHelper('profile')->getHandler('profile');
        $obj->setVar('field_name', str_replace(' ', '_', $obj->getVar('field_name')));
        $obj->cleanVars(); //Don't quote
        switch ($obj->getVar('field_type')) {
            case "datetime":
            case "date":
                $obj->setVar('field_valuetype', Dtype::TYPE_INTEGER);
                $obj->setVar('field_maxlength', 10);
                break;

            case "longdate":
                $obj->setVar('field_valuetype', Dtype::TYPE_MEDIUM_TIME);
                break;

            case "yesno":
                $obj->setVar('field_valuetype', Dtype::TYPE_INTEGER);
                $obj->setVar('field_maxlength', 1);
                break;

            case "textbox":
                if ($obj->getVar('field_valuetype') != Dtype::TYPE_INTEGER) {
                    $obj->setVar('field_valuetype', Dtype::TYPE_TEXT_BOX);
                }
                break;

            case "autotext":
                if ($obj->getVar('field_valuetype') != Dtype::TYPE_INTEGER) {
                    $obj->setVar('field_valuetype', Dtype::TYPE_TEXT_AREA);
                }
                break;

            case "group_multi":
            case "select_multi":
            case "checkbox":
                $obj->setVar('field_valuetype', Dtype::TYPE_ARRAY);
                break;

            case "language":
            case "timezone":
            case "theme":
                $obj->setVar('field_valuetype', Dtype::TYPE_TEXT_BOX);
                break;

            case "dhtml":
            case "textarea":
                $obj->setVar('field_valuetype', Dtype::TYPE_TEXT_AREA);
                break;
        }

        if ($obj->getVar('field_valuetype') == "") {
            $obj->setVar('field_valuetype', Dtype::TYPE_TEXT_BOX);
        }

        if (!in_array($obj->getVar('field_name'), $this->getUserVars())) {
            if ($obj->isNew()) {
                //add column to table
                $changetype = "ADD";
            } else {
                //update column information
                $changetype = "CHANGE `" . $obj->getVar('field_name', 'n') . "`";
            }
            $maxlengthstring = $obj->getVar('field_maxlength') > 0 ? "(" . $obj->getVar('field_maxlength') . ")" : "";

            //set type
            switch ($obj->getVar('field_valuetype')) {
                default:
                case Dtype::TYPE_ARRAY:
                case Dtype::TYPE_EMAIL:
                case Dtype::TYPE_TEXT_BOX:
                case Dtype::TYPE_URL:
                    $type = "varchar";
                    // varchars must have a maxlength
                    if (!$maxlengthstring) {
                        //so set it to max if maxlength is not set - or should it fail?
                        $maxlengthstring = "(255)";
                        $obj->setVar('field_maxlength', 255);
                    }
                    break;

                case Dtype::TYPE_INTEGER:
                    $type = "int";
                    break;

                case Dtype::TYPE_DECIMAL:
                    $type = "decimal(14,6)";
                    break;

                case Dtype::TYPE_FLOAT:
                    $type = "float(15,9)";
                    break;

                case Dtype::TYPE_OTHER:
                case Dtype::TYPE_TEXT_AREA:
                    $type = "text";
                    $maxlengthstring = "";
                    break;

                case Dtype::TYPE_MEDIUM_TIME:
                    $type = "date";
                    $maxlengthstring = "";
                    break;
            }

            $sql = "ALTER TABLE `" . $profile_handler->table . "` " . $changetype . " `" . $obj->cleanVars['field_name'] . "` " . $type . $maxlengthstring . ' NULL';
            if ($force) {
                $this->db2->setForce(true);
            }
            if (!$this->db2->query($sql)) {
                return false;
            }
        }

        //change this to also update the cached field information storage
        $obj->setDirty();
        if (!parent::insert($obj, $force)) {
            return false;
        }
        return $obj->getVar('field_id');

    }

    /**
     * delete a profile field from the database
     *
     * @param XoopsObject|ProfileField $obj reference to the object to delete
     * @param bool $force
     * @return bool FALSE if failed.
     **/
    public function deleteFields(XoopsObject $obj, $force = false)
    {
        $xoops = Xoops::getInstance();
        $profile_handler = \Xoops::getModuleHelper('profile')->getHandler('profile');
        // remove column from table
        $sql = "ALTER TABLE " . $profile_handler->table . " DROP `" . $obj->getVar('field_name', 'n') . "`";
        if ($this->db2->query($sql)) {
            //change this to update the cached field information storage
            if (!parent::delete($obj, $force)) {
                return false;
            }

            if ($obj->getVar('field_show') || $obj->getVar('field_edit')) {
                $profile_module = $xoops->getModuleByDirname('profile');
                if (is_object($profile_module)) {
                    // Remove group permissions
                    $groupperm_handler = $xoops->getHandlerGroupPermission();
                    $criteria = new CriteriaCompo(new Criteria('gperm_modid', $profile_module->getVar('mid')));
                    $criteria->add(new Criteria('gperm_itemid', $obj->getVar('field_id')));
                    return $groupperm_handler->deleteAll($criteria);
                }
            }
        }
        return false;
    }

    /**
     * Get array of standard variable names (user table)
     *
     * @return string[]
     */
    public function getUserVars()
    {
        return array(
            'uid', 'uname', 'name', 'email', 'url', 'user_avatar', 'user_regdate', 'user_icq', 'user_from', 'user_sig',
            'user_viewemail', 'actkey', 'user_aim', 'user_yim', 'user_msnm', 'pass', 'posts', 'attachsig', 'rank',
            'level', 'theme', 'timezone', 'last_login', 'umode', 'uorder', 'notify_method', 'notify_mode',
            'user_occ', 'bio', 'user_intrest', 'user_mailok'
        );
    }
}
