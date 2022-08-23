<?php

declare(strict_types=1);

namespace JakubBoucek\Odorik\Api\Response;

use Psr\Http\Message\ResponseInterface;

class JsonResponse extends ParsedResponse
{
    private array $content;

    public function __construct(array $content, ResponseInterface $response)
    {
        parent::__construct($response);
        $this->content = $content;
    }

    public function isError(): bool
    {
        return isset($this->content['errors'][0]);
    }

    public function isOk(): bool
    {
        return $this->isError() === false;
    }

    public function getError(): ?ResponseError
    {
        if ($this->isError()) {
            return new ResponseError($this->content['errors'][0]);
        }
        return null;
    }

    public function getContent(): array
    {
        return $this->content;
    }


}
