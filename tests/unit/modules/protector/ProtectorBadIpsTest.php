<?php
require_once __DIR__ . '/../../init_new.php';
require_once __DIR__ . '/../../../../htdocs/modules/protector/class/protector.php';

/**
 * Protector with the bad-IP list in a directory of the test's own and no config cache
 */
class ProtectorBadIpsDouble extends \Protector
{
    /** @var string */
    public $badipsPath = '';

    /** @var bool[] whether the write lock was held at each read */
    public $lockedAtRead = array();

    public function __construct()
    {
        // deliberately empty: Protector's own constructor reads the site's config cache
    }

    public function get_filepath4badips()
    {
        return $this->badipsPath;
    }

    public function get_bad_ips($with_jailed_time = false, &$readable = null)
    {
        $probe = fopen($this->badipsPath . '.lock', 'c');
        $free = flock($probe, LOCK_EX | LOCK_NB);
        fclose($probe);
        $this->lockedAtRead[] = !$free;

        return parent::get_bad_ips($with_jailed_time, $readable);
    }
}

class ProtectorBadIpsTest extends \PHPUnit\Framework\TestCase
{
    /** @var string */
    private $dir;

    /** @var ProtectorBadIpsDouble */
    private $protector;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/protector_badips_' . bin2hex(random_bytes(6));
        mkdir($this->dir);
        $this->protector = new ProtectorBadIpsDouble();
        $this->protector->badipsPath = $this->dir . '/badips';
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*'));
        rmdir($this->dir);
    }

    public function testRegisterAddsToTheStoredList()
    {
        $until = time() + 600;

        $this->assertTrue($this->protector->register_bad_ips($until, '192.0.2.1'));
        $this->assertTrue($this->protector->register_bad_ips(0, '192.0.2.2'));

        $this->assertSame(
            array('192.0.2.1' => $until, '192.0.2.2' => 0x7fffffff),
            $this->protector->get_bad_ips(true)
        );
        $this->assertSame(array('192.0.2.1', '192.0.2.2'), $this->protector->get_bad_ips());
    }

    public function testRegisterReadsAndWritesUnderTheLock()
    {
        $this->protector->register_bad_ips(0, '192.0.2.1');
        $this->assertSame(array(true), $this->protector->lockedAtRead);

        // a plain read takes no lock
        $this->protector->get_bad_ips();
        $this->assertSame(array(true, false), $this->protector->lockedAtRead);
    }

    public function testWriteLeavesOnlyTheListAndTheLockFile()
    {
        $this->protector->register_bad_ips(0, '192.0.2.1');
        $this->protector->write_file_badips(array('192.0.2.2' => 0x7fffffff));

        $files = array_map('basename', glob($this->dir . '/*'));
        sort($files);
        $this->assertSame(array('badips', 'badips.lock'), $files);
    }

    public function testFailedWriteReportsFalseAndKeepsTheStoredList()
    {
        $this->protector->register_bad_ips(0, '192.0.2.1');
        $stored = file_get_contents($this->protector->badipsPath);

        // the list path is a directory, so the new list cannot be swapped in
        $blocked = new ProtectorBadIpsDouble();
        $blocked->badipsPath = $this->dir . '/blocked';
        mkdir($blocked->badipsPath);
        touch($blocked->badipsPath . '/entry');
        try {
            $this->assertFalse($blocked->write_file_badips(array('192.0.2.2' => 0x7fffffff)));
            $this->assertSame(array('entry'), array_map('basename', glob($blocked->badipsPath . '/*')));
        } finally {
            unlink($blocked->badipsPath . '/entry');
            rmdir($blocked->badipsPath);
        }

        $this->assertSame($stored, file_get_contents($this->protector->badipsPath));
        $this->assertSame(array(), glob($this->dir . '/*.tmp'));
    }

    public function testMissingAndEmptyListsAreReadable()
    {
        $this->assertSame(array(), $this->protector->get_bad_ips(true, $readable));
        $this->assertTrue($readable);

        file_put_contents($this->protector->badipsPath, "\n");
        $this->assertSame(array(), $this->protector->get_bad_ips(true, $readable));
        $this->assertTrue($readable);
        $this->assertTrue($this->protector->register_bad_ips(0, '192.0.2.1'));
    }

    public static function unreadableLists()
    {
        return array(
            'not serialized' => array('192.0.2.9'),
            'truncated' => array(substr(serialize(array('192.0.2.9' => 0x7fffffff)), 0, -4)),
            'list shaped' => array(serialize(array('192.0.2.9'))),
            'scalar' => array(serialize('192.0.2.9')),
            'object' => array('O:8:"stdClass":0:{}'),
            'object in the map' => array('a:1:{s:9:"192.0.2.9";O:8:"stdClass":0:{}}'),
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('unreadableLists')]
    public function testRegisterDoesNotReplaceAnUnreadableList($stored)
    {
        file_put_contents($this->protector->badipsPath, $stored);

        $this->assertSame(array(), $this->protector->get_bad_ips(true, $readable));
        $this->assertFalse($readable);
        $this->assertFalse($this->protector->register_bad_ips(0, '192.0.2.1'));
        $this->assertSame($stored, file_get_contents($this->protector->badipsPath));

        // saving the whole list, as the admin page does, is the way out
        $this->assertTrue($this->protector->write_file_badips(array('192.0.2.2' => 0x7fffffff)));
        $this->assertSame(array('192.0.2.2'), $this->protector->get_bad_ips());
    }

    public function testExpiredBansAreDroppedAndNumericKeysKept()
    {
        $now = time();
        $this->protector->write_file_badips(array(
            '192.0.2.1' => $now - 10,
            '2130706433' => $now + 600,
            '192.0.2.3' => $now + 900,
        ));

        $this->assertSame(
            array(2130706433 => $now + 600, '192.0.2.3' => $now + 900),
            $this->protector->get_bad_ips(true)
        );
    }

    public function testTrimFitsTheLimitKeepsTheNewEntryAndDropsNoMoreThanNeeded()
    {
        $base = time() + 3600;
        $bad_ips = array();
        for ($i = 0; strlen(serialize($bad_ips)) <= \Protector::BADIPS_MAX_BYTES + 5000; $i += 1000) {
            for ($j = $i; $j < $i + 1000; ++$j) {
                $bad_ips[long2ip(0x0A000000 + $j)] = $base + $j;
            }
        }
        // the entry to keep ends first, so an unprotected trim would drop it before any other
        $keep = '192.0.2.1';
        $bad_ips[$keep] = $base - 1;

        $this->assertTrue($this->protector->write_file_badips($bad_ips, $keep));

        $data = trim(file_get_contents($this->protector->badipsPath));
        $this->assertLessThanOrEqual(\Protector::BADIPS_MAX_BYTES, strlen($data));

        // through the production reader: the trimmed file must still be one Protector can read
        $stored = $this->protector->get_bad_ips(true, $readable);
        $this->assertTrue($readable);
        $this->assertSame($base - 1, $stored[$keep]);

        $dropped = array_diff_key($bad_ips, $stored);
        $this->assertNotEmpty($dropped);
        // only the bans that end first went
        $this->assertLessThan(min(array_diff_key($stored, array($keep => 0))), max($dropped));
        // and one fewer would not have fitted
        $oneFewer = $stored + array(array_key_last($dropped) => end($dropped));
        $this->assertGreaterThan(\Protector::BADIPS_MAX_BYTES, strlen(serialize($oneFewer)));
    }
}
