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

use Xoops;
use Xoops\Core\Database\Connection;
use Xoops\Core\Database\QueryBuilder;
use Xoops\Core\Kernel\Criteria;
use Xoops\Core\Kernel\CriteriaCompo;
use Xoops\Core\Kernel\CriteriaElement;
use Xoops\Core\Kernel\XoopsObject;
use Xoops\Core\Kernel\XoopsPersistableObjectHandler;
use XoopsModules\Publisher;
use Doctrine\DBAL\ParameterType;

/**
 * @copyright       The XUUPS Project http://sourceforge.net/projects/xuups/
 * @license         GNU GPL V2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         Publisher
 * @since           1.0
 * @author          trabis <lusopoemas@gmail.com>
 * @author          The SmartFactory <www.smartfactory.ca>
 */
require_once \dirname(__DIR__) . '/include/common.php';

/**
 * Items handler class.
 * This class is responsible for providing data access mechanisms to the data source
 * of Q&A class objects.
 *
 * @author  marcan <marcan@notrevie.ca>
 * @package Publisher
 */
class ItemHandler extends XoopsPersistableObjectHandler
{
    /**
     * @var Helper
     * @access public
     */
    public $helper = null;

    public function __construct(Connection $db)
    {
        parent::__construct($db, 'publisher_items', Item::class, 'itemid', 'title');
        $this->helper = Helper::getInstance();
    }

    /**
     * insert a new item in the database
     *
     * @param XoopsObject $item reference to the {@link Publisher\Item} object
     * @param bool        $force
     *
     * @return bool FALSE if failed, TRUE if already present and unchanged or successful
     */
    public function insert(XoopsObject $item, $force = true)
    {
        $xoops = Xoops::getInstance();
        if (!$item->getVar('meta_keywords') || !$item->getVar('meta_description') || !$item->getVar('short_url')) {
            $publisher_metagen = new Publisher\Metagen($item->title(), $item->getVar('meta_keywords'), $item->getVar('summary'));
            // Auto create meta tags if empty
            if (!$item->getVar('meta_keywords')) {
                $item->setVar('meta_keywords', $publisher_metagen->_keywords);
            }
            if (!$item->getVar('meta_description')) {
                $item->setVar('meta_description', $publisher_metagen->_description);
            }
            // Auto create short_url if empty
            if (!$item->getVar('short_url')) {
                $item->setVar('short_url', $publisher_metagen::generateSeoTitle($item->getVar('title', 'n'), false));
            }
        }
        if (!parent::insert($item, $force)) {
            return false;
        }
        if ($xoops->isActiveModule('tag')) {
            // Storing tags information
            $tagHandler = $xoops->getModuleHandler('tag', 'tag');
            $tagHandler->updateByItem($item->getVar('item_tag'), $item->getVar('itemid'), \PUBLISHER_DIRNAME, 0);
        }

        return true;
    }

    /**
     * delete an item from the database
     *
     * @param XoopsObject $item reference to the ITEM to delete
     * @param bool        $force
     *
     * @return bool FALSE if failed.
     */
    public function delete(XoopsObject $item, $force = false)
    {
        $xoops = Xoops::getInstance();
        // Deleting the files
        if (!$this->helper->getFileHandler()->deleteItemFiles($item)) {
            $item->setErrors('An error while deleting a file.');
        }
        if (!parent::delete($item, $force)) {
            $item->setErrors('An error while deleting.');

            return false;
        }
        // Removing tags information
        if ($xoops->isActiveModule('tag')) {
            $tagHandler = $xoops->getModuleHandler('tag', 'tag');
            $tagHandler->updateByItem('', $item->getVar('itemid'), \PUBLISHER_DIRNAME, 0);
        }

        return true;
    }

    /**
     * retrieve items from the database
     *
     * @param object $criteria      {@link CriteriaElement} conditions to be met
     * @param string $id_key        what shall we use as array key ? none, itemid, categoryid
     * @param string $notNullFields fields that cannot be null or empty
     *
     * @return array array of {@link Publisher\Item} objects
     */
    public function getItemObjects($criteria = null, $id_key = 'none', $notNullFields = ''): array
    {
        $ret = [];
        $whereMode = '';

        $qb = $this->db2->createXoopsQueryBuilder();
        $qb->select('*')->fromPrefix('publisher_items', '');
        if (isset($criteria) && $criteria instanceof CriteriaElement) {
            $criteria->renderQb($qb, '');
            $whereMode = 'AND';
        }
        $this->addNotNullFieldClause($qb, $notNullFields, $whereMode);
        $theObjects = [];
        $result = $qb->execute();
        while (false !== ($myrow = $result->fetchAssociative())) {
            $item = new Publisher\Item();
            $item->assignVars($myrow);
            $theObjects[$myrow['itemid']] = $item;
            unset($item);
        }

        /* @var Publisher\Item $theObject */
        foreach ($theObjects as $theObject) {
            if ('none' === $id_key) {
                $ret[] = $theObject;
            } elseif ('itemid' === $id_key) {
                $ret[$theObject->getVar('itemid')] = $theObject;
            } else {
                $ret[$theObject->getVar($id_key)][$theObject->getVar('itemid')] = $theObject;
            }
            unset($theObject);
        }

        return $ret;
    }

    /**
     * count items matching a condition
     *
     * @param object $criteria      {@link CriteriaElement} to match
     * @param string $notNullFields fields that cannot be null or empty
     *
     * @return int count of items
     */
    public function getItemCount($criteria = null, $notNullFields = ''): int
    {
        $whereMode = '';

        $qb = $this->db2->createXoopsQueryBuilder();
        $qb->select('COUNT(*)')->fromPrefix('publisher_items', '');
        if (isset($criteria) && $criteria instanceof CriteriaElement) {
            $whereClause = $criteria->renderQb($qb, '');
            $whereMode = 'AND';
        }
        $this->addNotNullFieldClause($qb, $notNullFields, $whereMode);
        $result = $qb->execute();

        if (!$result) {
            return 0;
        }
        [$count] = $result->fetchNumeric();

        return $count;
    }

    /**
     * @param        $categoryid
     * @param string|array $status
     * @param string $notNullFields
     */
    public function getItemsCount($categoryid = -1, $status = '', $notNullFields = ''): int
    {
        global $publisher_isAdmin;
        $criteriaCategory = null;
        if (!$publisher_isAdmin) {
            $criteriaPermissions = new CriteriaCompo();
            // Categories for which user has access
            $categoriesGranted = $this->helper->getPermissionHandler()->getGrantedItems('category_read');
            if (!empty($categoriesGranted)) {
                $grantedCategories = new Criteria('categoryid', '(' . \implode(',', $categoriesGranted) . ')', 'IN');
                $criteriaPermissions->add($grantedCategories, 'AND');
            } else {
                return 0;
            }
        }
        if (isset($categoryid) && -1 != $categoryid) {
            $criteriaCategory = new criteria('categoryid', $categoryid);
        }
        $criteriaStatus = new CriteriaCompo();
        if (!empty($status) && \is_array($status)) {
            foreach ($status as $v) {
                $criteriaStatus->add(new Criteria('status', $v), 'OR');
            }
        } elseif (!empty($status) && -1 != $status) {
            $criteriaStatus->add(new Criteria('status', $status), 'OR');
        }
        $criteria = new CriteriaCompo();
        if (null !== $criteriaCategory) {
            $criteria->add($criteriaCategory);
        }
        if (null !== $criteriaPermissions) {
            $criteria->add($criteriaPermissions);
        }
        if (null !== $criteriaStatus) {
            $criteria->add($criteriaStatus);
        }

        return $this->getItemCount($criteria, $notNullFields);
    }

    /**
     * @param int    $limit
     * @param int    $start
     * @param int    $categoryid
     * @param string $sort
     * @param string $order
     * @param string $notNullFields
     * @param bool   $asobject
     * @param string $id_key
     */
    public function getAllPublished($limit = 0, $start = 0, $categoryid = -1, $sort = 'datesub', $order = 'DESC', $notNullFields = '', $asobject = true, $id_key = 'none'): array
    {
        $otherCriteria = new Criteria('datesub', \time(), '<=');

        return $this->getItems($limit, $start, [\_PUBLISHER_STATUS_PUBLISHED], $categoryid, $sort, $order, $notNullFields, $asobject, $otherCriteria, $id_key);
    }

    /**
     * @param Publisher\Item|false $obj
     */
    public function getPreviousPublished($obj)
    {
        $ret = false;
        $otherCriteria = new CriteriaCompo();
        $otherCriteria->add(new Criteria('datesub', $obj->getVar('datesub'), '<'));
        $objs = $this->getItems(1, 0, [\_PUBLISHER_STATUS_PUBLISHED], $obj->getVar('categoryid'), 'datesub', 'DESC', '', true, $otherCriteria, 'none');
        if (\count($objs) > 0) {
            $ret = $objs[0];
        }

        return $ret;
    }

    /**
     * @param Publisher\Item|false $obj
     */
    public function getNextPublished($obj)
    {
        $ret = false;
        $otherCriteria = new CriteriaCompo();
        $otherCriteria->add(new Criteria('datesub', $obj->getVar('datesub'), '>'));
        $otherCriteria->add(new Criteria('datesub', \time(), '<='));
        $objs = $this->getItems(1, 0, [\_PUBLISHER_STATUS_PUBLISHED], $obj->getVar('categoryid'), 'datesub', 'ASC', '', true, $otherCriteria, 'none');
        if (\count($objs) > 0) {
            $ret = $objs[0];
        }

        return $ret;
    }

    /**
     * @param int    $limit
     * @param int    $start
     * @param int    $categoryid
     * @param string $sort
     * @param string $order
     * @param string $notNullFields
     * @param bool   $asobject
     * @param string $id_key
     */
    public function getAllSubmitted($limit = 0, $start = 0, $categoryid = -1, $sort = 'datesub', $order = 'DESC', $notNullFields = '', $asobject = true, $id_key = 'none'): array
    {
        return $this->getItems($limit, $start, [\_PUBLISHER_STATUS_SUBMITTED], $categoryid, $sort, $order, $notNullFields, $asobject, null, $id_key);
    }

    /**
     * @param int    $limit
     * @param int    $start
     * @param int    $categoryid
     * @param string $sort
     * @param string $order
     * @param string $notNullFields
     * @param bool   $asobject
     * @param string $id_key
     */
    public function getAllOffline($limit = 0, $start = 0, $categoryid = -1, $sort = 'datesub', $order = 'DESC', $notNullFields = '', $asobject = true, $id_key = 'none'): array
    {
        return $this->getItems($limit, $start, [\_PUBLISHER_STATUS_OFFLINE], $categoryid, $sort, $order, $notNullFields, $asobject, null, $id_key);
    }

    /**
     * @param int    $limit
     * @param int    $start
     * @param int    $categoryid
     * @param string $sort
     * @param string $order
     * @param string $notNullFields
     * @param bool   $asobject
     * @param string $id_key
     */
    public function getAllRejected($limit = 0, $start = 0, $categoryid = -1, $sort = 'datesub', $order = 'DESC', $notNullFields = '', $asobject = true, $id_key = 'none'): array
    {
        return $this->getItems($limit, $start, [\_PUBLISHER_STATUS_REJECTED], $categoryid, $sort, $order, $notNullFields, $asobject, null, $id_key);
    }

    /**
     * @param int    $limit
     * @param int    $start
     * @param string $status
     * @param int    $categoryid
     * @param string $sort
     * @param string $order
     * @param string $notNullFields
     * @param bool   $asobject
     * @param null   $otherCriteria
     * @param string $id_key
     */
    public function getItems($limit = 0, $start = 0, $status = '', $categoryid = -1, $sort = 'datesub', $order = 'DESC', $notNullFields = '', $asobject = true, $otherCriteria = null, $id_key = 'none'): array
    {
        global $publisher_isAdmin;
        $criteriaCategory = $criteriaStatus = null;
        if (!$publisher_isAdmin) {
            $criteriaPermissions = new CriteriaCompo();
            // Categories for which user has access
            $categoriesGranted = $this->helper->getPermissionHandler()->getGrantedItems('category_read');
            if (!empty($categoriesGranted)) {
                $grantedCategories = new Criteria('categoryid', '(' . \implode(',', $categoriesGranted) . ')', 'IN');
                $criteriaPermissions->add($grantedCategories, 'AND');
            } else {
                return [];
            }
        }
        if (isset($categoryid) && (-1 != $categoryid)) {
            $criteriaCategory = new criteria('categoryid', $categoryid);
        }
        if (!empty($status) && \is_array($status)) {
            $criteriaStatus = new CriteriaCompo();
            foreach ($status as $v) {
                $criteriaStatus->add(new Criteria('status', $v), 'OR');
            }
        } elseif (!empty($status) && -1 != $status) {
            $criteriaStatus = new CriteriaCompo();
            $criteriaStatus->add(new Criteria('status', $status), 'OR');
        }
        $criteria = new CriteriaCompo();
        if (null !== $criteriaCategory) {
            $criteria->add($criteriaCategory);
        }
        if (null !== $criteriaPermissions) {
            $criteria->add($criteriaPermissions);
        }
        if (null !== $criteriaStatus) {
            $criteria->add($criteriaStatus);
        }
        if (!empty($otherCriteria)) {
            $criteria->add($otherCriteria);
        }
        $criteria->setLimit($limit);
        $criteria->setStart($start);
        $criteria->setSort($sort);
        $criteria->setOrder($order);
        $ret = $this->getItemObjects($criteria, $id_key, $notNullFields);

        return $ret;
    }

    /**
     * @param string $field
     * @param string $status
     * @param int    $categoryId
     */
    public function getRandomItem($field = '', $status = '', $categoryId = -1): bool
    {
        $ret = false;
        $notNullFields = $field;
        // Getting the number of published Items
        $totalItems = $this->getItemsCount($categoryId, $status, $notNullFields);
        if ($totalItems > 0) {
            $totalItems = $totalItems - 1;
            // mt_srand((float)\microtime() * 1000000);
            $entrynumber = \random_int(0, $totalItems);
            $item = $this->getItems(1, $entrynumber, $status, $categoryId, $sort = 'datesub', $order = 'DESC', $notNullFields);
            if ($item) {
                $ret = $item[0];
            }
        }

        return $ret;
    }

    /**
     * @param $itemid
     *
     * @return bool
     */
    public function updateCounter($itemid): ?bool
    {
        $qb = $this->db2->createXoopsQueryBuilder();
        $qb->updatePrefix('publisher_items', 'i')->set('i.counter', 'i.counter+1')->where('i.itemid = :itemid')->setParameter('itemid', $itemid, ParameterType::INTEGER);
        $result = $qb->execute();
        if ($result) {
            return true;
        }

        return false;
    }

    /**
     * addNotNullFieldClause exclude rows where specified columns are empty or null
     *
     * @param QueryBuilder $qb            QueryBuilder instance
     * @param string|array $notNullFields fields that should not be empty
     * @param string       $whereMode     Initial where method, 'AND' andWhere(), otherwise where()
     *
     * @return QueryBuilder instance
     */
    protected function addNotNullFieldClause(QueryBuilder $qb, $notNullFields = [], $whereMode = ''): QueryBuilder
    {
        $eb = $qb->expr();
        if (!empty($notNullFields)) {
            if (!\is_array($notNullFields)) {
                $notNullFields = (array)$notNullFields;
            }
            foreach ($notNullFields as $v) {
                if ('AND' === $whereMode) {
                    $qb->andWhere($eb->isNotNull($v, ''))->andWhere($eb->neq($v, "''"));
                } else {
                    $qb->where($eb->isNotNull($v, ''))->andWhere($eb->neq($v, "''"));
                    $whereMode = 'AND';
                }
            }
        }

        return $qb;
    }

    /**
     * @param array        $queryarray
     * @param string       $andor
     * @param int          $limit
     * @param int          $offset
     * @param int|array    $userid
     * @param array        $categories
     * @param int|string   $sortby
     * @param string|array $searchin
     * @param string       $extra
     */
    public function getItemsFromSearch($queryarray = [], $andor = 'AND', $limit = 0, $offset = 0, $userid = 0, $categories = [], $sortby = 0, $searchin = '', $extra = ''): array
    {
        $xoops = Xoops::getInstance();
        $ret = [];
        $gpermHandler = $xoops->getHandlerGroupPermission();
        $groups = $xoops->getUserGroups();
        $searchin = empty($searchin) ? [
            'title',
            'body',
            'summary',
        ] : (\is_array($searchin) ? $searchin : [$searchin]);
        if (\in_array('all', $searchin) || 0 == \count($searchin)) {
            $searchin = ['title', 'subtitle', 'body', 'summary', 'meta_keywords'];
        }
        if (\is_array($userid) && \count($userid) > 0) {
            $userid = \array_map('\intval', $userid);
            $criteriaUser = new CriteriaCompo();
            $criteriaUser->add(new Criteria('uid', '(' . \implode(',', $userid) . ')', 'IN'), 'OR');
        } elseif (\is_numeric($userid) && $userid > 0) {
            $criteriaUser = new CriteriaCompo();
            $criteriaUser->add(new Criteria('uid', $userid), 'OR');
        }
        $count = \count($queryarray);
        if (\is_array($queryarray) && $count > 0) {
            $criteriaKeywords = new CriteriaCompo();
            foreach ($queryarray as $iValue) {
                $criteriaKeyword = new CriteriaCompo();
                if (\in_array('title', $searchin)) {
                    $criteriaKeyword->add(new Criteria('title', '%' . $iValue . '%', 'LIKE'), 'OR');
                }
                if (\in_array('subtitle', $searchin)) {
                    $criteriaKeyword->add(new Criteria('subtitle', '%' . $iValue . '%', 'LIKE'), 'OR');
                }
                if (\in_array('body', $searchin)) {
                    $criteriaKeyword->add(new Criteria('body', '%' . $iValue . '%', 'LIKE'), 'OR');
                }
                if (\in_array('summary', $searchin)) {
                    $criteriaKeyword->add(new Criteria('summary', '%' . $iValue . '%', 'LIKE'), 'OR');
                }
                if (\in_array('meta_keywords', $searchin)) {
                    $criteriaKeyword->add(new Criteria('meta_keywords', '%' . $iValue . '%', 'LIKE'), 'OR');
                }
                $criteriaKeywords->add($criteriaKeyword, $andor);
                unset($criteriaKeyword);
            }
        }
        if (!Publisher\Utils::IsUserAdmin() && (\count($categories) > 0)) {
            $criteriaPermissions = new CriteriaCompo();
            // Categories for which user has access
            $categoriesGranted = $gpermHandler->getItemIds('category_read', $groups, $this->helper->getModule()->getVar('mid'));
            if (\count($categories) > 0) {
                $categoriesGranted = \array_intersect($categoriesGranted, $categories);
            }
            if (0 == \count($categoriesGranted)) {
                return $ret;
            }
            $grantedCategories = new Criteria('categoryid', '(' . \implode(',', $categoriesGranted) . ')', 'IN');
            $criteriaPermissions->add($grantedCategories, 'AND');
        } elseif (\count($categories) > 0) {
            $criteriaPermissions = new CriteriaCompo();
            $grantedCategories = new Criteria('categoryid', '(' . \implode(',', $categories) . ')', 'IN');
            $criteriaPermissions->add($grantedCategories, 'AND');
        }
        $criteriaItemsStatus = new CriteriaCompo();
        $criteriaItemsStatus->add(new Criteria('status', \_PUBLISHER_STATUS_PUBLISHED));
        $criteria = new CriteriaCompo();
        if (null !== $criteriaUser) {
            $criteria->add($criteriaUser, 'AND');
        }
        if (null !== $criteriaKeywords) {
            $criteria->add($criteriaKeywords, 'AND');
        }
        if (null !== $criteriaPermissions) {
            $criteria->add($criteriaPermissions);
        }
        if (null !== $criteriaItemsStatus) {
            $criteria->add($criteriaItemsStatus, 'AND');
        }
        $criteria->setLimit($limit);
        $criteria->setStart($offset);
        if (empty($sortby)) {
            $sortby = 'datesub';
        }
        $criteria->setSort($sortby);
        $order = 'ASC';
        if ('datesub' === $sortby) {
            $order = 'DESC';
        }
        $criteria->setOrder($order);
        $ret = $this->getItemObjects($criteria);

        return $ret;
    }

    /**
     * @param array $categoriesObj
     * @param array $status
     */
    public function getLastPublishedByCat($categoriesObj, $status = [\_PUBLISHER_STATUS_PUBLISHED]): array
    {
        $ret = [];
        $catIds = [];
        /* @var Publisher\Category $category */
        foreach ($categoriesObj as $parentid) {
            foreach ($parentid as $category) {
                $catId = $category->getVar('categoryid');
                $catIds[$catId] = $catId;
            }
        }
        if (empty($catIds)) {
            return $ret;
        }

        // $sql = "SELECT mi.categoryid, mi.itemid, mi.title, mi.short_url, mi.uid, mi.datesub";
        // $sql .= " FROM (SELECT categoryid, MAX(datesub) AS date FROM " . $this->db->prefix('publisher_items');
        // $sql .= " WHERE status IN (" . implode(',', $status) . ")";
        // $sql .= " AND categoryid IN (" . implode(',', $catIds) . ")";
        // $sql .= " GROUP BY categoryid)mo";
        // $sql .= " JOIN " . $this->db->prefix('publisher_items') . " mi ON mi.datesub = mo.date";

        $qb = $this->db2->createXoopsQueryBuilder();
        $qb->select('mi.categoryid', 'mi.itemid', 'mi.title', 'mi.short_url', 'mi.uid', 'mi.datesub');

        $subqb = $this->db2->createXoopsQueryBuilder();
        $subqb->select('categoryid', 'MAX(datesub) AS date')->fromPrefix('publisher_items', '')->where($subqb->expr()->in('status', $status))->andWhere($subqb->expr()->in('categoryid', $catIds))->groupBy('categoryid');
        $subquery = '(' . $subqb->getSQL() . ')';

        $qb->from($subquery, 'mo')->joinPrefix('mo', 'publisher_items', 'mi', 'mi.datesub = mo.date');

        $result = $qb->execute();
        while (false !== ($row = $result->fetchAssociative())) {
            $item = new Publisher\Item();
            $item->assignVars($row);
            $ret[$row['categoryid']] = $item;
            unset($item);
        }

        return $ret;
    }

    /**
     * @param int    $parentid
     * @param array  $catsCount
     * @param string $spaces
     * @param array  $resultCatCounts
     */
    public function countArticlesByCat($parentid, &$catsCount, $spaces = '', $resultCatCounts = []): int
    {
        $newspaces = $spaces . '--';
        $thecount = 0;
        foreach ($catsCount[$parentid] as $subCatId => $count) {
            $thecount = $thecount + $count;
            $resultCatCounts[$subCatId] = $count;
            if (isset($catsCount[$subCatId])) {
                $thecount = $thecount + $this->countArticlesByCat($subCatId, $catsCount, $newspaces, $resultCatCounts);
                $resultCatCounts[$subCatId] = $thecount;
            }
        }

        return $thecount;
    }

    /**
     * @param int   $cat_id
     * @param array $status
     * @param bool  $inSubCat
     */
    public function getCountsByCat($cat_id, $status, $inSubCat = false): array
    {
        $ret = [];
        $catsCount = [];

        $qb = $this->db2->createXoopsQueryBuilder();
        $qb->select('c.parentid', 'i.categoryid', 'COUNT(*) AS count')->fromPrefix('publisher_items', 'i')->innerJoinPrefix('i', 'publisher_categories', 'c', 'i.categoryid=c.categoryid')->where($qb->expr()->in('i.status', $status))->groupBy('i.categoryid')->orderBy(
            'c.parentid',
            'ASC'
        )->addOrderBy(
                                                                                                                                                                                                                                                                              'i.categoryid',
                                                                                                                                                                                                                                                                              'ASC'
                                                                                                                                                                                                                                                                          );
        if ((int)$cat_id > 0) {
            $qb->andWhere($qb->expr()->eq('i.categoryid', ':catid'))->setParameter('catid', $cat_id, ParameterType::INTEGER);
        }

        //$sql = 'SELECT c.parentid, i.categoryid, COUNT(*) AS count FROM ' . $this->db->prefix('publisher_items')
        //. ' AS i INNER JOIN ' . $this->db->prefix('publisher_categories') . ' AS c ON i.categoryid=c.categoryid';
        //if ((int)($cat_id) > 0) {
        //    $sql .= ' WHERE i.categoryid = ' . (int)($cat_id);
        //    $sql .= ' AND i.status IN (' . implode(',', $status) . ')';
        //} else {
        //    $sql .= ' WHERE i.status IN (' . implode(',', $status) . ')';
        //}
        //$sql .= ' GROUP BY i.categoryid ORDER BY c.parentid ASC, i.categoryid ASC';

        $result = $qb->execute();

        if (!$result) {
            return $ret;
        }
        if (!$inSubCat) {
            while (false !== ($row = $result->fetchAssociative())) {
                $catsCount[$row['categoryid']] = $row['count'];
            }

            return $catsCount;
        }
        while (false !== ($row = $result->fetchAssociative())) {
            $catsCount[$row['parentid']][$row['categoryid']] = $row['count'];
        }
        $resultCatCounts = [];
        foreach ($catsCount[0] as $subCatId => $count) {
            $resultCatCounts[$subCatId] = $count;
            if (isset($catsCount[$subCatId])) {
                $resultCatCounts[$subCatId] = $resultCatCounts[$subCatId] + $this->countArticlesByCat($subCatId, $catsCount, $spaces = '', $resultCatCounts);
            }
        }

        return $resultCatCounts;
    }
}
