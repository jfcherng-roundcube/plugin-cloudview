<?php
/**
 * @version $Id$
 * MIME types supported by cloudview
 *
 * @author Rene Kanzler <rk (at) cosmomill (dot) de>
 */
class mimeHelper
{
    public $sMimeType;

    /**
     * supported MIME types.
     *
     * @param null|string $sMimeType the MIME type
     */
    public static function isSupportedMimeType(?string $sMimeType): bool
    {
        return
            self::isMimeTypeText($sMimeType) ||
            self::isMimeTypeSpreadsheet($sMimeType) ||
            self::isMimeTypePresentation($sMimeType) ||
            self::isMimeTypePdf($sMimeType);
    }

    /**
     * text.
     *
     * @param null|string $sMimeType the MIME type
     */
    public static function isMimeTypeText(?string $sMimeType): bool
    {
        $aTextMimeTypes = [
            'application/msword' => true, // doc
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => true, // docx
            'application/vnd.sun.xml.writer' => true, // sxw
            'application/vnd.oasis.opendocument.text' => true, // odt
        ];

        return isset($aTextMimeTypes[$sMimeType]);
    }

    /**
     * spreadsheet.
     *
     * @param null|string $sMimeType the MIME type
     */
    public static function isMimeTypeSpreadsheet(?string $sMimeType): bool
    {
        $aSpreadsheetMimeTypes = [
            'text/csv' => true, // csv
            'application/vnd.ms-excel' => true, // xls
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => true, // xlsx
            'application/vnd.sun.xml.calc' => true, // sxc
            'application/vnd.oasis.opendocument.spreadsheet' => true, // ods
        ];

        return isset($aSpreadsheetMimeTypes[$sMimeType]);
    }

    /**
     * presentation.
     *
     * @param null|string $sMimeType the MIME type
     */
    public static function isMimeTypePresentation(?string $sMimeType): bool
    {
        $aPresentationMimeTypes = [
            'application/vnd.ms-powerpoint' => true, // ppt
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => true, // pptx
            'application/vnd.ms-powerpoint' => true, // pps
            'application/vnd.openxmlformats-officedocument.presentationml.slideshow' => true, // ppsx
            'application/vnd.oasis.opendocument.presentation' => true, // odp
        ];

        return isset($aPresentationMimeTypes[$sMimeType]);
    }

    /**
     * pdf.
     *
     * @param null|string $sMimeType the MIME type
     */
    public static function isMimeTypePdf(?string $sMimeType): bool
    {
        $aPdfMimeTypes = [
            'application/pdf' => true, // pdf
            'application/x-pdf' => true, // pdf
            'application/acrobat' => true, // pdf
            'applications/vnd.pdf' => true, // pdf
            'text/x-pdf' => true, // pdf
            'text/pdf' => true, // pdf
        ];

        return isset($aPdfMimeTypes[$sMimeType]);
    }
}
