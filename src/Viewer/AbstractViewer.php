<?php

declare(strict_types=1);

namespace Jfcherng\Roundcube\Plugin\CloudView\Viewer;

use Jfcherng\Roundcube\Plugin\CloudView\DataStructure\Attachment;
use rcube_plugin;

abstract class AbstractViewer implements ViewerInterface
{
    /**
     * Supported MIME types.
     *
     * @var string[]
     */
    const SUPPORTED_MIME_TYPES = [];

    /**
     * Does this viewer support viewing CORS files?
     *
     * Pure frontend viewers usually cannot support viewing files across domain.
     *
     * @var bool
     */
    const CAN_SUPPORT_CORS_FILE = true;

    /**
     * @var rcube_plugin
     */
    protected $rcubePlugin;

    /**
     * Set the rcube_plugin instance.
     */
    public function setRcubePlugin(rcube_plugin $rcubePlugin): void
    {
        $this->rcubePlugin = $rcubePlugin;
    }

    /**
     * {@inheritdoc}
     */
    public static function canSupportAttachment(Attachment $attachment): bool
    {
        return \in_array($attachment->getMimeType(), static::SUPPORTED_MIME_TYPES);
    }

    /**
     * Format the string with variable context.
     *
     * This will replace each "{context_key}" with "context_value" in the string.
     *
     * @param string $str     the string
     * @param array  $context the context
     */
    protected function formatString(string $str, array $context): string
    {
        foreach ($context as $key => $value) {
            $str = \str_replace("{{$key}}", $value, $str);
        }

        return $str;
    }

    /**
     * Determine whether the specified string is string fully formatted.
     *
     * @param string $str the string
     */
    protected function isStringFullyFormatted(string $str): bool
    {
        return false === \strpbrk($str, '{}');
    }
}
