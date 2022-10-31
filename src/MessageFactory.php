<?php

namespace Hollow3464\GraphMailHandler;

class MessageFactory
{
    private array|null $message = null;
    private array $attachments = [];

    public function __construct()
    {
        $this->message =  [
            'subject' => '',
            'importance' => 'low',
            'body' => [
                'contentType' => '',
                'content' => '',
            ],
            'toRecipients' => []
        ];
    }

    public function build()
    {
        if (!$this->message['body']['contentType']) {
            throw new \Exception("Content type must be set", 1);
        }

        if (!$this->message['body']['content']) {
            throw new \Exception("Content must be set", 1);
        }

        if (!count($this->message['toRecipients'])) {
            throw new \Exception("Cant create message without recipients", 1);
        }

        return $this->message;
    }

    public function setImportance(string $importance)
    {
        $this->message['importance'] = $importance;

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
        $this->message['body']['content'] = htmlentities($body);
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

    public function addAttachment(string $file)
    {
        $this->attachments[] = $this->createAttachment($file);

        return $this;
    }

    private function createAttachment(string $file)
    {
        return [
            'id' => '',
            'name' => '',
            'size' => 0,
            'contentBytes' => base64_encode($file),
            'isInline' => false,
        ];
    }
}
