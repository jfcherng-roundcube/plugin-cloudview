<?php

declare(strict_types=1);

namespace Jfcherng\Roundcube\Plugin\CloudView;

final class MimeHelper
{
    /**
     * supported MIME types.
     *
     * @param null|string $mimeType the MIME type
     */
    public static function isSupportedMimeType(?string $mimeType): bool
    {
        static $mimeTypes = [
            // text
            'application/doc' => true,
            'application/ms-doc' => true,
            'application/msword' => true,
            'application/vnd.oasis.opendocument.text' => true,
            'application/vnd.oasis.opendocument.text-template' => true,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => true,
            'application/vnd.sun.xml.writer' => true,
            // spreadsheet
            'application/excel' => true,
            'application/vnd.ms-excel' => true,
            'application/vnd.oasis.opendocument.spreadsheet' => true,
            'application/vnd.oasis.opendocument.spreadsheet-template' => true,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => true,
            'application/vnd.sun.xml.calc' => true,
            'application/x-excel' => true,
            'application/x-msexcel' => true,
            // presentation
            'application/mspowerpoint' => true,
            'application/powerpoint' => true,
            'application/vnd.ms-powerpoint' => true,
            'application/vnd.oasis.opendocument.presentation' => true,
            'application/vnd.oasis.opendocument.presentation-template' => true,
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => true,
            'application/vnd.openxmlformats-officedocument.presentationml.slideshow' => true,
            'application/x-mspowerpoint' => true,
            // pdf
            'application/acrobat' => true,
            'application/pdf' => true,
            'application/x-pdf' => true,
            'applications/vnd.pdf' => true,
            'text/pdf' => true,
            'text/x-pdf' => true,
        ];

        return isset($mimeTypes[$mimeType]);
    }

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
