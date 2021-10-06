<?php

declare(strict_types=1);

namespace Jfcherng\Roundcube\Plugin\CloudView\Viewer;

use Jfcherng\Roundcube\Plugin\CloudView\Helper\RoundcubeHelper;
use rcube_plugin;

final class HtmlJsViewer extends AbstractViewer
{
    /**
     * {@inheritdoc}
     */
    public const SUPPORTED_MIME_TYPES = [
        'text/html',
    ];

    /**
     * {@inheritdoc}
     *
     * This viewer is pure frontend but current it directly use URL redirection so it supports CORS files.
     */
    public const CAN_SUPPORT_CORS_FILE = false;

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
            . $rcubePlugin->url('assets/vendor/html-js/index.html')
            . '?url={document_url}';
    }
}
