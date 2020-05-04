<?php

declare(strict_types=1);

namespace Jfcherng\Roundcube\Plugin\CloudView;

final class MimeHelper
{
    /**
     * Guess mimetype by the given filename.
     *
     * @param string $filename the filename
     */
    public static function guessMimeTypeByFilename(string $filename): ?string
    {
        static $mimeMap;

        $mimeMap = $mimeMap ?? require __DIR__ . '/mime.types.php';
        $ext = \pathinfo($filename, \PATHINFO_EXTENSION);
        $mimes = $mimeMap[$ext] ?? [];

        return $mimes[0] ?? null;
    }
}
