<?php

declare(strict_types=1);

namespace Jfcherng\Roundcube\Plugin\CloudView\Factory;

use Jfcherng\Roundcube\Plugin\CloudView\Exception\ViewerNotFoundException;
use Jfcherng\Roundcube\Plugin\CloudView\Helper\PluginConst;
use Jfcherng\Roundcube\Plugin\CloudView\Viewer\AbstractViewer;

final class ViewerFactory
{
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
        return PluginConst::VIEWER_TABLE[$id] ?? null;
    }

    /**
     * Determine whether the specified viewer exists.
     *
     * @param int $id the viewer ID
     */
    public static function hasViewer(int $id): bool
    {
        return null !== self::getViewerFqcnById($id);
    }
}
