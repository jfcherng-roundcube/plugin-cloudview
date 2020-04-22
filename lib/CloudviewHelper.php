<?php

class CloudviewHelper
{
    /**
     * Get the site url.
     */
    public static function getSiteUrl(): string
    {
        static $url;

        if (isset($url)) {
            return $url;
        }

        $scheme = \filter_var($_SERVER['HTTPS'] ?? 'off', \FILTER_VALIDATE_BOOLEAN) ? 'https' : 'http';
        $requestedUrl = "{$scheme}://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
        $parts = \parse_url($requestedUrl);

        // remove potential trailing index.php
        $parts['path'] = \preg_replace('/(\/)index.php$/iuS', '$1', $parts['path']);
        unset($parts['query'], $parts['fragment']);

        return $url = self::unparseUrl($parts);
    }

    /**
     * Assemble URL parts back to string URL.
     *
     * @param array $parts the parts
     */
    public static function unparseUrl(array $parts): string
    {
        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ":{$parts['port']}" : '';
        $user = $parts['user'] ?? '';
        $pass = isset($parts['pass']) ? ":{$parts['pass']}" : '';
        $pass = ($user || $pass) ? "{$pass}@" : '';
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) ? "?{$parts['query']}" : '';
        $fragment = isset($parts['fragment']) ? "#{$parts['fragment']}" : '';

        return "{$scheme}{$user}{$pass}{$host}{$port}{$path}{$query}{$fragment}";
    }
}
