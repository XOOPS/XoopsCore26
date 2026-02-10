<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

namespace Xoops\Core\Text\Sanitizer\Extensions;

use Xoops\Core\Text\Sanitizer;
use Xoops\Core\Text\Sanitizer\FilterAbstract;

/**
 * TextSanitizer filter - clean up HTML text
 *
 * @category  Sanitizer
 * @package   Xoops\Core\Text
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2015-2016 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 */
class Embed extends FilterAbstract
{
    /**
     * @var array default configuration values
     */
    protected static $defaultConfiguration = [
        'enabled' => true,
        'cache_time' => 15552000, // 180 days in seconds
    ];

    /**
     * Make and URL's in the text clickable links
     *
     * @param string $text text string to filter
     *
     * @return mixed
     */
    public function applyFilter($text)
    {
        if (!$this->config['enabled']) {
            return $text;
        }

        $pattern = '/^(https?:\/\/([\p{L}\p{N}]{1,}\.){1,}[\p{L}\p{N}]{2,}[\/\?\.\-_&=:#\p{L}\p{N}]{0,})$/m';

        $text = preg_replace_callback(
            $pattern,
            [$this, 'decorateUrl'],
            $text
        );

        return $text;
    }

    /**
     * decorate a bare url with the help of embed/embed
     *
     * @param string $match string to be truncated
     *
     * @return string
     */
    protected function decorateUrl($match) {
        $url = $match[1];
        $decorated = null;
        $xoops = \Xoops::getInstance();
        $md5 = md5($url);
        $crc = hash("crc32b", $url);
        $key = implode('/', ['embed', substr($crc, -2), $md5]);
        //$xoops->cache()->delete($key);
        $decorated = $xoops->cache()->cacheRead(
            $key,
            function ($url) {
                $return = null;
                try {
                    $embed = new \Embed\Embed();
                    $info = $embed->get($url);
                } catch (\Exception $e) {
                    $info = null;
                }
                if (is_object($info)) {
                    $code = $info->code;
                    if (!empty($code)) {
                        $return = (string) $code;
                    }
                    if (empty($return)) {
                        $imageUrl = $info->image;
                        return $this->mediaBox(
                            (string) ($info->url ?? $url),
                            $imageUrl ? (string) $imageUrl : '',
                            $info->title ?? '',
                            $info->description ?? ''
                        );
                    }
                    $height = $code->height ?? null;
                    $width = $code->width ?? null;
                    if ($this->enableResponsive($return) && !empty($height) && !empty($width)) {
                        $ratio = (1.5 > ($width/$height)) ? '4by3' : '16by9';
                        $return = '<div class="embed-responsive embed-responsive-' . $ratio . '">' . $return . '</div>';
                    }
                }
                if (empty($return)) {
                    $return = $url;
                }
                return $return;
            },
            $this->config['cache_time'],
            $url
        );
        return $decorated;
    }

    protected function mediaBox($link, $imageSrc, $title, $description)
    {
        $htmlTemplate = <<<'EOT'
<div class="media">
  <a class="pull-left" href="%1$s" rel="external">
    <img src="%2$s" class="media-object" style="max-height: 128px; max-width: 128px;">
  </a>
  <div class="media-body">
    <h4 class="media-heading">%3$s</h4>
%4$s
  </div>
</div>
EOT;

        if(empty($imageSrc)) {
            $imageSrc = \Xoops::getInstance()->url('media/xoops/images/icons/link-ext.svg');
        }
        $box = sprintf($htmlTemplate, $link, $imageSrc, $title, $description);
        return $box;
    }

    /**
     * Check for know issues if wrapped in embed-responsive div
     *
     * @param string $code embed code to stest
     *
     * @return bool true if responsive should be enabled, false otherwise
     */
    protected function enableResponsive($code)
    {
        // sites in this list are known to have problems
        $excludeList = [
            'circuitlab.com',
        ];

        foreach ($excludeList as $test) {
            if (false !== stripos($code, $test)) {
                return false;
            }
        }
        return true;
    }
}
