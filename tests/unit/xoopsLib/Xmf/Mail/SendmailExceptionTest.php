<?php

namespace Xmf\Test\Mail;

use Xmf\Mail\SendmailException;

class SendmailExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testFactoryMethodsBuildExpectedMessages()
    {
        $this->assertSame('Invalid sendmail path.', SendmailException::invalidPath()->getMessage());
        $this->assertSame('Failed to start sendmail process.', SendmailException::failedToStartProcess()->getMessage());
        $this->assertSame('Failed to open sendmail pipes.', SendmailException::failedToOpenPipes()->getMessage());
        $this->assertSame('Failed to write message to sendmail (broken pipe).', SendmailException::writeFailure()->getMessage());
        $this->assertSame('sendmail closed the input pipe prematurely.', SendmailException::prematurePipeClosure()->getMessage());
        $this->assertSame('Sendmail exited with code 1: failed', SendmailException::exitedWithCode(1, 'failed')->getMessage());
    }
}
