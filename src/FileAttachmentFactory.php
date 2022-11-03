<?php

namespace Hollow3464\GraphMailHandler;

use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Utils;
use Microsoft\Graph\Model\Attachment;
use Microsoft\Graph\Model\FileAttachment;
use Psr\Http\Message\StreamInterface;

class FileAttachmentFactory
{
    public static function fromPath(string $filename, string $file): Attachment
    {
        $attachment = new FileAttachment(['@odata.type' => '#microsoft.graph.fileAttachment']);

        $attachment->setName($filename);

        if (!file_exists($file)) {
            throw new \Exception("The file does not exist", 1);
        }

        if (!is_readable($file)) {
            throw new \Exception("The file is not readable", 1);
        }

        if (filesize($file) > GraphMailHandler::MIN_UPLOAD_SESSION_SIZE) {
            throw new \Exception("The file is too big to upload", 1);
        }

        return $attachment
            ->setContentBytes(new Stream(Utils::streamFor(
                base64_encode(file_get_contents($file))
            )->detach()))
            ->setContentType(mime_content_type($file) ?: 'text/plain')
            ->setSize(filesize($file));
    }

    public static function fromStream(string $filename, StreamInterface $stream): Attachment
    {
        $attachment = new FileAttachment(['@odata.type' => '#microsoft.graph.fileAttachment']);

        $attachment->setName($filename);

        if (!$stream->isReadable()) {
            throw new \Exception("The file is not readable", 1);
        }

        if ($stream->getSize() > GraphMailHandler::MIN_UPLOAD_SESSION_SIZE) {
            throw new \Exception("The file is too big to upload", 1);
        }

        if (!$stream->getMetadata('mime_type')) {
            throw new \Exception("A mime type for the file in the stream must be provided", 1);
        }


        return $attachment
            ->setContentBytes(new Stream(Utils::streamFor(
                base64_encode($stream)
            )->detach()))
            ->setContentType($stream->getMetadata('mime_type'))
            ->setSize($stream->getSize());
    }
}
