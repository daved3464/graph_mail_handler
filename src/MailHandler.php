<?php

namespace Hollow3464\GraphMailHandler;

use GuzzleHttp\Client;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Microsoft\Graph\Exception\GraphException;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\Attachment;
use Microsoft\Graph\Model\Message;

class MailHandler
{
    private string $mail_endpoint;
    private string $attachment_endpoint;

    public function __construct(
        private string $email,
        private AccessTokenInterface $token,
        private Client $client,
        private Graph $graph
    ) {
        $this->mail_endpoint = sprintf(
            "/users/%s/mailFolders('INBOX')/messages?",
            $email
        );

        $this->attachment_endpoint =
            "https://graph.microsoft.com/v1.0/users/%s/messages/%s/attachments";
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
    public function requestPage()
    {
        $endpoint =
            $this->mail_endpoint .
            $this->buildQuery(
                ['id', 'hasAttachments', 'from'],
                ['hasAttachments eq true']
            )
            . '&$expand=attachments'
            ;

        echo "Retrieving mail w/ endpoint $endpoint  \n";

        $mailRetriever = $this
            ->graph
            ->createCollectionRequest('get', $endpoint)
            ->setReturnType(Message::class);

        while (!$mailRetriever->isEnd()) {
            yield $mailRetriever->getPage();
        }
    }

    public function getEmailPages()
    {
        yield $this->requestPage();
    }

    /** 
     * @return array<int, Message>
     */
    public function getEmails(): array
    {
        $data = [];

        foreach ($this->getEmailPages() as $page) {
            foreach ($page as $emails) {
                $data = array_merge($data, $emails);
            }
        }
        
        return $data;
    }

    public function requestAttachments(string $mail_id)
    {
        $attachmentRetriever = $this->graph
            ->createCollectionRequest(
                'get',
                sprintf(
                    $this->attachment_endpoint,
                    $this->email,
                    $mail_id
                )
            )
            ->setReturnType(Attachment::class);

        while (!$attachmentRetriever->isEnd()) {
            yield $attachmentRetriever->getPage();
        }
    }

    public function getAttachments(string $mail_id)
    {
        $data = [];

        foreach ($this->requestAttachments($mail_id) as $at) {
            $data = array_merge($data, $at);
        }

        return $data;
    }
}
