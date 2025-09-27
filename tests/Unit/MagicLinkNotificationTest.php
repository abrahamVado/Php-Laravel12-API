<?php

namespace Tests\Unit;

use App\Notifications\MagicLinkNotification;
use Illuminate\Notifications\Messages\MailMessage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MagicLinkNotificationTest extends TestCase
{
    #[Test]
    public function it_renders_the_action_url(): void
    {
        $url = 'https://example.com/magic-link?token=secret';

        $notification = new MagicLinkNotification($url);

        $mailMessage = $notification->toMail((object) []);

        $this->assertInstanceOf(MailMessage::class, $mailMessage);
        $this->assertSame($url, $mailMessage->actionUrl);
    }
}
