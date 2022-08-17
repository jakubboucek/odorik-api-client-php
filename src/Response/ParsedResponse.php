<?php

declare(strict_types=1);

namespace JakubBoucek\OdorikApi\Response;

abstract class ParsedResponse extends Response
{
    abstract public function isError(): bool;

    abstract public function isOk(): bool;

    abstract public function getError(): ?ResponseError;

    abstract public function getContent();

}
