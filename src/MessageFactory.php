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

    public function build()
    {
        if (!$this->message['body']['contentType']) {
            throw new Exception("Content type must be set", 1);
        }

        if (!$this->message['body']['content']) {
            throw new Exception("Content must be set", 1);
        }

        if (!count($this->message['toRecipients'])) {
            throw new Exception("Cant create message without recipients", 1);
        }

        return $this->message;
    }

    public function setLowImportance()
    {
        $this->message['importance'] = self::LOW_IMPORTANCE;

        return $this;
    }

    public function setNormalImportance()
    {
        $this->message['importance'] = self::NORMAL_IMPORTANCE;

        return $this;
    }

    public function setHighImportance()
    {
        $this->message['importance'] = self::HIGH_IMPORTANCE;

        return $this;
    }

    public function setContentToHTML()
    {
        $this->message['body']['contentType'] = 'html';

        return $this;
    }

    public function setContentToText()
    {
        $this->message['body']['contentType'] = 'text';

        return $this;
    }

    public function setBody(string $body)
    {
        $this->message['body']['content'] = $body;
        return $this;
    }

    public function setSubject(string $subject)
    {
        $this->message['subject'] = $subject;

        return $this;
    }

    public function addRecipient(string $address)
    {
        $this->message['toRecipients'][] = ['emailAddress' => ['address' => $address]];

        return $this;
    }

    public function addAttachment(FileAttachment $file)
    {
        if ($file->getSize() > GraphMailHandler::MIN_UPLOAD_SESSION_SIZE) {
            throw new Exception("The attachment size cannot be greater than 3MB", 1);
        }

        $attachment = $file->jsonSerialize();
        $attachment['contentBytes'] = base64_encode($file->getContentBytes());

        $this->message['attachments'][] = $attachment;

        return $this;
    }
}
