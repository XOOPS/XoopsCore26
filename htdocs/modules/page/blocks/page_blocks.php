<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

/**
 * page module
 *
 * @copyright       XOOPS Project (http://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package         page
 * @since           2.6.0
 * @author          Mage Grégory (AKA Mage)
 * @version         $Id$
 */

function page_blocks_show($options)
{
    $xoops = \Xoops::getInstance();
    $page = $xoops->getModuleHelper('page');
    $xoops->theme()->addStylesheet($page->url('css/styles.css'));
    $xoops->theme()->addStylesheet($page->url('css/rating.css'));

    $block = [];
    if ($options[0] === 'id') {
        // same rule as viewpage.php: active, and readable by this visitor's groups
        $view_content = $page->getContentHandler()->getViewable($options[1]);
        if (null === $view_content) {
            return [];
        }

        // content
        $content = $view_content->getValues();
        foreach ($content as $k => $v) {
            $block[$k] = $v;
        }
        // related
        $block['related'] =  $page->getLinkHandler()->menu_related($options[1]);

        // get vote by user
        $block['yourvote'] = $page->getRatingHandler()->getVotebyUser($options[1]);

        // get token for rating
        $block['security'] = $xoops->security()->createToken();
    } else {
        $block['text'] = $options[4];
        $block['mode'] = $options[0];

        if ($options[0] === 'random') {
            $sort = ('sqlite' === \XoopsBaseConfig::get('db-type')) ? 'RANDOM()' : 'RAND()';
            $content = $page->getContentHandler()->getPagePublished(0, $options[3], $sort);
        } else {
            $content = $page->getContentHandler()->getPagePublished(0, $options[3], 'content_' . $options[1], $options[2]);
        }
        foreach (array_keys($content) as $i) {
            $block['content'][$i] = $content[$i]->getValues();
        }
    }
    return $block;
}

function page_blocks_edit($options)
{
    $block_form = new Xoops\Form\BlockForm();
    if ($options[0] !== 'id') {
        $mode_form = new Xoops\Form\Select(PageLocale::CONF_BLOCK_MODE, 'options[0]', $options[0], 1, false);
        $mode_form->addOption('content', PageLocale::CONF_BLOCK_L_CONTENT);
        $mode_form->addOption('list', PageLocale::CONF_BLOCK_L_LIST);
        $block_form->addElement($mode_form);

        $order_form = new Xoops\Form\Select(PageLocale::CONF_BLOCK_ORDER, 'options[1]', $options[1], 1, false);
        $order_form->addOption('create', PageLocale::CONF_BLOCK_L_RECENT);
        $order_form->addOption('hits', PageLocale::CONF_BLOCK_L_HITS);
        $order_form->addOption('rating', PageLocale::CONF_BLOCK_L_RATING);
        $order_form->addOption('random', PageLocale::CONF_BLOCK_L_RANDOM);
        $block_form->addElement($order_form);

        $sort_form = new Xoops\Form\Select(PageLocale::CONF_BLOCK_SORT, 'options[2]', $options[2], 1, false);
        $sort_form->addOption('ASC', PageLocale::CONF_BLOCK_L_ASC);
        $sort_form->addOption('DESC', PageLocale::CONF_BLOCK_L_DESC);
        $block_form->addElement($sort_form);

        $block_form->addElement(new Xoops\Form\Text(PageLocale::CONF_BLOCK_DISPLAY_NUMBER, 'options[3]', 1, 2, $options[3]), true);
        $block_form->addElement(new Xoops\Form\RadioYesNo(PageLocale::CONF_BLOCK_ALL_CONTENT, 'options[4]', $options[4]));
    } else {
        $xoops = \Xoops::getInstance();
        $page = $xoops->getModuleHelper('page');
        $block_form->addElement(new Xoops\Form\Hidden('options[0]', $options[0]));
        $content = $page->getContentHandler()->getPageTitle(1);

        $select_form = new Xoops\Form\Select(PageLocale::CONF_BLOCK_CONTENTDISPLAY, 'options[1]', $options[1], 1, false);
        foreach ($content as $value) {
            $select_form->addOption($value['content_id'], $value['content_title']);
        }

        $block_form->addElement($select_form);
    }
    return $block_form->render();
}
