<?php

class MimeHelper
{
    public $mimeType;

    /**
     * supported MIME types.
     *
     * @param null|string $mimeType the MIME type
     */
    public static function isSupportedMimeType(?string $mimeType): bool
    {
        return
            self::isMimeTypeText($mimeType) ||
            self::isMimeTypeSpreadsheet($mimeType) ||
            self::isMimeTypePresentation($mimeType) ||
            self::isMimeTypePdf($mimeType);
    }

    /**
     * text.
     *
     * @param null|string $mimeType the MIME type
     */
    public static function isMimeTypeText(?string $mimeType): bool
    {
        $textMimeTypes = [
            'application/msword' => true, // doc
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => true, // docx
            'application/vnd.sun.xml.writer' => true, // sxw
            'application/vnd.oasis.opendocument.text' => true, // odt
        ];

        return isset($textMimeTypes[$mimeType]);
    }

    /**
     * spreadsheet.
     *
     * @param null|string $mimeType the MIME type
     */
    public static function isMimeTypeSpreadsheet(?string $mimeType): bool
    {
        $spreadsheetMimeTypes = [
            'text/csv' => true, // csv
            'application/vnd.ms-excel' => true, // xls
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => true, // xlsx
            'application/vnd.sun.xml.calc' => true, // sxc
            'application/vnd.oasis.opendocument.spreadsheet' => true, // ods
        ];

        return isset($spreadsheetMimeTypes[$mimeType]);
    }

    /**
     * presentation.
     *
     * @param null|string $mimeType the MIME type
     */
    public static function isMimeTypePresentation(?string $mimeType): bool
    {
        $presentationMimeTypes = [
            'application/vnd.ms-powerpoint' => true, // ppt
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => true, // pptx
            'application/vnd.ms-powerpoint' => true, // pps
            'application/vnd.openxmlformats-officedocument.presentationml.slideshow' => true, // ppsx
            'application/vnd.oasis.opendocument.presentation' => true, // odp
        ];

        return isset($presentationMimeTypes[$mimeType]);
    }

    /**
     * pdf.
     *
     * @param null|string $mimeType the MIME type
     */
    public static function isMimeTypePdf(?string $mimeType): bool
    {
        $pdfMimeTypes = [
            'application/pdf' => true, // pdf
            'application/x-pdf' => true, // pdf
            'application/acrobat' => true, // pdf
            'applications/vnd.pdf' => true, // pdf
            'text/x-pdf' => true, // pdf
            'text/pdf' => true, // pdf
        ];

        return isset($pdfMimeTypes[$mimeType]);
    }
}
