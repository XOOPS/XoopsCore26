<?php
require_once(__DIR__.'/../init_new.php');
require_once(XOOPS_ROOT_PATH.'/modules/profile/class/field.php');

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * A profile field name becomes a column, a form element name and a JavaScript
 * identifier, and must never rebind an account column.
 */
class ProfileFieldNameTest extends \PHPUnit\Framework\TestCase
{
    public static function names(): array
    {
        return [
            'default field'          => ['user_from', true],
            'mixed case'             => ['MyField2', true],
            'account id'             => ['uid', false],
            'account id, upper case' => ['UID', false],
            'password'               => ['pass', false],
            'level'                  => ['level', false],
            'email'                  => ['email', false],
            'activation key'         => ['actkey', false],
            'profile row id'         => ['profile_id', false],
            'leading digit'          => ['1field', false],
            'script breakout'        => ['a;alert(1)//', false],
            'backtick'               => ['a`b', false],
            'trailing newline'       => ["name\n", false],
            'empty'                  => ['', false],
            'too long'               => [str_repeat('a', 65), false],
            'longest allowed'        => [str_repeat('a', 64), true],
        ];
    }

    #[DataProvider('names')]
    public function testIsValidFieldName(string $name, bool $expected): void
    {
        $this->assertSame($expected, ProfileFieldHandler::isValidFieldName($name));
    }
}
