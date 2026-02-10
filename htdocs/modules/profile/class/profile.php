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
use Xoops\Core\Kernel\XoopsObject;
use Xoops\Core\Kernel\XoopsPersistableObjectHandler;
use Xoops\Core\Kernel\CriteriaElement;

/**
 * Extended User Profile
 *
 * @author    Taiwen Jiang <phppp@users.sourceforge.net>
 * @author    Jan Pedersen
 * @copyright 2000-2019 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 */
class ProfileProfile extends XoopsObject
{
    /**
     * @var null|ProfileProfileHandler
     */
    public $handler;

    /**
     * @param array $fields
     */
    public function __construct($fields)
    {
        $this->initVar('profile_id', Dtype::TYPE_INTEGER, null, true);
        $this->init($fields);
    }

    /**
     * Initiate variables
     *
     * @param array $fields field information array of {@link ProfileField} objects
     */
    public function init($fields)
    {
        if (is_array($fields) && count($fields) > 0) {
            /* @var ProfileField $field */
            foreach ($fields as $key => $field) {
                $this->initVar($key, $field->getVar('field_valuetype'), $field->getVar('field_default', 'n'), $field->getVar('field_required'), $field->getVar('field_maxlength'));
            }
        }
    }
}

class ProfileProfileHandler extends XoopsPersistableObjectHandler
{
    /**
     * @var bool|ProfileFieldHandler
     */
    private $_fHandler;

    /**
     * Array of {@link ProfileField} objects
     *
     * @var array
     */
    private $_fields = array();

    public function __construct(Connection $db)
    {
        parent::__construct($db, 'profile_profile', 'ProfileProfile', 'profile_id');
        $xoops = Xoops::getInstance();
        $this->_fHandler = \Xoops::getModuleHelper('profile')->getHandler('field');
    }

    /**
     * create a new {@link ProfileProfile}
     *
     * @param bool $isNew Flag the new objects as "new"?
     *
     * @return ProfileProfile {@link ProfileProfile}
     */
    public function create($isNew = true)
    {
        /* @var $obj ProfileProfile */
        $obj = new $this->className($this->loadFields());
        $obj->handler = $this;
        if ($isNew === true) {
            $obj->setNew();
        }
        return $obj;
    }

    /**
     * Get a {@link ProfileProfile}
     *
     * @param      $uid
     * @param bool $createOnFailure create a new {@link ProfileProfile} if none is fetched
     *
     * @return null|ProfileProfile|XoopsObject
     */
    public function getProfile($uid, $createOnFailure = true)
    {
        $obj = parent::get($uid);
        if (!is_object($obj) && $createOnFailure) {
            $obj = $this->create();
        }
        return $obj;
    }

    /**
     * Create new {@link ProfileField} object
     *
     * @param bool $isNew
     *
     * @return ProfileField
     */
    public function createField($isNew = true)
    {
        $return = $this->_fHandler->create($isNew);
        return $return;
    }

    /**
     * Load field information
     *
     * @return array
     */
    public function loadFields()
    {
        if (count($this->_fields) == 0) {
            $this->_fields = $this->_fHandler->loadFields();
        }
        return $this->_fields;
    }

    /**
     * Fetch fields
     *
     * @param CriteriaElement $criteria  {@link CriteriaElement} object
     * @param bool            $id_as_key return array with field IDs as key?
     * @param bool            $as_object return array of objects?
     *
     * @return array
     **/
    public function getFields(CriteriaElement $criteria, $id_as_key = true, $as_object = true)
    {
        return $this->_fHandler->getObjects($criteria, $id_as_key, $as_object);
    }

    /**
     * Insert a field in the database
     *
     * @param ProfileField $field
     * @param bool         $force
     *
     * @return bool
     */
    public function insertField(ProfileField $field, $force = false)
    {
        return $this->_fHandler->insert($field, $force);
    }

    /**
     * Delete a field from the database
     *
     * @param ProfileField $field
     * @param bool         $force
     *
     * @return bool
     */
    public function deleteField(ProfileField $field, $force = false)
    {
        return $this->_fHandler->delete($field, $force);
    }

    /**
     * Save a new field in the database
     *
     * @param array $vars array of variables, taken from $module->loadInfo('profile')['field']
     * @param int   $weight
     *
     * @return string
     */
    public function saveField($vars, $weight = 0)
    {
        $field = $this->createField();
        $field->setVar('field_name', $vars['name']);
        $field->setVar('field_valuetype', $vars['valuetype']);
        $field->setVar('field_type', $vars['type']);
        $field->setVar('field_weight', $weight);
        if (isset($vars['title'])) {
            $field->setVar('field_title', $vars['title']);
        }
        if (isset($vars['description'])) {
            $field->setVar('field_description', $vars['description']);
        }
        if (isset($vars['required'])) {
            $field->setVar('field_required', $vars['required']); //0 = no, 1 = yes
        }
        if (isset($vars['maxlength'])) {
            $field->setVar('field_maxlength', $vars['maxlength']);
        }
        if (isset($vars['default'])) {
            $field->setVar('field_default', $vars['default']);
        }
        if (isset($vars['notnull'])) {
            $field->setVar('field_notnull', $vars['notnull']);
        }
        if (isset($vars['show'])) {
            $field->setVar('field_show', $vars['show']);
        }
        if (isset($vars['edit'])) {
            $field->setVar('field_edit', $vars['edit']);
        }
        if (isset($vars['config'])) {
            $field->setVar('field_config', $vars['config']);
        }
        if (isset($vars['options'])) {
            $field->setVar('field_options', $vars['options']);
        } else {
            $field->setVar('field_options', array());
        }
        if ($this->insertField($field)) {
            $msg = '&nbsp;&nbsp;Field <strong>' . $vars['name'] . '</strong> added to the database';
        } else {
            $msg = '&nbsp;&nbsp;<span class="red">ERROR: Could not insert field <strong>' . $vars['name'] . '</strong> into the database. ' . implode(' ', $field->getErrors()) . $this->db2->errorInfo() . '</span>';
        }
        unset($field);
        return $msg;
    }

    /**
     * insert a new object in the database
     *
     * @param XoopsObject|ProfileProfile $obj   reference to the object
     * @param bool                       $force whether to force the query execution despite security settings
     *
     * @return bool FALSE if failed, TRUE if already present and unchanged or successful
     */
    public function insert(XoopsObject $obj, $force = false)
    {
        $uservars = $this->getUserVars();
        foreach ($uservars as $var) {
            unset($obj->vars[$var]);
        }
        if (count($obj->vars) == 0) {
            return true;
        }
        return parent::insert($obj, $force);
    }

    /**
     * Get array of standard variable names (user table)
     *
     * @return array
     */
    public function getUserVars()
    {
        return $this->_fHandler->getUserVars();
    }

    /**
     * Search profiles and users
     *
     * @param CriteriaElement    $criteria   CriteriaElement
     * @param array              $searchvars Fields to be fetched
     * @param array              $groups     for Usergroups is selected (only admin!)
     *
     * @return array
     */
    public function search(CriteriaElement $criteria, $searchvars = array(), $groups = null)
    {
        $xoops = Xoops::getInstance();
        $uservars = $this->getUserVars();

        $searchvars_user = array_intersect($searchvars, $uservars);
        $searchvars_profile = array_diff($searchvars, $uservars);
        $sv = array('u.uid, u.uname, u.email, u.user_viewemail');
        if (!empty($searchvars_user)) {
            $sv[0] .= ",u." . implode(", u.", $searchvars_user);
        }
        if (!empty($searchvars_profile)) {
            $sv[] = "p." . implode(", p.", $searchvars_profile);
        }

        $qb = $xoops->db()->createXoopsQueryBuilder();

        $qb->select((empty($searchvars) ? "u.*, p.*" : implode(", ", $sv)))
            ->fromPrefix('system_user', 'u')
            ->leftJoin('u', $this->table, 'p', 'u.uid=p.profile_id');

        if (!empty($groups)) {
            $qb->leftJoinPrefix('u', 'system_usergroup', 'g', 'u.uid=g.uid');
        }

        $criteria->renderQb($qb);

        if (!empty($groups)) {
            $qb->andWhere('g.groupid IN (:grps)')->setParameter('grps', $groups);
        }

        $result = $qb->execute();

        $user_handler = $xoops->getHandlerUser();
        $uservars = $this->getUserVars();

        $users = array();
        $profiles = array();
        while ($myrow = $result->fetchAssociative()) {
            $profile = $this->create(false);
            $user = $user_handler->create(false);

            foreach ($myrow as $name => $value) {
                if (in_array($name, $uservars)) {
                    $user->assignVar($name, $value);
                } else {
                    $profile->assignVar($name, $value);
                }
            }
            $profiles[$myrow['uid']] = $profile;
            $users[$myrow['uid']] = $user;
        }

        $qb->select('COUNT(*)')->setMaxResults(null)->setFirstResult(null);
        $result = $qb->execute();
        $count = $result->fetchOne();

        return array($users, $profiles, (int)($count));
    }
}
