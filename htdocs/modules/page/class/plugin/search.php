<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xoops\Module\Plugin\PluginAbstract;
use Xmf\Metagen;
use Doctrine\DBAL\ParameterType;

/**
 * page module
 *
 * @author          Mage Grégory (AKA Mage)
 * @copyright       2000-2019 XOOPS Project (https://xoops.org)
 * @license         GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @since           2.6.0
 */
class PageSearchPlugin extends PluginAbstract implements SearchPluginInterface
{
    /**
     * search - search
     *
     * @param string[] $queryArray search terms
     * @param string   $andor      and/or how to treat search terms
     * @param integer  $limit      max number to return
     * @param integer  $offset     offset of first row to return
     * @param integer  $userid     a specific user id to limit the query
     *
     * @return array of result items
     *           'title' => the item title
     *           'content' => brief content or summary
     *           'link' => link to visit item
     *           'time' => time modified (unix timestamp)
     *           'uid' => author uid
     *           'image' => icon for search display
     *
     */
    public function search($queryArray, $andor, $limit, $offset, $userid)
    {
        $andor = strtolower($andor)==='and' ? 'and' : 'or';

        $qb = \Xoops::getInstance()->db()->createXoopsQueryBuilder();
        $eb = $qb->expr();
        $qb ->select('DISTINCT *')
            ->fromPrefix('page_content')
            ->where($eb->neq('content_status', '0'))
            ->orderBy('content_create', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        if (is_array($queryArray) && !empty($queryArray)) {
            $queryParts = array();
            foreach ($queryArray as $i => $q) {
                $qterm = ':qterm' . $i;
                $qb->setParameter($qterm, '%' . $q . '%', ParameterType::STRING);
                $queryParts[] = $eb -> orX(
                    $eb->like('content_title', $qterm),
                    $eb->like('content_text', $qterm),
                    $eb->like('content_shorttext', $qterm)
                );
            }
            if ($andor === 'and') {
                $qb->andWhere(call_user_func_array(array($eb, "andX"), $queryParts));
            } else {
                $qb->andWhere(call_user_func_array(array($eb, "orX"), $queryParts));
            }
        } else {
            $qb->setParameter('uid', (int) $userid, ParameterType::INTEGER);
            $qb->andWhere($eb->eq('content_author', ':uid'));
        }

        $myts = \Xoops\Core\Text\Sanitizer::getInstance();
        $items = array();
        $result = $qb->execute();
        while ($myrow = $result->fetchAssociative()) {
            $content = $myrow["content_shorttext"] . "<br /><br />" . $myrow["content_text"];
            $content = $myts->displayTarea($content);
            $items[] = array(
                'title' => $myrow['content_title'],
                'content' => Metagen::getSearchSummary($content, $queryArray),
                'link' => "viewpage.php?id=" . $myrow["content_id"],
                'time' => $myrow['content_create'],
                'uid' => $myrow['content_author'],
                'image' => 'images/logo_small.png',
            );
        }
        return $items;
    }
}
