<?php

declare(strict_types=1);

namespace Jfcherng\Roundcube\Plugin\CloudView;

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
     * Guess mimetype by the given filename.
     *
     * @param string $filename the filename
     */
    public static function guessMimeTypeByFilename(string $filename): ?string
    {
        static $mimeMap;

        $mimeMap = $mimeMap ?? \array_merge(
            (require __DIR__ . '/mime.types.php'),
            self::EXTRA_MIME_MAP
        );

        $ext = \pathinfo($filename, \PATHINFO_EXTENSION);
        $mimes = $mimeMap[$ext] ?? [];

        return $mimes[0] ?? null;
    }
}
