<?php

declare(strict_types=1);

namespace Jfcherng\Roundcube\Plugin\CloudView\Helper;

final class PluginConst
{
    const VIEWER_GOOGLE_DOCS = 1;
    const VIEWER_MICROSOFT_OFFICE_WEB = 2;
    const VIEWER_PDF_JS = 3;
    const VIEWER_MARKDOWN_JS = 4;
    const VIEWER_STACK_EDIT = 5;
    const VIEWER_PSD_JS = 6;
    const VIEWER_HTML_JS = 7;

    const VIEW_BUTTON_IN_ATTACHMENTSLIST = 1;
    const VIEW_BUTTON_IN_ATTACHMENTOPTIONSMENU = 2;

    /**
     * The map of viewer id to viewer FQCN.
     *
     * @var array<int,string> [ view ID (int) => view FQCN ]
     */
    const VIEWER_TABLE = [
        self::VIEWER_GOOGLE_DOCS => \Jfcherng\Roundcube\Plugin\CloudView\Viewer\GoogleDocsViewer::class,
        self::VIEWER_MICROSOFT_OFFICE_WEB => \Jfcherng\Roundcube\Plugin\CloudView\Viewer\MicrosoftOfficeWebViewer::class,
        self::VIEWER_PDF_JS => \Jfcherng\Roundcube\Plugin\CloudView\Viewer\PdfJsViewer::class,
        self::VIEWER_MARKDOWN_JS => \Jfcherng\Roundcube\Plugin\CloudView\Viewer\MarkdownJsViewer::class,
        self::VIEWER_STACK_EDIT => \Jfcherng\Roundcube\Plugin\CloudView\Viewer\StackEditViewer::class,
        self::VIEWER_PSD_JS => \Jfcherng\Roundcube\Plugin\CloudView\Viewer\PsdJsViewer::class,
        self::VIEWER_HTML_JS => \Jfcherng\Roundcube\Plugin\CloudView\Viewer\HtmlJsViewer::class,
    ];
}
