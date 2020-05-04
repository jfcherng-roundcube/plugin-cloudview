<?php

declare(strict_types=1);

namespace Jfcherng\Roundcube\Plugin\CloudView\Factory;

use cloudview;
use Jfcherng\Roundcube\Plugin\CloudView\Exception\ViewerNotFoundException;
use Jfcherng\Roundcube\Plugin\CloudView\Viewer\AbstractViewer;
use Jfcherng\Roundcube\Plugin\CloudView\Viewer\GoogleDocsViewer;
use Jfcherng\Roundcube\Plugin\CloudView\Viewer\MarkdownJsViewer;
use Jfcherng\Roundcube\Plugin\CloudView\Viewer\MicrosoftOfficeWebViewer;
use Jfcherng\Roundcube\Plugin\CloudView\Viewer\PdfJsViewer;

final class ViewerFactory
{
    /**
     * The map of viewer id to viewer FQCN.
     *
     * @var array<int,string>
     */
    const VIEWER_TABLE = [
        cloudview::VIEWER_GOOGLE_DOCS => GoogleDocsViewer::class,
        cloudview::VIEWER_MICROSOFT_OFFICE_WEB => MicrosoftOfficeWebViewer::class,
        cloudview::VIEWER_PDF_JS => PdfJsViewer::class,
        cloudview::VIEWER_MARKDOWN_JS => MarkdownJsViewer::class,
    ];

    /**
     * Viewer singletons.
     *
     * @var array<int,AbstractViewer>
     */
    private static $singletons = [];

    /**
     * Create a new instance of a viewer.
     *
     * @param int $id the viewer identifier
     */
    public static function make(int $id): AbstractViewer
    {
        if (null === ($fqcn = self::getViewerFqcnById($id))) {
            throw new ViewerNotFoundException($id);
        }

        return new $fqcn();
    }

    /**
     * Create the singleton of a viewer.
     *
     * @param int $id the viewer identifier
     */
    public static function getInstance(int $id): AbstractViewer
    {
        return self::$singletons[$id] = self::$singletons[$id] ?? self::make($id);
    }

    /**
     * Get the viewer FQCN by viewer ID.
     *
     * @param int $id the identifier
     *
     * @return null|string the viewer FQCN or null if not found
     */
    public static function getViewerFqcnById(int $id): ?string
    {
        return self::VIEWER_TABLE[$id] ?? null;
    }
}
