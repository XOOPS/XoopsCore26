<?php
/**
 * XOOPS kernel class
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         kernel
 * @since           2.0.0
 * @author          Kazumi Ono (AKA onokazu) http://www.myweb.ne.jp/, http://jp.xoops.org/
 * @version         $Id$
 */

namespace Xoops\Core\Kernel\Handlers;

use Xoops\Core\Database\Connection;
use Xoops\Core\Kernel\Criteria;
use Xoops\Core\Kernel\CriteriaCompo;
use Xoops\Core\Kernel\CriteriaElement;
use Xoops\Core\Kernel\XoopsPersistableObjectHandler;
use Doctrine\DBAL\ParameterType;

/**
 * XOOPS template file handler class.
 * This class is responsible for providing data access mechanisms to the data source
 * of XOOPS template file class objects.
 *
 * @category  Xoops\Core\Kernel\XoopsTplFileHandler
 * @package   Xoops\Core\Kernel
 * @author    Kazumi Ono <onokazu@xoops.org>
 * @copyright 2000-2019 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 */
class XoopsTplFileHandler extends XoopsPersistableObjectHandler
{

    /**
     * Constructor
     *
     * @param Connection|null $db database
     */
    public function __construct(?Connection $db = null)
    {
        parent::__construct($db, 'system_tplfile', '\Xoops\Core\Kernel\Handlers\XoopsTplFile', 'tpl_id', 'tpl_refid');
    }

    /**
     * retrieve a specific XoopsTplFile
     *
     * @param int  $id        tpl_id of the block to retrieve
     * @param bool $getsource true = also return source
     *
     * @return XoopsTplFile|bool
     */
    public function getById($id, $getsource = false)
    {
        $qb = $this->db2->createXoopsQueryBuilder();
        $eb = $qb->expr();
        $tplfile = false;
        $id = (int)($id);
        if ($id > 0) {
            if (!$getsource) {
                $qb->select('*')
                    ->fromPrefix('system_tplfile', 'f')
                    ->where($eb->eq('f.tpl_id', ':tplid'))
                    ->setParameter('tplid', $id, ParameterType::INTEGER);
            } else {
                $qb->select('f.*')
                    ->addSelect('s.tpl_source')
                    ->fromPrefix('system_tplfile', 'f')
                    ->leftJoinPrefix('f', 'system_tplsource', 's', $eb->eq('s.tpl_id', 'f.tpl_id'))
                    ->where($eb->eq('f.tpl_id', ':tplid'))
                    ->setParameter('tplid', $id, ParameterType::INTEGER);
            }
            $result = $qb->execute();
            if (!$result) {
                return $tplfile;
            }
            $allrows = $result->fetchAll();
            if (count($allrows) == 1) {
                $tplfile = new XoopsTplFile();
                $tplfile->assignVars(reset($allrows));
            }
        }
        return $tplfile;
    }

    /**
     * loadSource
     *
     * @param XoopsTplFile $tplfile object
     *
     * @return bool
     */
    public function loadSource(XoopsTplFile $tplfile)
    {
        if (!$tplfile->getVar('tpl_source')) {
            $qb = $this->db2->createXoopsQueryBuilder();
            $eb = $qb->expr();
            $qb->select('tpl_source')
                ->fromPrefix('system_tplsource', null)
                ->where($eb->eq('tpl_id', ':tplid'))
                ->setParameter('tplid', $tplfile->getVar('tpl_id'), ParameterType::INTEGER);
            if (!$result = $qb->execute()) {
                return false;
            }
            $myrow = $result->fetchAssociative();
            $tplfile->assignVar('tpl_source', $myrow['tpl_source']);
        }
        return true;
    }

    /**
     * write a new TplFile into the database
     *
     * @param XoopsTplFile $tplfile object
     *
     * @return bool
     */
    public function insertTpl(XoopsTplFile $tplfile)
    {
        $tpl_id = 0;
        if (!$tplfile->isDirty()) {
            return true;
        }
        if (!$tplfile->cleanVars()) {
            return false;
        }

        $vars = $tplfile->cleanVars;
        $tpl_module       = $vars['tpl_module'];
        $tpl_refid        = $vars['tpl_refid'];
        $tpl_tplset       = $vars['tpl_tplset'];
        $tpl_file         = $vars['tpl_file'];
        $tpl_desc         = $vars['tpl_desc'];
        $tpl_lastmodified = $vars['tpl_lastmodified'];
        $tpl_lastimported = $vars['tpl_lastimported'];
        $tpl_type         = $vars['tpl_type'];
        if (isset($vars['tpl_id'])) {
            $tpl_id = $vars['tpl_id'];
        }
        if (isset($vars['tpl_source'])) {
            $tpl_source = $vars['tpl_source'];
        }

        if ($tplfile->isNew()) {
            $tpl_id = 0;
            $values = array(
                // 'tpl_id' => $tpl_id,
                'tpl_module' => $tpl_module,
                'tpl_refid' => $tpl_refid,
                'tpl_tplset' => $tpl_tplset,
                'tpl_file' => $tpl_file,
                'tpl_desc' => $tpl_desc,
                'tpl_lastmodified' => $tpl_lastmodified,
                'tpl_lastimported' => $tpl_lastimported,
                'tpl_type' => $tpl_type,
            );
            if (!$this->db2->insertPrefix('system_tplfile', $values)) {
                return false;
            }
            if (empty($tpl_id)) {
                $tpl_id = $this->db2->lastInsertId();
            }
            if (isset($tpl_source) && $tpl_source != '') {
                $values = array(
                    'tpl_id' => $tpl_id,
                    'tpl_source' => $tpl_source,
                );
                if (!$this->db2->insertPrefix('system_tplsource', $values)) {
                    $this->db2->deletePrefix('system_tplfile', array('tpl_id' => $tpl_id));
                    return false;
                }
            }
            $tplfile->assignVar('tpl_id', $tpl_id);
        } else {
            $values = array(
                // 'tpl_id' => $tpl_id,
                'tpl_module' => $tpl_module,
                'tpl_refid' => $tpl_refid,
                'tpl_tplset' => $tpl_tplset,
                'tpl_file' => $tpl_file,
                'tpl_desc' => $tpl_desc,
                'tpl_lastmodified' => $tpl_lastmodified,
                'tpl_lastimported' => $tpl_lastimported,
                'tpl_type' => $tpl_type,
            );
            if (!$this->db2->updatePrefix('system_tplfile', $values, array('tpl_id' => $tpl_id))) {
                return false;
            }

            if (isset($tpl_source) && $tpl_source != '') {
                $values = array(
                    // 'tpl_id' => $tpl_id,
                    'tpl_source' => $tpl_source,
                );
                if ($this->db2->updatePrefix('system_tplsource', $values, array('tpl_id' => $tpl_id))) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * forceUpdate
     *
     * @param XoopsTplFile $tplfile object
     *
     * @return bool
     */
    public function forceUpdate(XoopsTplFile $tplfile)
    {
        if (!$tplfile->isDirty()) {
            return true;
        }
        if (!$tplfile->cleanVars()) {
            return false;
        }

        $vars = $tplfile->cleanVars;
        $tpl_module       = $vars['tpl_module'];
        $tpl_refid        = $vars['tpl_refid'];
        $tpl_tplset       = $vars['tpl_tplset'];
        $tpl_file         = $vars['tpl_file'];
        $tpl_desc         = $vars['tpl_desc'];
        $tpl_lastmodified = $vars['tpl_lastmodified'];
        $tpl_lastimported = $vars['tpl_lastimported'];
        $tpl_type         = $vars['tpl_type'];
        //$tpl_id           = $vars['tpl_id'];
        if (isset($vars['tpl_source'])) {
            $tpl_source = $vars['tpl_source'];
        }

        if (!$tplfile->isNew()) {
            $tpl_id = 0;
            $values = array(
                // 'tpl_id' => $tpl_id,
                'tpl_module' => $tpl_module,
                'tpl_refid' => $tpl_refid,
                'tpl_tplset' => $tpl_tplset,
                'tpl_file' => $tpl_file,
                'tpl_desc' => $tpl_desc,
                'tpl_lastmodified' => $tpl_lastmodified,
                'tpl_lastimported' => $tpl_lastimported,
                'tpl_type' => $tpl_type,
            );
            if (!$this->db2->updatePrefix('system_tplfile', $values, array('tpl_id' => $tpl_id))) {
                return false;
            }

            if (isset($tpl_source) && $tpl_source != '') {
                $tpl_id = 0;
                $values = array(
                    // 'tpl_id' => $tpl_id,
                    'tpl_source' => $tpl_source,
                );
                if ($this->db2->updatePrefix('system_tplsource', $values, array('tpl_id' => $tpl_id))) {
                    return false;
                }
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * delete a block from the database
     *
     * @param XoopsTplFile $tplfile object
     *
     * @return bool
     */
    public function deleteTpl(XoopsTplFile $tplfile)
    {
        $tpl_id = $tplfile->getVar('tpl_id');
        if (!$this->db2->deletePrefix('system_tplfile', array('tpl_id' => $tpl_id))) {
            return false;
        }
        $this->db2->deletePrefix('system_tplsource', array('tpl_id' => $tpl_id));
        return true;
    }

    /**
     * getTplObjects
     *
     * @param CriteriaElement|null $criteria  criteria to match
     * @param bool                 $getsource include the source
     * @param bool                 $id_as_key use the object id as array key
     *
     * @return array
     */
    public function getTplObjects(?CriteriaElement $criteria = null, $getsource = false, $id_as_key = false)
    {
        $qb = $this->db2->createXoopsQueryBuilder();
        $eb = $qb->expr();

        $ret = array();

        if (!$getsource) {
            $qb->select('*')
                ->fromPrefix('system_tplfile', 'f');
        } else {
            $qb->select('f.*')
                ->addSelect('s.tpl_source')
                ->fromPrefix('system_tplfile', 'f')
                ->leftJoinPrefix('f', 'system_tplsource', 's', $eb->eq('s.tpl_id', 'f.tpl_id'));
        }
        if (isset($criteria) && ($criteria instanceof CriteriaElement)) {
            $criteria->renderQb($qb);
        }
        $result = $qb->execute();
        if (!$result) {
            return $ret;
        }
        while ($myrow = $result->fetchAssociative()) {
            $tplfile = new XoopsTplFile();
            $tplfile->assignVars($myrow);
            if (!$id_as_key) {
                $ret[] = $tplfile;
            } else {
                $ret[$myrow['tpl_id']] = $tplfile;
            }
            unset($tplfile);
        }
        return $ret;
    }

    /**
     * getModuleTplCount
     *
     * @param string $tplset tpl set name
     *
     * @return array
     */
    public function getModuleTplCount($tplset)
    {
        $qb = $this->db2->createXoopsQueryBuilder();
        $eb = $qb->expr();

        $qb->select('tpl_module')
            ->addSelect('COUNT(tpl_id) AS count')
            ->fromPrefix('system_tplfile', null)
            ->where($eb->eq('tpl_tplset', ':tpset'))
            ->groupBy('tpl_module')
            ->setParameter('tpset', $tplset, ParameterType::STRING);

        $ret = array();
        $result = $qb->execute();
        if (!$result) {
            return $ret;
        }
        while ($myrow = $result->fetchAssociative()) {
            if ($myrow['tpl_module'] != '') {
                $ret[$myrow['tpl_module']] = $myrow['count'];
            }
        }
        return $ret;
    }

    /**
     * Find Template File
     *
     * @param string|null $tplset    template set
     * @param string|null $type      template type
     * @param string|null $refid     reference id
     * @param string|null $module    module
     * @param string|null $file      file name
     * @param bool        $getsource include template source
     *
     * @return array
     */
    public function find($tplset = null, $type = null, $refid = null, $module = null, $file = null, $getsource = false)
    {
        $criteria = new CriteriaCompo();
        if (isset($tplset)) {
            $criteria->add(new Criteria('tpl_tplset', $tplset));
        }
        if (isset($module)) {
            $criteria->add(new Criteria('tpl_module', $module));
        }
        if (isset($refid)) {
            $criteria->add(new Criteria('tpl_refid', $refid));
        }
        if (isset($file)) {
            $criteria->add(new Criteria('tpl_file', $file));
        }
        if (isset($type)) {
            if (is_array($type)) {
                $criteria2 = new CriteriaCompo();
                foreach ($type as $t) {
                    $criteria2->add(new Criteria('tpl_type', $t), 'OR');
                }
                $criteria->add($criteria2);
            } else {
                $criteria->add(new Criteria('tpl_type', $type));
            }
        }
        return $this->getTplObjects($criteria, $getsource, false);
    }

    /**
     * Template Exists
     *
     * @param string $tplname     template name
     * @param string $tplset_name set name
     *
     * @return bool
     */
    public function templateExists($tplname, $tplset_name)
    {
        $criteria = new CriteriaCompo(new Criteria('tpl_file', trim($tplname)));
        $criteria->add(new Criteria('tpl_tplset', trim($tplset_name)));
        if ($this->getCount($criteria) > 0) {
            return true;
        }
        return false;
    }
}
