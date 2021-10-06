<?php

declare(strict_types=1);

namespace Jfcherng\Roundcube\Plugin\CloudView\Helper;

final class PluginConst
{
    ////////////////
    // viewer IDs //
    ////////////////

    /** @var int indicate no available viewer */
    public const VIEWER_NOT_FOUND = -1;

    public const VIEWER_GOOGLE_DOCS = 1;
    public const VIEWER_MICROSOFT_OFFICE_WEB = 2;
    public const VIEWER_PDF_JS = 3;
    public const VIEWER_MARKDOWN_JS = 4;
    public const VIEWER_STACK_EDIT = 5;
    public const VIEWER_PSD_JS = 6;
    public const VIEWER_HTML_JS = 7;

    ///////////////////////////
    // viewer button layouts //
    ///////////////////////////

    public const VIEW_BUTTON_IN_ATTACHMENTSLIST = 1;
    public const VIEW_BUTTON_IN_ATTACHMENTOPTIONSMENU = 2;

    /**
     * The map of viewer id to viewer FQCN.
     *
     * @var array<int,string> [ view ID (int) => view FQCN ]
     */
    public const VIEWER_TABLE = [
        self::VIEWER_GOOGLE_DOCS => \Jfcherng\Roundcube\Plugin\CloudView\Viewer\GoogleDocsViewer::class,
        self::VIEWER_MICROSOFT_OFFICE_WEB => \Jfcherng\Roundcube\Plugin\CloudView\Viewer\MicrosoftOfficeWebViewer::class,
        self::VIEWER_PDF_JS => \Jfcherng\Roundcube\Plugin\CloudView\Viewer\PdfJsViewer::class,
        self::VIEWER_MARKDOWN_JS => \Jfcherng\Roundcube\Plugin\CloudView\Viewer\MarkdownJsViewer::class,
        self::VIEWER_STACK_EDIT => \Jfcherng\Roundcube\Plugin\CloudView\Viewer\StackEditViewer::class,
        self::VIEWER_PSD_JS => \Jfcherng\Roundcube\Plugin\CloudView\Viewer\PsdJsViewer::class,
        self::VIEWER_HTML_JS => \Jfcherng\Roundcube\Plugin\CloudView\Viewer\HtmlJsViewer::class,
    ];
}
