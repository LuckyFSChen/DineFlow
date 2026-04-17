<?php

namespace App\Mail\Transport;

use App\Services\MicrosoftGraphMailer;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MicrosoftGraphTransport extends AbstractTransport
{
    public function __construct(private readonly MicrosoftGraphMailer $graphMailer)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $original = $message->getOriginalMessage();

        if (! $original instanceof Email) {
            throw new TransportException('Microsoft Graph transport only supports Email messages.');
        }

        $recipients = array_map(
            static fn (Address $address): string => $address->getAddress(),
            $message->getEnvelope()->getRecipients()
        );

        if ($recipients === []) {
            throw new TransportException('Microsoft Graph transport requires at least one recipient.');
        }

        $htmlBody = $original->getHtmlBody();
        $textBody = $original->getTextBody();

        $body = is_string($htmlBody) ? $htmlBody : ((string) $textBody);
        $isHtml = is_string($htmlBody);

        $cc = array_map(static fn (Address $address): string => $address->getAddress(), $original->getCc());
        $bcc = array_map(static fn (Address $address): string => $address->getAddress(), $original->getBcc());

        try {
            $this->graphMailer->sendMessage(
                $recipients,
                (string) ($original->getSubject() ?? ''),
                $body,
                $isHtml,
                $cc,
                $bcc,
                true,
            );
        } catch (\Throwable $e) {
            throw new TransportException($e->getMessage(), 0, $e);
        }
    }

    public function __toString(): string
    {
        return 'graph://default';
    }
}
