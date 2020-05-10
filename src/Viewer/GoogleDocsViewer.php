<?php

declare(strict_types=1);

namespace Jfcherng\Roundcube\Plugin\CloudView\Viewer;

final class GoogleDocsViewer extends AbstractViewer
{
    /**
     * {@inheritdoc}
     */
    const SUPPORTED_MIME_TYPES = [
        // text
        'application/doc',
        'application/ms-doc',
        'application/msword',
        'application/rtf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        // spreadsheet
        'application/excel',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/x-excel',
        'application/x-msexcel',
        // presentation
        'application/mspowerpoint',
        'application/powerpoint',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        'application/x-mspowerpoint',
    ];

    /**
     * The base viewer URL.
     *
     * @var string
     */
    const URL = 'https://docs.google.com/viewer?embedded=true&url={document_url}';

    /**
     * {@inheritdoc}
     */
    public function getViewableUrl(array $context): ?string
    {
        $url = $this->formatString(self::URL, $context);

        return $this->isStringFullyFormatted($url) ? $url : null;
    }
}
