<?php

declare(strict_types=1);

namespace JakubBoucek\OdorikApi;

use JakubBoucek\OdorikApi\Exception\InvalidCredentialsException;
use JakubBoucek\OdorikApi\Exception\ResponseErrorException;
use JakubBoucek\OdorikApi\Exception\UnauthenticatedException;
use JakubBoucek\OdorikApi\Response\ParsedResponse;
use JakubBoucek\OdorikApi\Response\Response;
use JakubBoucek\OdorikApi\Response\ResponseError;

class Client
{
    public const VERSION = 'v0.1-dev';
    public const CLIENT_NAME = 'Odorik.cz API client for PHP';
    public const CLIENT_URL = 'https://github.com/jakubboucek/odorik-api-php';
    public const USER_AGENT = self::CLIENT_NAME . ' ' . self::VERSION . ' (' . self::CLIENT_URL . ')';

    private Credentials $credentials;
    private Http $http;
    private string $userAgent;

    public function __construct(
        Credentials $credentials,
        Http $http,
        ?string $userAgent = null
    ) {
        $this->credentials = $credentials;
        $this->http = $http;
        $this->userAgent = $userAgent ?? self::USER_AGENT;
    }

    public static function create(string $user, string $password, ?string $userAgent = null): Client
    {
        return new self(
            new Credentials($user, $password),
            Http::discover(),
            $userAgent
        );
    }

    public function get(string $endpoint, array $query = []): ParsedResponse
    {
        return $this->request('GET', $endpoint, $query);
    }

    public function request(
        string $method,
        string $endpoint,
        array $query = [],
        ?string $body = '',
        ?array $headers = []
    ): ParsedResponse {
        $query += ['user' => $this->credentials->getUser(), 'password' => $this->credentials->getPassword()];

        $uri = $this->http->getUriFactory()
            ->createUri($this->credentials->getUrl() . $endpoint)
            ->withQuery(http_build_query($query));

        $headers += ['User-Agent'=> $this->userAgent];

        $request = $this->http->getRequestFactory()
            ->createRequest($method, $uri);

        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        if ($body !== null) {
            $request = $request->withBody($this->http->getStreamFactory()->createStream($body));
        }

        $client = $this->http->getClient();

        $response = Response::createFromResponse($client->sendRequest($request));

        if (($error = $response->getError()) instanceof ResponseError) {
            $message = $error->getMessage();

            if ($message === 'authentication_required') {
                throw new UnauthenticatedException($error->getMessage(), $error->getCode(), $response);
            }

            if ($message === 'authentication_failed') {
                throw new InvalidCredentialsException($error->getMessage(), $error->getCode(), $response);
            }

            throw new ResponseErrorException($error->getMessage(), $error->getCode(), $response);
        }

        return $response;
    }
}
