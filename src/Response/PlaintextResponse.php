<?php

declare(strict_types=1);

namespace JakubBoucek\OdorikApi\Response;

use Psr\Http\Message\ResponseInterface;

class PlaintextResponse extends ParsedResponse
{
    private string $content;

    public function __construct(string $content, ResponseInterface $response)
    {
        parent::__construct($response);
        $this->content = $content;
    }

    public function isError(): bool
    {
        return preg_match('/^error(?:\s|$)/D', $this->content) === 1;
    }

    public function isOk(): bool
    {
        return $this->isError() === false;
    }

    public function getError(): ?ResponseError
    {
        if ($this->isError()) {
            preg_match('/^error(?:\s+(.+)?|$)/D', $this->content, $matches);
            $message = $matches[1] ?? 'unknown error';
            return new ResponseError($message, 0);
        }

        return null;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
