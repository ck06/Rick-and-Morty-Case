<?php declare(strict_types=1);

namespace App\Service;

class ApiUtilityService
{
    public static function getIdFromApiUrl(string $url): int
    {
        return (int)substr($url, 1 + (int)strrpos($url, '/'));
    }
}
