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
        'application/doc' => true,
        'application/ms-doc' => true,
        'application/msword' => true,
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => true,
        // spreadsheet
        'application/excel' => true,
        'application/vnd.ms-excel' => true,
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => true,
        'application/x-excel' => true,
        'application/x-msexcel' => true,
        // presentation
        'application/mspowerpoint' => true,
        'application/powerpoint' => true,
        'application/vnd.ms-powerpoint' => true,
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => true,
        'application/vnd.openxmlformats-officedocument.presentationml.slideshow' => true,
        'application/x-mspowerpoint' => true,
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
