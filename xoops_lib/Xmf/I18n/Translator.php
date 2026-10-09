<?php

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

declare(strict_types=1);

namespace Xmf\I18n;

/**
 * Minimal translator that resolves XOOPS-style constant labels.
 *
 * @category  Xmf\I18n\Translator
 * @package   Xmf
 * @author    MAMBA <mambax7@gmail.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
final class Translator
{
    /**
     * Translate a label string by resolving it as a PHP constant if it
     * matches the XOOPS naming convention (_UPPERCASE_NAME).
     *
     * Note: The regex restricts lookup to uppercase constants starting
     * with an underscore, which limits the set of resolvable constants.
     *
     * @param string $label Label to translate (e.g. '_MI_MYMODULE_NAME')
     *
     * @return string Resolved constant value, or the raw label if not a defined constant
     */
    public static function t(string $label): string
    {
        if (\preg_match('/^_[A-Z][A-Z0-9_]*$/', $label) === 1 && \defined($label)) {
            $value = \constant($label);
            return \is_string($value) ? $value : $label;
        }
        return $label;
    }
}
