<?php

namespace Hollow3464\GraphMailHandler;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Stream;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Http\GraphResponse;
use Microsoft\Graph\Model\FileAttachment;
use Microsoft\Graph\Model\Message;
use Psr\Log\LoggerInterface;

class MailHandler
{
    public function __construct(
        private string $email,
        private AccessTokenInterface $token,
        private Client $client,
        private Graph $graph,
        private LoggerInterface|null $log = null
    ) {
    }

    private function buildQuery(
        array|null $select_params = null,
        array|null $filter_params = null
    ): string {

        if (!$select_params && !$filter_params) {
            return "";
        }

        if (!$select_params && $filter_params) {
            return $this->buildFilterQuery($filter_params);
        }

        if (!$filter_params && $select_params) {
            return $this->buildSelectQuery($select_params);
        }

        return join('&', [
            $this->buildSelectQuery($select_params),
            $this->buildFilterQuery($filter_params)
        ]);
    }

    private function buildSelectQuery(array|null $params = null): string
    {
        if (!$params) {
            return "";
        }

        if (count($params)) {
            $params = ['id', 'hasAttachments', 'from'];
        }
        return '$select=' . join(',', $params);
    }

    private function buildFilterQuery(array $params): string
    {
        // TODO Implement Query Builder
        return "\$filter=" . join(' and ', $params);
    }

    /** 
     * @return \Generator<int, array<int,Message>>
     */
    public function requestPage(EmailParams $params = null)
    {
        $filters = [];

        if ($params?->withAttachments) {
            $filters[] = "hasAttachments eq true";
        }

        $endpoint =
            join('?', [
                OutlookApplicationEndpoints::MAIL_WITH_FOLDER
                    ->fillEmailFolder($this->email, 'INBOX'),
                $this->buildQuery(
                    ['id', 'hasAttachments', 'from'],
                    $filters
                )
            ]);

        if ($params?->includeAttachments) {
            $endpoint = $endpoint . '&$expand=attachments';
        }

        if ($this->log) {
            $this->log->info("Retrieving mail w/ endpoint $endpoint  \n");
        }

        echo "Retrieving mail w/ endpoint $endpoint  \n";

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

    public function createEmail(array $message): Message
    {
        return $this->graph
            ->createRequest(
                'post',
                OutlookApplicationEndpoints::MAIL_WITH_FOLDER
                    ->fillEmailFolder($this->email, 'INBOX')
            )
            ->attachBody($message)
            ->setReturnType(Message::class)
            ->execute();
    }

    public function sendEmail(string $id): GraphResponse
    {
        return $this->graph
            ->createRequest(
                'post',
                OutlookApplicationEndpoints::MAIL_SEND->fillEmailSingle($this->email, $id)
            )
            ->execute();
    }

    public function createEmailWithAttachment()
    {
    }

    public function createEmailWithBigAttachment()
    {
    }
}
