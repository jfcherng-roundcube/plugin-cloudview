<?php

declare(strict_types=1);

namespace Jfcherng\Roundcube\Plugin\CloudView\Exception;

use RuntimeException;
use Throwable;

final class ViewerNotFoundException extends RuntimeException
{
    public function __construct(int $viewerId, int $code = 0, Throwable $previous = null)
    {
        parent::__construct("No viewer is associated with ID '{$viewerId}'", $code, $previous);
    }
}
