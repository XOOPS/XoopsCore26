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

namespace Xmf\Security;

/**
 * Format types for serialization (PHP 7.4+ compatible)
 *
 * @category  Xmf\Security
 * @package   Xmf
 * @author    MAMBA <mambax7@gmail.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
final class Format
{
    public const JSON = 'json';
    public const PHP = 'php';
    public const LEGACY = 'legacy';  // base64-encoded PHP serialize
    public const AUTO = 'auto';      // auto-detect format

    /**
     * Prevent instantiation
     */
    private function __construct()
    {
    }
}
