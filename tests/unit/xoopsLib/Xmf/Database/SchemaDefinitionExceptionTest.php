<?php

namespace Xmf\Test\Database;

use Xmf\Database\SchemaDefinitionException;

class SchemaDefinitionExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testForFileUsesBasenameInMessage()
    {
        $exception = SchemaDefinitionException::forFile('/tmp/module/schema.yml');

        $this->assertSame('No schema definition schema.yml', $exception->getMessage());
    }

    public function testForTableBuildsExpectedMessage()
    {
        $exception = SchemaDefinitionException::forTable('xoops_users');

        $this->assertSame('No schema definition for table xoops_users', $exception->getMessage());
    }
}
