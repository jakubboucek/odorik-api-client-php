<?php

declare(strict_types=1);

namespace JakubBoucek\Odorik\Api;

class Helper
{
    /**
     * Returns HTTP header value without header parameters
     * @link https://www.rfc-editor.org/rfc/rfc2616.html#section-14
     */
    public static function getHttpHeaderBody(string $header): string
    {
        if ($header === '') {
            return '';
        }

        return rtrim(strtok($header, ';'));
    }

    /**
     * Case-insensitive comparing of header value, which is ignoring appended parameters
     *
     * @param string $headerValue Value of HTTP Header
     * @param string ...$expectedValue List of values; return `true` when at least one match
     */
    public static function isSameHttpHeaderValue(string $headerValue, string ...$expectedValue): bool
    {
        $headerBody = self::getHttpHeaderBody($headerValue);

        foreach ($expectedValue as $contentType) {
            if (strcasecmp($headerBody, $contentType) === 0) {
                return true;
            }
        }

        return false;
    }
}
