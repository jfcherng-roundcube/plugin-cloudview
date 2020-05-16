<?php

declare(strict_types=1);

namespace Jfcherng\Roundcube\Plugin\CloudView\Helper;

final class MimeHelper
{
    /**
     * Extra MIME types that is not in Apache's list.
     *
     * @var array<string,string[]>
     */
    const EXTRA_MIME_MAP = [
        'md' => ['text/markdown'],
    ];

    /**
     * Get the whole MIME type map.
     */
    public static function getMimeTypeMap(): array
    {
        static $mimeMap;

        if (null === $mimeMap) {
            $mimeMap = require __DIR__ . '/mime.types.php';

            foreach (self::EXTRA_MIME_MAP as $ext => $mimes) {
                $mimeMap[$ext] = \array_merge($mimeMap[$ext] ?? [], $mimes);
            }
        }

        return $mimeMap;
    }

    /**
     * Get MIME type for the given filename.
     *
     * @param string $filename the filename
     */
    public static function getMimeTypeByFilename(string $filename): ?string
    {
        $ext = \pathinfo($filename, \PATHINFO_EXTENSION);
        $mimes = self::getMimeTypeMap()[$ext] ?? [];

        return $mimes[0] ?? null;
    }
}
