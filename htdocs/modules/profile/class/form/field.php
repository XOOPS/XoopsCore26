<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xmf\Security\Format;
use Xmf\Security\Serializer;
use Xoops\Core\Kernel\Dtype;

/**
 * @copyright       2000-2016 XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author          trabis <lusopoemas@gmail.com>
 */

class ProfileFieldForm extends Xoops\Form\ThemeForm
{
    /**
     * @param ProfileField|XoopsObject $obj
     */
    public function __construct(ProfileField $obj)
    {
        $xoops = Xoops::getInstance();

        $title = $obj->isNew() ? sprintf(_PROFILE_AM_ADD, _PROFILE_AM_FIELD)
                : sprintf(_PROFILE_AM_EDIT, _PROFILE_AM_FIELD);

        parent::__construct($title, 'form', '', 'post', true);

        $this->addElement(new Xoops\Form\Text(_PROFILE_AM_TITLE, 'field_title', 5, 255, $obj->getVar('field_title', 'e')), true);
        $this->addElement(new Xoops\Form\TextArea(_PROFILE_AM_DESCRIPTION, 'field_description', $obj->getVar('field_description', 'e'), 5, 5));

        if (!$obj->isNew()) {
            $fieldcat_id = $obj->getVar('cat_id');
        } else {
            $fieldcat_id = 0;
        }
        $category_handler = \Xoops::getModuleHelper('profile')->getHandler('category');
        $cat_select = new Xoops\Form\Select(_PROFILE_AM_CATEGORY, 'field_category', $fieldcat_id);
        $cat_select->addOption(0, _PROFILE_AM_DEFAULT);
        $cat_select->addOptionArray($category_handler->getList());
        $this->addElement($cat_select);
        $weight = new Xoops\Form\Text(_PROFILE_AM_WEIGHT, 'field_weight', 1, 10, $obj->getVar('field_weight', 'e'), '');
        $weight->setPattern('^\d+$', _PROFILE_AM_ERROR_WEIGHT);
        $this->addElement($weight, true);
        if ($obj->getVar('field_config') || $obj->isNew()) {
            if (!$obj->isNew()) {
                $this->addElement(new Xoops\Form\Label(_PROFILE_AM_NAME, $obj->getVar('field_name')));
                $this->addElement(new Xoops\Form\Hidden('id', $obj->getVar('field_id')));
            } else {
                $this->addElement(new Xoops\Form\Text(_PROFILE_AM_NAME, 'field_name', 5, 255, $obj->getVar('field_name', 'e')), true);
            }

            //autotext and theme left out of this one as fields of that type should never be changed (valid assumption, I think)
            $fieldtypes = array(
                'checkbox' => _PROFILE_AM_CHECKBOX, 'date' => _PROFILE_AM_DATE, 'datetime' => _PROFILE_AM_DATETIME,
                'longdate' => _PROFILE_AM_LONGDATE, 'group' => _PROFILE_AM_GROUP,
                'group_multi' => _PROFILE_AM_GROUPMULTI, 'language' => _PROFILE_AM_LANGUAGE,
                'radio' => _PROFILE_AM_RADIO, 'select' => _PROFILE_AM_SELECT, 'select_multi' => _PROFILE_AM_SELECTMULTI,
                'textarea' => _PROFILE_AM_TEXTAREA, 'dhtml' => _PROFILE_AM_DHTMLTEXTAREA,
                'textbox' => _PROFILE_AM_TEXTBOX, 'timezone' => _PROFILE_AM_TIMEZONE, 'yesno' => _PROFILE_AM_YESNO
            );

            $element_select = new Xoops\Form\Select(_PROFILE_AM_TYPE, 'field_type', $obj->getVar('field_type', 'e'));
            $element_select->addOptionArray($fieldtypes);

            $this->addElement($element_select);

            switch ($obj->getVar('field_type')) {
                case "textbox":
                    $valuetypes = array(
                        Dtype::TYPE_ARRAY => _PROFILE_AM_ARRAY,
                        Dtype::TYPE_EMAIL => _PROFILE_AM_EMAIL,
                        Dtype::TYPE_INTEGER => _PROFILE_AM_INT,
                        Dtype::TYPE_FLOAT => _PROFILE_AM_FLOAT,
                        Dtype::TYPE_DECIMAL => _PROFILE_AM_DECIMAL,
                        Dtype::TYPE_TEXT_AREA => _PROFILE_AM_TXTAREA,
                        Dtype::TYPE_TEXT_BOX => _PROFILE_AM_TXTBOX,
                        Dtype::TYPE__URL => _PROFILE_AM_URL,
                        Dtype::TYPE_OTHER => _PROFILE_AM_OTHER
                    );

                    $type_select = new Xoops\Form\Select(_PROFILE_AM_VALUETYPE, 'field_valuetype', $obj->getVar('field_valuetype', 'e'), 5, 5);
                    $type_select->addOptionArray($valuetypes);
                    $this->addElement($type_select);
                    break;

                case "select":
                case "radio":
                    $valuetypes = array(
                        Dtype::TYPE__ARRAY => _PROFILE_AM_ARRAY,
                        Dtype::TYPE__EMAIL => _PROFILE_AM_EMAIL,
                        Dtype::TYPE_INTEGER => _PROFILE_AM_INT,
                        Dtype::TYPE_FLOAT => _PROFILE_AM_FLOAT,
                        Dtype::TYPE_DECIMAL => _PROFILE_AM_DECIMAL,
                        Dtype::TYPE_TEXT_AREA => _PROFILE_AM_TXTAREA,
                        Dtype::TYPE_TEXT_BOX => _PROFILE_AM_TXTBOX,
                        Dtype::TYPE_URL => _PROFILE_AM_URL,
                        Dtype::TYPE_OTHER => _PROFILE_AM_OTHER
                    );

                    $type_select = new Xoops\Form\Select(_PROFILE_AM_VALUETYPE, 'field_valuetype', $obj->getVar('field_valuetype', 'e'));
                    $type_select->addOptionArray($valuetypes);
                    $this->addElement($type_select);
                    break;
            }

            //$this->addElement(new Xoops\Form\RadioYesNo(_PROFILE_AM_NOTNULL, 'field_notnull', $obj->getVar('field_notnull', 'e') ));

            if ($obj->getVar('field_type') === "select" || $obj->getVar('field_type') === "select_multi" || $obj->getVar('field_type') === "radio" || $obj->getVar('field_type') === "checkbox") {
                $options = $obj->getVar('field_options');
                if (count($options) > 0) {
                    $remove_options = new Xoops\Form\Checkbox(_PROFILE_AM_REMOVEOPTIONS, 'removeOptions');
                    //$remove_options->columns = 3;
                    asort($options);
                    foreach (array_keys($options) as $key) {
                        $options[$key] .= "[{$key}]";
                    }
                    $remove_options->addOptionArray($options);
                    $this->addElement($remove_options);
                }

                $option_text = "<table  cellspacing='1'><tr><td class='width20'>" . _PROFILE_AM_KEY . "</td><td>" . _PROFILE_AM_VALUE . "</td></tr>";
                for ($i = 0; $i < 3; ++$i) {
                    $option_text .= "<tr><td><input type='text' name='addOption[{$i}][key]' id='addOption[{$i}][key]' size='15' /></td><td><input type='text' name='addOption[{$i}][value]' id='addOption[{$i}][value]' size='35' /></td></tr>";
                    $option_text .= "<tr height='3px'><td colspan='2'> </td></tr>";
                }
                $option_text .= "</table>";
                $this->addElement(new Xoops\Form\Label(_PROFILE_AM_ADDOPTION, $option_text));
            }
        }

        if ($obj->getVar('field_edit')) {
            switch ($obj->getVar('field_type')) {
                case "textbox":
                case "textarea":
                case "dhtml":
                    $this->addElement(new Xoops\Form\Text(_PROFILE_AM_MAXLENGTH, 'field_maxlength', 5, 5, $obj->getVar('field_maxlength', 'e')));
                    $this->addElement(new Xoops\Form\TextArea(_PROFILE_AM_DEFAULT, 'field_default', $obj->getVar('field_default', 'e')));
                    break;

                case "checkbox":
                case "select_multi":
                    $def_value = $obj->getVar('field_default', 'e') != null
                            ? Serializer::tryFrom((string) $obj->getVar('field_default', 'n'), false, Format::PHP) : null;
                    $element = new Xoops\Form\Select(_PROFILE_AM_DEFAULT, 'field_default', $def_value, 8, true);
                    $options = $obj->getVar('field_options');
                    asort($options);
                    // If options do not include an empty element, then add a blank option to prevent any default selection
                    if (!in_array('', array_keys($options))) {
                        $element->addOption('', XoopsLocale::NONE);
                    }
                    $element->addOptionArray($options);
                    $this->addElement($element);
                    break;

                case "select":
                case "radio":
                    $def_value = $obj->getVar('field_default', 'e') != null ? $obj->getVar('field_default') : null;
                    $element = new Xoops\Form\Select(_PROFILE_AM_DEFAULT, 'field_default', $def_value);
                    $options = $obj->getVar('field_options');
                    asort($options);
                    // If options do not include an empty element, then add a blank option to prevent any default selection
                    if (!in_array('', array_keys($options))) {
                        $element->addOption('', XoopsLocale::NONE);
                    }
                    $element->addOptionArray($options);
                    $this->addElement($element);
                    break;

                case "date":
                    $this->addElement(new Xoops\Form\DateSelect(_PROFILE_AM_DEFAULT, 'field_default', $obj->getVar('field_default', 'e')));
                    break;

                case "longdate":
                    $this->addElement(new Xoops\Form\DateSelect(_PROFILE_AM_DEFAULT, 'field_default', strtotime($obj->getVar('field_default', 'e'))));
                    break;

                case "datetime":
                    $this->addElement(new Xoops\Form\DateTimeSelect(_PROFILE_AM_DEFAULT, 'field_default', $obj->getVar('field_default', 'e')));
                    break;

                case "yesno":
                    $this->addElement(new Xoops\Form\RadioYesNo(_PROFILE_AM_DEFAULT, 'field_default', $obj->getVar('field_default', 'e')));
                    break;

                case "timezone":
                    $this->addElement(new Xoops\Form\SelectTimeZone(_PROFILE_AM_DEFAULT, 'field_default', $obj->getVar('field_default', 'e')));
                    break;

                case "language":
                    $this->addElement(new Xoops\Form\SelectLanguage(_PROFILE_AM_DEFAULT, 'field_default', $obj->getVar('field_default', 'e')));
                    break;

                case "group":
                    $this->addElement(new Xoops\Form\SelectGroup(_PROFILE_AM_DEFAULT, 'field_default', true, $obj->getVar('field_default', 'e')));
                    break;

                case "group_multi":
                    $this->addElement(new Xoops\Form\SelectGroup(_PROFILE_AM_DEFAULT, 'field_default', true, Serializer::tryFrom((string) $obj->getVar('field_default', 'n'), false, Format::PHP), 5, true));
                    break;

                case "theme":
                    $this->addElement(new Xoops\Form\SelectTheme(_PROFILE_AM_DEFAULT, 'field_default', $obj->getVar('field_default', 'e')));
                    break;

                case "autotext":
                    $this->addElement(new Xoops\Form\TextArea(_PROFILE_AM_DEFAULT, 'field_default', $obj->getVar('field_default', 'e')));
                    break;
            }
        }

        $groupperm_handler = $xoops->getHandlerGroupPermission();
        $searchable_types = array(
            'textbox', 'select', 'radio', 'yesno', 'date', 'datetime', 'timezone', 'language'
        );
        if (in_array($obj->getVar('field_type'), $searchable_types)) {
            $search_groups = $groupperm_handler->getGroupIds('profile_search', $obj->getVar('field_id'), $xoops->module->getVar('mid'));
            $this->addElement(new Xoops\Form\SelectGroup(_PROFILE_AM_PROF_SEARCH, 'profile_search', true, $search_groups, 5, true));
        }
        if ($obj->getVar('field_edit') || $obj->isNew()) {
            if (!$obj->isNew()) {
                //Load groups
                $editable_groups = $groupperm_handler->getGroupIds('profile_edit', $obj->getVar('field_id'), $xoops->module->getVar('mid'));
            } else {
                $editable_groups = array();
            }
            $this->addElement(new Xoops\Form\SelectGroup(_PROFILE_AM_PROF_EDITABLE, 'profile_edit', false, $editable_groups, 5, true));
            $this->addElement(new Xoops\Form\RadioYesNo(_PROFILE_AM_REQUIRED, 'field_required', $obj->getVar('field_required', 'e')));
            $regstep_select = new Xoops\Form\Select(_PROFILE_AM_PROF_REGISTER, 'step_id', $obj->getVar('step_id', 'e'));
            $regstep_select->addOption(0, XoopsLocale::NO);
            $regstep_handler = \Xoops::getModuleHelper('profile')->getHandler('regstep');
            $regstep_select->addOptionArray($regstep_handler->getList());
            $this->addElement($regstep_select);
        }
        $this->addElement(new Xoops\Form\Hidden('op', 'save'));
        $this->addElement(new Xoops\Form\Button('', 'submit', XoopsLocale::A_SUBMIT, 'submit'));
    }
}
