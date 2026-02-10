<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

namespace Xoops\Core\Filter;

use Assetic\Contracts\Asset\AssetInterface;
use Assetic\Filter\BaseFilter;
use MatthiasMullie\Minify\JS;

/**
 * Minifies JavaScript using MatthiasMullie\Minify\JS.
 *
 * Replaces the old Assetic JSqueezeFilter (patchwork/jsqueeze) and
 * JSMinFilter that are no longer available.
 *
 * @category  Xoops\Core\Filter
 * @package   Filter
 * @author    XOOPS Development Team
 * @copyright 2024-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 * @see       https://github.com/matthiasmullie/minify
 */
class MinifyJsFilter extends BaseFilter
{
    public function filterDump(AssetInterface $asset): void
    {
        $minifier = new JS();
        $minifier->add($asset->getContent());
        $asset->setContent($minifier->minify());
    }
}
