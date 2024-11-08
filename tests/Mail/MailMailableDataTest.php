<?php

declare(strict_types=1);

namespace SwooleTW\Hyperf\Tests\Mail;

use PHPUnit\Framework\TestCase;
use SwooleTW\Hyperf\Mail\Mailable;

/**
 * @internal
 * @coversNothing
 */
class MailMailableDataTest extends TestCase
{
    public function testMailableDataIsNotLost()
    {
        $testData = ['first_name' => 'James'];

        $mailable = new MailableStub();
        $mailable->build(function ($m) use ($testData) {
            $m->view('view', $testData);
        });
        $this->assertSame($testData, $mailable->buildViewData());

        $mailable = new MailableStub();
        $mailable->build(function ($m) use ($testData) {
            $m->view('view', $testData)
                ->text('text-view');
        });
        $this->assertSame($testData, $mailable->buildViewData());
    }
}

class MailableStub extends Mailable
{
    /**
     * Build the message.
     *
     * @param mixed $builder
     * @return $this
     */
    public function build($builder)
    {
        $builder($this);
    }
}