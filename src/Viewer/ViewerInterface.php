<?php

declare(strict_types=1);

namespace Jfcherng\Roundcube\Plugin\CloudView\Viewer;

interface ViewerInterface
{
    /**
     * Get the viewable URL.
     *
     * @param array $context the context
     *
     * @return null|string the viewable URL or null if failed
     */
    public function getViewableUrl(array $context): ?string;

    /**
     * Determine whether the attachment is supported.
     *
     * @param array $attachment the attachment information
     */
    public static function isSupportedAttachment(array $attachment): bool;
}
