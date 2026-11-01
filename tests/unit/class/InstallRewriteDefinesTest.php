<?php
require_once(__DIR__.'/../../../htdocs/install/include/functions.php');

use PHPUnit\Framework\Attributes\DataProvider;

class InstallRewriteDefinesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Values that broke out of the generated define() with the old addslashes()
     * plus replacement-string code.
     */
    public static function hostileValues()
    {
        return [
            'backslash quote' => ["x\\'); phpinfo(); //"],
            'replacement ref' => ['$1${2}\\0'],
            'double quote'    => ['x"); phpinfo(); //'],
            'close tag'       => ['http://example.com/?>'."<?php phpinfo(); ?>"],
            'newline'         => ["a\nphpinfo();"],
        ];
    }

    #[DataProvider('hostileValues')]
    public function testHostileValuesStayStringLiterals($value)
    {
        foreach (['/htdocs/mainfile.dist.php', '/xoops_data/data/secure.dist.php'] as $dist) {
            $template = file_get_contents(__DIR__ . '/../../..' . $dist);
            $out = install_rewriteDefines($template, ['URL' => $value, 'DB_PASS' => $value]);

            $key = strpos($dist, 'secure') ? 'DB_PASS' : 'URL';
            $this->assertStringContainsString(
                "define('XOOPS_{$key}', " . var_export($value, true) . ')',
                $out,
                $dist
            );
            foreach (token_get_all($out) as $i => $token) {
                if ($i > 0 && is_array($token)) {
                    $this->assertNotSame(T_INLINE_HTML, $token[0], $dist . ': value left PHP mode');
                    $this->assertNotSame(T_OPEN_TAG, $token[0], $dist . ': value opened PHP');
                }
            }
            $this->assertSame(
                substr_count($template, 'define('),
                substr_count($out, 'define('),
                $dist . ': value added a define()'
            );
        }
    }

    public function testCommentedExampleIsNotRewritten()
    {
        $template = file_get_contents(__DIR__ . '/../../../htdocs/mainfile.dist.php');
        $out = install_rewriteDefines($template, ['URL' => 'http://example.com']);
        $this->assertStringContainsString('// Example: define("XOOPS_URL", "http://url_to_xoops_directory");', $out);
        $this->assertStringContainsString("define('XOOPS_URL', 'http://example.com')", $out);
    }

    public function testIntegerDefinesKeepTheirType()
    {
        $template = file_get_contents(__DIR__ . '/../../../xoops_data/data/secure.dist.php');
        $out = install_rewriteDefines($template, ['DB_PCONNECT' => 1, 'DB_NAME' => 7]);
        $this->assertStringContainsString("define('XOOPS_DB_PCONNECT', 1)", $out);
        $this->assertStringContainsString("define('XOOPS_DB_NAME', '7')", $out);

        $out = install_rewriteDefines($template, ['DB_PCONNECT' => '1); phpinfo(']);
        $this->assertStringContainsString('define("XOOPS_DB_PCONNECT", 0)', $out);
    }

    public function testUnexpectedKeysAreIgnored()
    {
        $template = "<?php\ndefine('XOOPS_URL', '');\n";
        $this->assertSame($template, install_rewriteDefines($template, ['URL|.*' => 'x', 'authorized' => true]));
    }
}
