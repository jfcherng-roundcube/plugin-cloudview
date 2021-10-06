<?php

declare(strict_types=1);

namespace Jfcherng\Roundcube\Plugin\CloudView\Viewer;

final class StackEditViewer extends AbstractViewer
{
    /**
     * {@inheritdoc}
     */
    public const SUPPORTED_MIME_TYPES = [
        'text/markdown',
    ];

    /**
     * The base viewer URL.
     *
     * @var string
     */
    public const URL = 'https://stackedit.io/viewer#!url={document_url}';

    /**
     * {@inheritdoc}
     */
    public function getViewableUrl(array $context): ?string
    {
        $url = $this->formatString(self::URL, $context);

        return $this->isStringFullyFormatted($url) ? $url : null;
    }
}
