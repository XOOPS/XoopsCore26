<?php

namespace Xmf\Test\Mail;

use Xmf\Mail\SendmailException;
use Xmf\Mail\SendmailRunner;

class SendmailRunnerTest extends \PHPUnit\Framework\TestCase
{
    private array $cleanupPaths = array();

    protected function tearDown(): void
    {
        foreach ($this->cleanupPaths as $path) {
            if (file_exists($path)) {
                @unlink($path);
            }
        }
        $this->cleanupPaths = array();
    }

    public function testDeliverRejectsInvalidPath()
    {
        $runner = new SendmailRunner();

        $this->expectException(SendmailException::class);
        $this->expectExceptionMessage('Invalid sendmail path.');

        $runner->deliver('/definitely/not/a/sendmail-binary', 'Subject: test');
    }

    public function testDeliverWritesMessageToSendmailProcess()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Sendmail runner process test requires a POSIX shell.');
        }

        $outputFile = $this->createTempFile('sendmail-output-', '');
        $script = $this->createExecutableScript(
            "#!/usr/bin/env bash\ncat > " . escapeshellarg($outputFile) . "\nexit 0\n"
        );
        $runner = new SendmailRunner(array($script));
        $message = "Subject: Test\n\nHello world\n";

        $runner->deliver($script, $message);

        $this->assertSame("Subject: Test\r\n\r\nHello world\r\n", file_get_contents($outputFile));
    }

    public function testDeliverThrowsOnNonZeroExitCode()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Sendmail runner process test requires a POSIX shell.');
        }

        $script = $this->createExecutableScript(
            "#!/usr/bin/env bash\ncat > /dev/null\nprintf 'boom\\n' >&2\nexit 1\n"
        );
        $runner = new SendmailRunner(array($script));

        set_error_handler(static function (): bool {
            return true;
        });

        try {
            $this->expectException(SendmailException::class);
            $this->expectExceptionMessage('Sendmail exited with code 1: boom');

            $runner->deliver($script, "Subject: Test\n\nHello world\n");
        } finally {
            restore_error_handler();
        }
    }

    public function testDeliverWarnsWhenSendmailWritesToStderrOnSuccess()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Sendmail runner process test requires a POSIX shell.');
        }

        $script = $this->createExecutableScript(
            "#!/usr/bin/env bash\ncat > /dev/null\nprintf 'minor warning\\n' >&2\nexit 0\n"
        );
        $runner = new SendmailRunner(array($script));
        $warning = null;

        set_error_handler(static function (int $errno, string $errstr) use (&$warning): bool {
            $warning = $errstr;
            return true;
        });

        try {
            $runner->deliver($script, "Subject: Test\n\nHello world\n");
        } finally {
            restore_error_handler();
        }

        $this->assertSame('sendmail warning (success): minor warning\n', $warning);
    }

    public function testDeliverEmptyPayloadDoesNotHang()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Sendmail runner process test requires a POSIX shell.');
        }

        $outputFile = $this->createTempFile('sendmail-output-', '');
        $script = $this->createExecutableScript(
            "#!/usr/bin/env bash\ncat > " . escapeshellarg($outputFile) . "\nexit 0\n"
        );
        $runner = new SendmailRunner(array($script));

        $runner->deliver($script, '');

        $this->assertSame('', file_get_contents($outputFile));
    }

    private function createTempFile(string $prefix, string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);
        $this->assertNotFalse($path, 'Failed to create temporary file.');
        file_put_contents($path, $contents);
        $this->cleanupPaths[] = $path;

        return $path;
    }

    private function createExecutableScript(string $contents): string
    {
        $path = $this->createTempFile('sendmail-script-', $contents);
        chmod($path, 0700);

        return $path;
    }
}
