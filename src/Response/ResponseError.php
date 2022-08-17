<?php

declare(strict_types=1);

namespace JakubBoucek\OdorikApi\Response;

class ResponseError
{
    private string $message;
    private int $code;

    public function __construct(string $message, int $code = 0)
    {
        $this->message = $message;
        $this->code = $code;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getCode(): int
    {
        return $this->code;
    }
}
