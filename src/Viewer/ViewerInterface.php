<?php

declare(strict_types=1);

namespace Jfcherng\Roundcube\Plugin\CloudView\Viewer;

use Jfcherng\Roundcube\Plugin\CloudView\DataStructure\Attachment;

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
     * @param Attachment $attachment the attachment
     */
    public static function canSupportAttachment(Attachment $attachment): bool;
}
