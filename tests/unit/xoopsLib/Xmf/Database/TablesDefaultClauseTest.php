<?php

declare(strict_types=1);

namespace Xmf\Test\Database;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Xmf\Database\Tables;

#[CoversClass(Tables::class)]
class TablesDefaultClauseTest extends \PHPUnit\Framework\TestCase
{
    public static function defaults(): array
    {
        return [
            'no default'             => [null, ''],
            'mysql timestamp'        => ['CURRENT_TIMESTAMP', ' DEFAULT CURRENT_TIMESTAMP '],
            'mariadb timestamp'      => ['current_timestamp()', ' DEFAULT CURRENT_TIMESTAMP() '],
            'timestamp precision'    => ['CURRENT_TIMESTAMP(6)', ' DEFAULT CURRENT_TIMESTAMP(6) '],
            'plain string'           => ['abc', " DEFAULT 'abc' "],
            'embedded quote'         => ["it's", " DEFAULT 'it''s' "],
            'timestamp-like literal' => ['current_timestamp_x', " DEFAULT 'current_timestamp_x' "],
            'trailing newline'       => ["CURRENT_TIMESTAMP\n", " DEFAULT 'CURRENT_TIMESTAMP\n' "],
        ];
    }

    #[DataProvider('defaults')]
    public function testQuoteDefaultClause(?string $default, string $expected): void
    {
        $tables = (new \ReflectionClass(Tables::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(Tables::class, 'quoteDefaultClause');

        $this->assertSame($expected, $method->invoke($tables, $default));
    }
}
