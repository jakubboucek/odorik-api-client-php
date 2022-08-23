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

    public static function isContentType($header, ...$expectedContentType): bool
    {
        $headerBody = self::getHttpHeaderBody($header);

        foreach ($expectedContentType as $contentType) {
            if (strcasecmp($headerBody, $contentType) === 0) {
                return true;
            }
        }

        return false;
    }
}
