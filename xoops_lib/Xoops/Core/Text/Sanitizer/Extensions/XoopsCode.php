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
use Xoops\Core\Text\Sanitizer\ExtensionAbstract;

/**
 * TextSanitizer extension
 *
 * @category  Sanitizer
 * @package   Xoops\Core\Text
 * @author    iHackCode <https://github.com/ihackcode>
 * @copyright 2011-2015 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 */
class XoopsCode extends ExtensionAbstract
{
    /**
     * @var array default configuration values
     */
    protected static $defaultConfiguration = ['enabled' => true];

    /**
     * Register extension with the supplied sanitizer instance
     *
     * @return void
     */
    public function registerExtensionProcessing()
    {
        $shortcodes = $this->shortcodes;

        // Attribute values are unescaped by stripcslashes() in ShortCodes, so "\x22"
        // arrives here as a real quote: every value is validated, then escaped.
        // a target with a scheme must be a web link; any other target is made
        // site-relative, so it cannot start with a scheme once rendered
        $link = function ($attributes, $content) use ($shortcodes) {
            $target = ltrim($attributes[0] ?? '', '=');
            if (1 === preg_match('#^[a-z][a-z0-9+.-]*:#i', $target)) {
                $url = 1 === preg_match('#^(?:https?|ftps?)://#i', $target) ? $target : '';
            } else {
                $url = \Xoops::getInstance()->path($target, true);
            }
            if ('' === $url) {
                return $shortcodes->process($content);
            }
            return '<a href="' . self::attr($url) . '">' . $shortcodes->process($content) . '</a>';
        };
        $shortcodes->addShortcode('siteurl', $link);
        $shortcodes->addShortcode('url', $link);

        $style = function ($property, $pattern) use ($shortcodes) {
            return function ($attributes, $content) use ($shortcodes, $property, $pattern) {
                $value = trim(ltrim($attributes[0] ?? '', '='));
                if ('color' === $property && 1 === preg_match('/^(?:[a-f0-9]{3}|[a-f0-9]{6})\z/i', $value)) {
                    $value = '#' . $value;
                }
                if (1 !== preg_match($pattern, $value)) {
                    return $shortcodes->process($content);
                }
                return '<span style="' . $property . ': ' . self::attr($value) . '">'
                    . $shortcodes->process($content) . '</span>';
            };
        };
        $shortcodes->addShortcode('color', $style('color', '/^(?:#[a-f0-9]{3}|#[a-f0-9]{6}|[a-z]{3,20})\z/i'));
        $shortcodes->addShortcode(
            'size',
            $style('font-size', '/^(?:[a-z-]{3,10}|\d{1,3}(?:\.\d{1,2})?(?:px|pt|em|rem|%))\z/i')
        );
        $shortcodes->addShortcode('font', $style('font-family', '/^[a-z0-9\'][a-z0-9 ,\'-]{0,63}\z/i'));

        $shortcodes->addShortcode(
            'email',
            function ($attributes, $content, $tagName) {
                $content = trim($content);
                if (false === filter_var($content, FILTER_VALIDATE_EMAIL)) {
                    return self::attr($content);
                }
                return '<a href="mailto:' . self::attr($content) . '">' . self::attr($content) . '</a>';
            }
        );

        $shortcodes->addShortcode(
            'b',
            function ($attributes, $content, $tagName) use ($shortcodes) {
                $newcontent = '<strong>' . $shortcodes->process($content) . '</strong>';
                return $newcontent;
            }
        );

        $shortcodes->addShortcode(
            'i',
            function ($attributes, $content, $tagName) use ($shortcodes) {
                $newcontent = '<em>' . $shortcodes->process($content) . '</em>';
                return $newcontent;
            }
        );

        $shortcodes->addShortcode(
            'u',
            function ($attributes, $content, $tagName) use ($shortcodes) {
                $newcontent = '<u>' . $shortcodes->process($content) . '</u>';
                return $newcontent;
            }
        );

        $shortcodes->addShortcode(
            'd',
            function ($attributes, $content, $tagName) use ($shortcodes) {
                $newcontent = '<del>' . $shortcodes->process($content) . '</del>';
                return $newcontent;
            }
        );

        $shortcodes->addShortcode(
            'center',
            function ($attributes, $content, $tagName) use ($shortcodes) {
                $newcontent = '<div style="text-align: center;">' . $shortcodes->process($content) . '</div>';
                return $newcontent;
            }
        );

        $shortcodes->addShortcode(
            'left',
            function ($attributes, $content, $tagName) use ($shortcodes) {
                $newcontent = '<div style="text-align: left;">' . $shortcodes->process($content) . '</div>';
                return $newcontent;
            }
        );

        $shortcodes->addShortcode(
            'right',
            function ($attributes, $content, $tagName) use ($shortcodes) {
                $newcontent = '<div style="text-align: right;">' . $shortcodes->process($content) . '</div>';
                return $newcontent;
            }
        );
    }

    /**
     * Escape a value for a double-quoted HTML attribute; entities already in it are kept
     *
     * @param string $value value to escape
     *
     * @return string
     */
    private static function attr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }
}
