<?php

declare(strict_types=1);

namespace Jfcherng\Roundcube\Plugin\CloudView\DataStructure;

use Exception;

trait StrictPropertyArrayAccessTrait
{
    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->{$offset};
        }

        throw new Exception("No such property '{$offset}' in " . static::class);
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if ($this->offsetExists($offset)) {
            $this->{$offset} = $value;
        } else {
            throw new Exception("No such property '{$offset}' in " . static::class);
        }
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new Exception('Unsupported operation');
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return property_exists($this, $offset);
    }
}
