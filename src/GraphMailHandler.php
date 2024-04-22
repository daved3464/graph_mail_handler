<?php

declare(strict_types=1);

namespace Hollow3464\GraphMailHandler;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Stream;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Http\GraphResponse;
use Microsoft\Graph\Model\AttachmentItem;
use Microsoft\Graph\Model\AttachmentType;
use Microsoft\Graph\Model\FileAttachment;
use Microsoft\Graph\Model\Importance;
use Microsoft\Graph\Model\Message;
use Microsoft\Graph\Model\UploadSession;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Exception;
use Generator;
use GuzzleHttp\Exception\GuzzleException;

final class GraphMailHandler
{
    public const MIN_UPLOAD_SESSION_SIZE = 3145728;
    public const UPLOAD_CHUNK_SIZE = 4194304;

    public function __construct(
        private string $email,
        private ClientInterface $client,
        private RequestFactoryInterface $requests,
        private Graph $graph,
        private LoggerInterface|null $log = null
    ) {
    }

    /**
     * @param ?array<string> $select_params
     * @param ?array<string> $filter_params
     * @return Generator<int, array<int,Message>>
     */
    public function requestPage(
        EmailParams $params = null,
        ?array $select_params = null,
        ?array $filter_params = null
    ) {
        $filters = $filter_params ?? [];

        if ($params?->withAttachments) {
            $filters[] = "hasAttachments eq true";
        }

        $endpoint =
            join('?', [
                sprintf(
                    ApplicationEndpoints::MAIL_WITH_FOLDER->value,
                    $this->email,
                    'INBOX'
                ),
                $this->buildQuery($select_params ?? [], $filters)
            ]);

        if ($params?->includeAttachments) {
            $endpoint = $endpoint . '&$expand=attachments';
        }

        if ($this->log) {
            $this->log->info("Retrieving mail w/ endpoint $endpoint  \n");
        }

        $req = $this->graph
            ->createCollectionRequest('get', $endpoint)
            ->setReturnType(Message::class);

        while (!$req->isEnd()) {
            yield $req->getPage();
        }
    }

    /**
     * @return array<int, Message>
     */
    public function getEmails(EmailParams $params = null): array
    {
        $data = [];

        foreach ($this->requestPage($params) as $emailPage) {
            $data = array_merge($data, $emailPage);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $message
     * @throws GuzzleException
     * @throws \Exception
     */
    public function createEmail(array $message): Message
    {
        $message = $this->graph
            ->createRequest('post', sprintf(
                ApplicationEndpoints::MAIL_WITH_FOLDER->value,
                $this->email,
                'INBOX'
            ))
            ->attachBody($message)
            ->setReturnType(Message::class)
            ->execute();
        if (!$message instanceof Message) {
            throw new Exception("Failed to create message", 1);
        }

        return $message;
    }

    public function sendEmail(string $id): GraphResponse
    {
        $response =  $this->graph
            ->createRequest('post', sprintf(
                ApplicationEndpoints::MAIL_SEND->value,
                $this->email,
                $id
            ))
            ->execute();

        if (!$response instanceof GraphResponse) {
            throw new Exception("Failed to send message", 1);
        }

        return $response;
    }

    public function markAsRead(string $id): mixed
    {
        return $this->graph->createRequest(
            'patch',
            sprintf(
                ApplicationEndpoints::MAIL->value . '/%s',
                $this->email,
                $id
            )
        )
            ->attachBody(['isRead' => true])
            ->execute();
    }

    public function markAsImportant(string $id): mixed
    {
        return $this->graph->createRequest(
            'patch',
            sprintf(
                ApplicationEndpoints::MAIL->value . '/%s',
                $this->email,
                $id
            )
        )
            ->attachBody(['importance' => Importance::HIGH])
            ->execute();
    }

    public function moveToFolder(string $mail_id, string $folder_id): mixed
    {
        return $this->graph->createRequest(
            'post',
            sprintf(
                ApplicationEndpoints::MAIL->value . '/%s/move',
                $this->email,
                $mail_id
            )
        )
            ->attachBody(['destinationId' => $folder_id])
            ->execute();
    }

    public function uploadAttachment(string $mail_id, FileAttachment $file): mixed
    {
        return $this->graph
            ->createRequest('post', sprintf(
                ApplicationEndpoints::ATTACHMENT->value,
                $this->email,
                $mail_id
            ))
            ->attachBody($file)
            ->execute();
    }

    public function createUploadSession(string $mail_id, string $filename, string|StreamInterface $file): UploadSession
    {
        $size = 0;

        if (is_string($file)) {
            if (!file_exists($file)) {
                throw new Exception("File does not exist", 1);
            }

            if (!is_readable($file)) {
                throw new Exception("File cannot be read", 1);
            }

            $size = filesize($file);
        }

        if (is_object($file)) {
            $size = $file->getSize();
        }

        if ($size <= self::MIN_UPLOAD_SESSION_SIZE) {
            throw new Exception("The file size for an upload session must be greater than 3MB", 1);
        }

        $session = $this->graph
            ->createRequest('post', sprintf(
                ApplicationEndpoints::ATTACHMENT->value . 'createUploadSession',
                $this->email,
                $mail_id
            ))
            ->attachBody([
                'AttachmentItem' => (new AttachmentItem())
                    ->setAttachmentType(new AttachmentType(AttachmentType::FILE))
                    ->setName($filename)
                    ->setSize($size)
            ])
            ->setReturnType(UploadSession::class)
            ->execute();

        if (!$session instanceof UploadSession) {
            throw new Exception("Failed to create upload session", 1);
        }

        return $session;
    }

    /**
     * TODO
     * Check non-working content-range header
     *
     * @throws RequestException
     * @throws ClientException
     */
    public function uploadAttachmentToSession(UploadSession $session, StreamInterface $stream): bool
    {
        $url = (string) $session->getUploadUrl();
        $init_range = 0;

        if ($stream->getSize() <= self::UPLOAD_CHUNK_SIZE) {
            $res =  $this->client->sendRequest(
                $this->requests->createRequest('PUT', $url)
                    ->withHeader('content-type', 'application/octet-stream')
                    ->withHeader('content-length', (string) ($stream->getSize() ?? 0))
                    ->withHeader(
                        'content-range',
                        sprintf('bytes %u-%u/%u', 0, $stream->getSize(), $stream->getSize())
                    )
                    ->withBody($stream)
            );

            if ($res->getStatusCode() !== 201) {
                throw new Exception("The file did not upload correctly to the session", 1);
            }

            return true;
        }

        while (!$stream->eof()) {
            //Create chunk in memory
            $resource = fopen('php://memory', 'r+');
            if (!$resource) {
                throw new Exception("The file could not be opened", 1);
            }

            $chunk = new Stream($resource);
            $chunk->write($stream->read(self::UPLOAD_CHUNK_SIZE));

            $range = sprintf(
                "bytes %s-%s/%s",
                $init_range,
                $init_range + $chunk->getSize() - 1,
                $stream->getSize()
            );

            $res =  $this->client->sendRequest(
                $this->requests
                    ->createRequest('PUT', $url)
                    ->withHeader('Content-Type', 'application/octet-stream')
                    ->withHeader('Content-Length', (string) ($chunk->getSize() ?? 0))
                    ->withHeader('Content-Range', $range)
                    ->withBody($chunk)
            );

            if ($res->getStatusCode() === 201) {
                break;
            }

            /**
             * @var array<string,array<string|int>> $data
             */
            $data = (array) json_decode((string)$res->getBody(), true);

            /**
             * @var int
             */
            $init_range = $data['nextExpectedRanges'][0];
        }

        return true;
    }

    /**
     * @param ?array<string> $select_params
     * @param ?array<string> $filter_params
     */
    private function buildQuery(
        ?array $select_params = null,
        ?array $filter_params = null
    ): string {

        if (!$select_params && !$filter_params) {
            return "";
        }

        if (!$select_params) {
            return $this->buildFilterQuery($filter_params);
        }

        if (!$filter_params) {
            return $this->buildSelectQuery($select_params);
        }

        return join('&', [
            $this->buildSelectQuery($select_params),
            $this->buildFilterQuery($filter_params)
        ]);
    }

    /**
     * @param array<string> $params
     */
    private function buildSelectQuery(array $params = ['id', 'hasAttachments', 'from']): string
    {
        return '$select=' . join(',', $params);
    }

    /**
     * @param array<string> $params
     */
    private function buildFilterQuery(array $params): string
    {
        // TODO Implement Query Builder
        return "\$filter=" . join(' and ', $params);
    }
}
