<?php

declare(strict_types=1);

namespace Hollow3464\GraphMailHandler;

use GuzzleHttp\Psr7\Stream;
use Microsoft\Graph\Model\Attachment;
use Microsoft\Graph\Model\FileAttachment;
use Exception;

final class FileAttachmentFactory
{
    public static function fromPath(string $filename, string $file): Attachment
    {
        $attachment = new FileAttachment(['@odata.type' => '#microsoft.graph.fileAttachment']);

        $attachment->setName($filename);

        if (!file_exists($file)) {
            throw new Exception("The file does not exist", 1);
        }

        if (!is_readable($file)) {
            throw new Exception("The file is not readable", 1);
        }

        if (filesize($file) > GraphMailHandler::MIN_UPLOAD_SESSION_SIZE) {
            throw new Exception("The file is too big to upload", 1);
        }

        $stream = new Stream(fopen($file, 'r'));

        return $attachment
            ->setContentBytes($stream)
            ->setContentType(mime_content_type($file) ?: 'text/plain')
            ->setSize(filesize($file));
    }

    public static function fromStream(string $filename, Stream $stream): Attachment
    {
        $attachment = new FileAttachment(['@odata.type' => '#microsoft.graph.fileAttachment']);

        $attachment->setName($filename);

        if (!$stream->isReadable()) {
            throw new Exception("The file is not readable", 1);
        }

        if ($stream->getSize() > GraphMailHandler::MIN_UPLOAD_SESSION_SIZE) {
            throw new Exception("The file is too big to upload", 1);
        }

        if (!$stream->getMetadata('mime_type')) {
            throw new Exception("A mime type for the file in the stream must be provided", 1);
        }

        return $attachment
            ->setContentBytes($stream)
            ->setContentType($stream->getMetadata('mime_type'))
            ->setSize($stream->getSize());
    }
}
