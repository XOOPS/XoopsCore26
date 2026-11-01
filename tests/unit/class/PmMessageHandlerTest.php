<?php
require_once(__DIR__.'/../init_new.php');
require_once(XOOPS_ROOT_PATH.'/modules/pm/class/message.php');

use PHPUnit\Framework\Attributes\DataProvider;
use Xoops\Core\Kernel\CriteriaElement;
use Xoops\Core\Kernel\XoopsObject;

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

    public static function persistedCases(): array
    {
        // [to_userid, to_save, from_userid, from_delete, from_save, folder, final row (null = removed)]
        return [
            'inbox side of a two-party message' => [self::ME, 0, self::OTHER, 0, 0, 'in', ['to_delete' => 1, 'from_delete' => 0]],
            'self message saved on both sides'  => [self::ME, 1, self::ME, 0, 1, 'save', null],
            'saved, sender side deleted'        => [self::ME, 1, self::ME, 1, 0, 'save', null],
            'self message from inbox only'      => [self::ME, 0, self::ME, 0, 0, 'in', ['to_delete' => 1, 'from_delete' => 0]],
        ];
    }

    /**
     * Runs the real setTodelete()/setFromDelete() against an in-memory row.
     */
    #[DataProvider('persistedCases')]
    public function testDeleteFromFolderPersistedRow(
        int $toUid,
        int $toSave,
        int $fromUid,
        int $fromDelete,
        int $fromSave,
        string $folder,
        ?array $expected
    ): void {
        $message = new PmMessage();
        $message->setVars([
            'msg_id' => 1, 'to_userid' => $toUid, 'to_delete' => 0, 'to_save' => $toSave,
            'from_userid' => $fromUid, 'from_delete' => $fromDelete, 'from_save' => $fromSave,
        ]);
        $handler = new class extends PmMessageHandler {
            public ?array $row;

            public function __construct()
            {
            }

            public function updateAll($fieldname, $fieldvalue, CriteriaElement $criteria, $force = false)
            {
                if (null === $this->row) {
                    return false; // no row to update
                }
                $this->row[$fieldname] = $fieldvalue;
                return true;
            }

            public function delete(XoopsObject $object, $force = false)
            {
                if (null === $this->row) {
                    return false;
                }
                $this->row = null;
                return true;
            }
        };
        $handler->row = ['to_delete' => 0, 'from_delete' => $fromDelete];

        $this->assertTrue($handler->deleteFromFolder($message, self::ME, $folder));
        $this->assertSame($expected, $handler->row);
    }
}
