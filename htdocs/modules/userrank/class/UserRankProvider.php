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
use Xoops\Core\Service\AbstractContract;
use Xoops\Core\Service\Contract\UserRankInterface;
use Xoops\Core\Service\Response;
use Doctrine\DBAL\ParameterType;

/**
 * UserRank provider for service manager
 *
 * @package   UserRankProvider
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2013-2019 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 */
class UserRankProvider extends AbstractContract implements UserRankInterface
{
    /**
     * getName - get a short name for this service provider. This should be unique within the
     * scope of the named service, so using module dirname is suggested.
     *
     * @return string - a unique name for the service provider
     */
    public function getName()
    {
        return 'userrank';
    }

    /**
     * getDescription - get human readable description of the service provider
     *
     * @return string
     */
    public function getDescription()
    {
        return 'Traditional XOOPS User Ranks.';
    }

    /**
     * getUserRank - given user info return array of rank information for the user
     *
     * @param Response $response \Xoops\Core\Service\Response object
     * @param mixed    $userinfo Xoops\Core\Kernel\Handlers\XoopsUser object for user (preferred) or
     *                            array of user info,
     *                               'uid'   => (int) id of system user
     *                               'posts' => (int) contribution count associated with the user
     *                               'rank'  => (int) id of manually assigned rank, 0 if none assigned
     *
     * @return void - $response->value set to array of rank information
     *                    'title' => string that describes the rank
     *                    'image' => url of image associated with the rank
     */
    public function getUserRank(Response $response, $userinfo)
    {
        $uid = isset($userinfo['uid']) ? (int) $userinfo['uid'] : null;
        $posts = isset($userinfo['posts']) ? (int) $userinfo['posts'] : null;
        $rank = isset($userinfo['rank']) ? (int) $userinfo['rank'] : null;
        if ($uid === null || $posts === null || $rank === null) {
            $response->setSuccess(false)->addErrorMessage('User info is invalid');
            return;
        }

        $myts = \Xoops\Core\Text\Sanitizer::getInstance();
        $db = \Xoops::getInstance()->db();
        $qb = $db->createXoopsQueryBuilder();
        $eb = $qb->expr();
        $qb ->select('r.rank_title AS title')
            ->addSelect('r.rank_image AS image')
            ->fromPrefix('userrank_rank', 'r');
        if ($rank != 0) {
            $qb->where($eb->eq('r.rank_id', ':rank'))
                ->setParameter('rank', $rank, ParameterType::INTEGER);
        } else {
            $qb->where($eb->lte('r.rank_min', ':posts'))
                ->andWhere($eb->gte('r.rank_max', ':posts'))
                ->andWhere($eb->eq('r.rank_special', 0))
                ->setParameter('posts', $posts, ParameterType::INTEGER);
        }
        $result = $qb->execute();
        $rank = $result->fetchAssociative();

        $rank['title'] = isset($rank['title']) ? $myts->htmlSpecialChars($rank['title']) : '';
        $rank['image'] = \XoopsBaseConfig::get('uploads-url') .
            (isset($rank['image']) ? '/' . $rank['image'] : '/blank.gif');

        $response->setValue($rank);
    }

    /**
     * getAssignableUserRankList - return a list of ranks that can be assigned
     *
     * @param Response $response \Xoops\Core\Service\Response object
     *
     * @return void - response->value set to array of (int) id => (string) rank title
     *                 entries of assignable ranks
     */
    public function getAssignableUserRankList(Response $response)
    {
        $db = \Xoops::getInstance()->db();
        $myts = \Xoops\Core\Text\Sanitizer::getInstance();

        $ret = array();

        $sql = $db->createXoopsQueryBuilder();
        $eb = $sql->expr();
        $sql->select('rank_id')
            ->addSelect('rank_title')
            ->fromPrefix('userrank_rank', 'r')
            ->where($eb->eq('rank_special', ':rankspecial'))
            ->orderBy('rank_title')
            ->setParameter('rankspecial', 1);

        $result = $sql->execute();
        while ($myrow = $result->fetchAssociative()) {
            $ret[$myrow['rank_id']] = $myts->htmlSpecialChars($myrow['rank_title']);
        }
        $response->setValue($ret);
    }
}
