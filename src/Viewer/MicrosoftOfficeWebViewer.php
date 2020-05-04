<?php

declare(strict_types=1);

namespace Jfcherng\Roundcube\Plugin\CloudView\Viewer;

final class MicrosoftOfficeWebViewer extends AbstractViewer
{
    /**
     * {@inheritdoc}
     */
    const SUPPORTED_MIME_TYPES = [
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
    ];

    /**
     * The base viewer URL.
     *
     * @var string
     */
    const URL = 'https://view.officeapps.live.com/op/view.aspx?src={document_url}';

    /**
     * {@inheritdoc}
     */
    public function getViewableUrl(array $context): ?string
    {
        $url = $this->formatString(self::URL, $context);

        return $this->isStringFullyFormatted($url) ? $url : null;
    }
}
