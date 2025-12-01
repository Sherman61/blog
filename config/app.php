<?php
// Application configuration
$baseUrl = '/blog/';

function site_url(string $path = ''): string
{
    global $baseUrl;
    $normalizedBase = rtrim($baseUrl, '/');
    $trimmedPath = ltrim($path, '/');

    return $trimmedPath === ''
        ? $normalizedBase
        : $normalizedBase . '/' . $trimmedPath;
}

function asset_url(string $path): string
{
    return site_url('public/assets/' . ltrim($path, '/'));
}
?>