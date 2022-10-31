<?php

namespace Hollow3464\GraphMailHandler;

use GuzzleHttp\Psr7\Uri;
use League\OAuth2\Client\Token\AccessTokenInterface;
use TheNetworg\OAuth2\Client\Provider\Azure as OauthProvider;
use TheNetworg\OAuth2\Client\Token\AccessToken;
use Symfony\Component\Cache\Adapter\AbstractAdapter as Cache;
use Symfony\Component\Cache\CacheItem;

class Auth
{
    private string $login_domain = "login.microsoftonline.com";
    private string|null $authorization_code = null;

    public function __construct(
        private string $tenant_id,
        private string $client_id,
        private string $redirect_uri,
        private string $cache_key,
        private AuthStrategy $auth_strategy,
        private OauthProvider $provider,
        private Cache $cache
    ) {
    }

    /**
     * This function generates a request code URI for API auth
     * when using authenticate code flow
     */
    public function generateAuthorizationURI(): string
    {
        return Uri::withQueryValues(
            new Uri(sprintf(
                "https://%s/%s/oauth2/v2.0/authorize",
                htmlspecialchars($this->login_domain, FILTER_SANITIZE_URL),
                $this->tenant_id
            )),
            [
                'response_mode' => 'query',
                'response_type' => 'code',
                'client_id' => $this->client_id,
                'redirect_uri' => $this->redirect_uri,
                'scope' => join(' ', [
                    'openid', 'profile', 'email', 'offline_access',
                    'user.read', 'mail.readwrite', 'mail.send'
                ]),
            ]
        )->__toString();
    }

    /** 
     * This function retreives either a cached token or requests
     * MS for a new one
     */
    public function getToken(): string
    {
        if ($cached_token = $this->getCachedToken()) {
            return $cached_token->getToken();
        }

        if (!$this->auth_strategy == AuthStrategy::AUTHORIZATION_CODE) {
            throw new TokenNotFoundException("Error Processing Request", 1);
        }

        $token = match ($this->auth_strategy) {
            AuthStrategy::AUTHORIZATION_CODE => $this->retrieveWithAuthorizationCode(),
            AuthStrategy::CLIENT_CREDENTIALS => $this->retrieveWithClientCredentials()
        };

        $token = $this->provider->getAccessToken('client_credentials', [
            'scope' => $this->provider->getRootMicrosoftGraphUri(null) . '/.default'
        ]);

        $this->cacheToken($token);

        return $token->getToken();
    }

    private function getCachedToken(): AccessToken|null
    {        
        return $this->cache->get($this->cache_key, fn () => null);
    }

    private function cacheToken(AccessToken $token)
    {
        return $this->cache->save(
            (new CacheItem())
                ->expiresAfter($token->getExpires())
                ->set($token)
        );
    }

    private function retrieveWithAuthorizationCode(): AccessTokenInterface
    {
        if (!$this->authorization_code) {
            throw new \Exception("No authorization code set", 1);
        }

        return $this->provider->getAccessToken('authorization_code', [
            'scope' =>  'openid profile email offline_access User.Read Mail.ReadWrite Mail.Send',
            'code' => $this->authorization_code
        ]);
    }

    private function retrieveWithClientCredentials(): AccessTokenInterface
    {
        return $this->provider->getAccessToken('client_credentials', [
            'scope' =>  $this->provider->getRootMicrosoftGraphUri(null) . "/.default"
        ]);
    }
}
