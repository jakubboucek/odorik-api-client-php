<?php

declare(strict_types=1);

namespace JakubBoucek\OdorikApi\Exception;

use JakubBoucek\OdorikApi\Response\Response;
use RuntimeException;
use Throwable;

class ResponseException extends RuntimeException
{
    protected Response $response;

    public function __construct($message, $code, Response $response, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}
