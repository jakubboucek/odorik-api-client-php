<?php

declare(strict_types=1);

namespace JakubBoucek\Odorik\Api\Response;

use JakubBoucek\Odorik\Api\Exception\UnexpectedResponseException;
use JakubBoucek\Odorik\Api\Helper;
use Nette\Utils\Json;
use Psr\Http\Message\ResponseInterface;

class Response
{
    protected ResponseInterface $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public static function createFromResponse(ResponseInterface $response): ParsedResponse
    {
        $parsed = new self($response);

        if ($parsed->isJson()) {
            return new JsonResponse(Json::decode((string)$response->getBody(), Json::FORCE_ARRAY), $response);
        }
        if ($parsed->isPlaintext()) {
            return new PlaintextResponse((string)$response->getBody(), $response);
        }

        throw new UnexpectedResponseException(
            "Unrecognized Reponse content type: '{$parsed->getContentType()}'.",
            0,
            $parsed
        );
    }

    public function getContentType(): string
    {
        return $this->response->getHeaderLine('Content-Type');
    }

    public function isJson(): bool
    {
        return Helper::isSameHttpHeaderValue($this->getContentType(), 'application/json');
    }

    public function isPlaintext(): bool
    {
        return Helper::isSameHttpHeaderValue($this->getContentType(), 'text/plain');
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
