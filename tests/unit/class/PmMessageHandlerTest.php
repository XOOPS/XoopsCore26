<?php
require_once(__DIR__.'/../init_new.php');
require_once(XOOPS_ROOT_PATH.'/modules/pm/class/message.php');

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * PmMessageHandler::deleteFromFolder() picks the sides a user has in a folder.
 */
class PmMessageHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const ME = 5;
    private const OTHER = 9;

    public static function folderCases(): array
    {
        // [to_userid, to_delete, to_save, from_userid, from_delete, from_save, folder, expected calls]
        return [
            'inbox message'                => [self::ME, 0, 0, self::OTHER, 0, 0, 'in', ['to']],
            'outbox message'               => [self::OTHER, 0, 0, self::ME, 0, 0, 'out', ['from']],
            'not mine'                     => [self::OTHER, 0, 0, self::OTHER, 0, 0, 'in', null],
            'inbox id posted from outbox'  => [self::ME, 0, 0, self::OTHER, 0, 0, 'out', null],
            'saved message not in inbox'   => [self::ME, 0, 1, self::OTHER, 0, 0, 'in', null],
            'self message from inbox'      => [self::ME, 0, 0, self::ME, 0, 0, 'in', ['to']],
            'self message from outbox'     => [self::ME, 0, 0, self::ME, 0, 0, 'out', ['from']],
            'self message both saved'      => [self::ME, 0, 1, self::ME, 0, 1, 'save', ['to', 'from']],
            'self message only to saved'   => [self::ME, 0, 1, self::ME, 0, 0, 'save', ['to']],
            'self message only from saved' => [self::ME, 0, 0, self::ME, 0, 1, 'save', ['from']],
            'saved, sender side deleted'   => [self::ME, 0, 1, self::ME, 1, 0, 'save', ['to']],
            'saved side already deleted'   => [self::ME, 1, 1, self::OTHER, 0, 0, 'save', null],
        ];
    }

    #[DataProvider('folderCases')]
    public function testDeleteFromFolder(
        int $toUid,
        int $toDelete,
        int $toSave,
        int $fromUid,
        int $fromDelete,
        int $fromSave,
        string $folder,
        ?array $expected
    ): void {
        $pm = new PmMessage();
        $pm->setVars([
            'to_userid' => $toUid, 'to_delete' => $toDelete, 'to_save' => $toSave,
            'from_userid' => $fromUid, 'from_delete' => $fromDelete, 'from_save' => $fromSave,
        ]);
        $handler = new class extends PmMessageHandler {
            public array $calls = [];

            public function __construct()
            {
            }

            public function setTodelete(PmMessage $pm, $val = 1)
            {
                $this->calls[] = 'to';
                return true;
            }

            public function setFromDelete(PmMessage $pm, $val = 1)
            {
                $this->calls[] = 'from';
                return true;
            }
        };

        $result = $handler->deleteFromFolder($pm, self::ME, $folder);

        $this->assertSame(null === $expected ? null : true, $result);
        $this->assertSame($expected ?? [], $handler->calls);
    }
}
