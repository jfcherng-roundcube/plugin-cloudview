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
        'application/doc',
        'application/ms-doc',
        'application/msword',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.text-template',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.sun.xml.writer',
        // spreadsheet
        'application/excel',
        'application/vnd.ms-excel',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.spreadsheet-template',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.sun.xml.calc',
        'application/x-excel',
        'application/x-msexcel',
        // presentation
        'application/mspowerpoint',
        'application/powerpoint',
        'application/vnd.ms-powerpoint',
        'application/vnd.oasis.opendocument.presentation',
        'application/vnd.oasis.opendocument.presentation-template',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        'application/x-mspowerpoint',
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
