<?php

declare(strict_types=1);

namespace Xmf\Test\Security;

use PHPUnit\Framework\Attributes\CoversClass;
use Xmf\Security\Format;
use Xmf\Security\SerializableTrait;
use Xmf\Security\Serializer;

class SerializableEntity
{
    use SerializableTrait;

    public $tags = ['a', 'b'];
    public $meta = ['n' => 1];
    public $broken;
    /** @var array<string, string> values written through setVar() */
    public $written = [];

    public function __construct()
    {
        $this->broken = new \stdClass();
    }

    public function setVar($key, $value)
    {
        $this->written[$key] = $value;
    }

    protected function getSerializableProperties(): array
    {
        return [
            'tags'    => Format::JSON,
            'meta'    => Format::PHP,
            'missing' => Format::JSON,
        ];
    }

    public function serialize($value, string $format = Format::JSON): string
    {
        return $this->serializeProperty($value, $format);
    }

    public function unserialize(string $data, $default = null)
    {
        return $this->unserializeProperty($data, $default);
    }
}

#[CoversClass(Serializer::class)]
class SerializableTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testSerializeProperties(): void
    {
        $entity = new SerializableEntity();

        $this->assertSame(
            ['tags' => '["a","b"]', 'meta' => serialize(['n' => 1])],
            $entity->serializeProperties()
        );
    }

    public function testSerializePropertyRejectsUnsupportedFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new SerializableEntity())->serialize([1], Format::LEGACY);
    }

    public function testUnserializePropertyAutoDetectsAndFallsBack(): void
    {
        $entity = new SerializableEntity();

        $this->assertSame([1], $entity->unserialize('[1]'));
        $this->assertSame([1], $entity->unserialize(serialize([1])));
        $this->assertSame('fallback', $entity->unserialize('{bad', 'fallback'));
    }

    public function testMigrateConvertsPhpPayloadToJson(): void
    {
        $entity = new SerializableEntity();

        $this->assertTrue($entity->migrateSerializedData('tags', serialize(['x', 'y'])));
        $this->assertSame(['tags' => '["x","y"]'], $entity->written);
    }

    public function testMigrateConvertsLegacyPayloadToJson(): void
    {
        $entity = new SerializableEntity();

        $this->assertTrue($entity->migrateSerializedData('meta', base64_encode(serialize(['k' => 'v']))));
        $this->assertSame(['meta' => '{"k":"v"}'], $entity->written);
    }

    public function testMigrateLeavesJsonPayloadAlone(): void
    {
        $entity = new SerializableEntity();

        $this->assertFalse($entity->migrateSerializedData('tags', '["already","json"]'));
        $this->assertSame([], $entity->written);
    }

    public function testMigrateKeepsDataWhenDecodingFails(): void
    {
        $this->markTestIncomplete(
            'Upstream XMF 1.3.2 bug: migrateSerializedData() decodes with tryFrom(), so a corrupt payload '
            . 'becomes null and is written back as the JSON string "null".'
        );
        $entity = new SerializableEntity();

        $this->assertFalse($entity->migrateSerializedData('tags', 'a:2:{i:0;s:1:"x";'));
        $this->assertSame([], $entity->written);
    }
}
