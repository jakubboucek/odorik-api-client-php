<?php

declare(strict_types=1);

namespace JakubBoucek\OdorikApi;

class Credentials
{
    public const DEFAULT_URL = 'https://www.odorik.cz/api/v1';

    private string $user;
    private string $password;
    private string $url;

    public function __construct(string $user, string $password, string $url = self::DEFAULT_URL)
    {
        $this->user = $user;
        $this->password = $password;
        $this->url = $url;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
