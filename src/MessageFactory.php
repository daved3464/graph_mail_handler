<?php

declare(strict_types=1);

namespace Hollow3464\GraphMailHandler;

use Microsoft\Graph\Model\FileAttachment;
use Exception;

final class MessageFactory
{
    public const LOW_IMPORTANCE =  "low";
    public const NORMAL_IMPORTANCE = "normal";
    public const HIGH_IMPORTANCE = "high";

    public const HTML_CONTENT = 'html';
    public const TEXT_CONTENT = 'text';

    /**
     * @var array<string,string|array<string>|array<string,array<string>>>
     */
    private array|null $message = null;

    public function __construct()
    {
        $this->message =  [
            'subject' => '',
            'importance' => 'normal',
            'body' => [
                'contentType' => 'html',
                'content' => '',
            ],
            'toRecipients' => [],
            'attachments' => []
        ];
    }

    /**
     * @throws Exception
     */
    // @phpstan-ignore-next-line
    public function build(): array
    {
        // @phpstan-ignore-next-line
        if (!$this->message['body']['contentType']) {
            throw new Exception("Content type must be set", 1);
        }

        if (!$this->message['body']['content']) {
            throw new Exception("Content must be set", 1);
        }

        // @phpstan-ignore-next-line
        if (!count($this->message['toRecipients'])) {
            throw new Exception("Cant create message without recipients", 1);
        }

        return $this->message;
    }

    public function setLowImportance(): static
    {
        $this->message['importance'] = self::LOW_IMPORTANCE;

        return $this;
    }

    public function setNormalImportance(): static
    {
        $this->message['importance'] = self::NORMAL_IMPORTANCE;

        return $this;
    }

    public function setHighImportance(): static
    {
        $this->message['importance'] = self::HIGH_IMPORTANCE;

        return $this;
    }

    public function setContentToHTML(): static
    {
        // @phpstan-ignore-next-line
        $this->message['body']['contentType'] = 'html';

        return $this;
    }

    public function setContentToText(): static
    {
        // @phpstan-ignore-next-line
        $this->message['body']['contentType'] = 'text';

        return $this;
    }

    public function setBody(string $body): static
    {
        // @phpstan-ignore-next-line
        $this->message['body']['content'] = $body;
        return $this;
    }

    public function setSubject(string $subject): static
    {
        $this->message['subject'] = $subject;

        return $this;
    }

    public function addRecipient(string $address): static
    {
        // @phpstan-ignore-next-line
        $this->message['toRecipients'][] = ['emailAddress' => ['address' => $address]];

        return $this;
    }

    public function addAttachment(FileAttachment $file): static
    {
        if ($file->getSize() > GraphMailHandler::MIN_UPLOAD_SESSION_SIZE) {
            throw new Exception("The attachment size cannot be greater than 3MB", 1);
        }

        $attachment = $file->jsonSerialize();
        $attachment['contentBytes'] = base64_encode((string) $file->getContentBytes());

        // @phpstan-ignore-next-line
        $this->message['attachments'][] = $attachment;

        return $this;
    }
}
