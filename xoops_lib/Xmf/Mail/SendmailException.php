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

namespace Xmf\Mail;

/**
 * Specific exception for sendmail delivery failures.
 *
 * @category  Xmf\Mail
 * @package   Xmf
 * @author    XOOPS Development Team <contact@xoops.org>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
final class SendmailException extends \RuntimeException
{
    /**
     * Build an exception for an invalid sendmail path.
     *
     * @return self
     */
    public static function invalidPath(): self
    {
        return new self('Invalid sendmail path.');
    }

    /**
     * Build an exception for a sendmail process startup failure.
     *
     * @return self
     */
    public static function failedToStartProcess(): self
    {
        return new self('Failed to start sendmail process.');
    }

    /**
     * Build an exception for invalid sendmail pipe resources.
     *
     * @return self
     */
    public static function failedToOpenPipes(): self
    {
        return new self('Failed to open sendmail pipes.');
    }

    /**
     * Build an exception for a broken sendmail stdin pipe.
     *
     * @return self
     */
    public static function writeFailure(): self
    {
        return new self('Failed to write message to sendmail (broken pipe).');
    }

    /**
     * Build an exception for a sendmail process closing stdin too early.
     *
     * @return self
     */
    public static function prematurePipeClosure(): self
    {
        return new self('sendmail closed the input pipe prematurely.');
    }

    /**
     * Build an exception for a non-zero sendmail exit code.
     *
     * @param int    $code      process exit code
     * @param string $firstLine first stderr line
     *
     * @return self
     */
    public static function exitedWithCode(int $code, string $firstLine = ''): self
    {
        return new self('Sendmail exited with code ' . $code . ($firstLine !== '' ? ': ' . $firstLine : ''));
    }
}
