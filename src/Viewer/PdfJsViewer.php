<?php

declare(strict_types=1);

namespace Jfcherng\Roundcube\Plugin\CloudView\Viewer;

use Jfcherng\Roundcube\Plugin\CloudView\RoundcubeHelper;
use rcube_plugin;

final class PdfJsViewer extends AbstractViewer
{
    /**
     * {@inheritdoc}
     */
    const SUPPORTED_MIME_TYPES = [
        'application/acrobat' => true,
        'application/pdf' => true,
        'application/x-pdf' => true,
        'applications/vnd.pdf' => true,
        'text/pdf' => true,
        'text/x-pdf' => true,
    ];

    /**
     * {@inheritdoc}
     */
    const IS_SUPPORT_CORS_FILE = false;

    /**
     * {@inheritdoc}
     */
    public function getViewableUrl(array $context): ?string
    {
        $url = $this->formatString(self::getViewerUrl($this->rcubePlugin), $context);

        return $this->isStringFullyFormatted($url) ? $url : null;
    }

    /**
     * Get the viewer URL.
     *
     * @param rcube_plugin $rcubePlugin the rcube plugin
     */
    public static function getViewerUrl(rcube_plugin $rcubePlugin): string
    {
        return RoundcubeHelper::getSiteUrl()
            . $rcubePlugin->url('assets/pdfjs-dist/web/viewer.html')
            . '?file={document_url}';
    }
}
