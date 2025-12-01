<?php
// Application configuration
$baseUrl = '/blog';

function site_url(string $path = ''): string
{
    global $baseUrl;
    
    $normalizedBase = rtrim($baseUrl, '/');
    $trimmedPath = ltrim($path, '/');

    if ($trimmedPath === '') {
        return $normalizedBase . '/public';
    }

    if (str_starts_with($trimmedPath, 'admin/')) {
        return $normalizedBase . '/' . $trimmedPath;
    }

    return $normalizedBase . '/public/' . $trimmedPath;
}

function asset_url(string $path): string
{
    return site_url('assets/' . ltrim($path, '/'));
}
?>