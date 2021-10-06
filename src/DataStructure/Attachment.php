<?php

declare(strict_types=1);

namespace Jfcherng\Roundcube\Plugin\CloudView\DataStructure;

use ArrayAccess;
use JsonSerializable;

final class Attachment implements ArrayAccess, JsonSerializable
{
    use StrictPropertyArrayAccessTrait;

    /**
     * Properties that will be JSON serialized.
     *
     * @var string[]
     */
    public const JSON_SERIALIZE_PROPERTIES = [
        'id',
        'uid',
        'filename',
        'mimeType',
        'size',
        'isSupported',
    ];

    /**
     * The attachment ID (unique if and only if in a message).
     *
     * @var string
     */
    private $id = '';

    /**
     * The global unique message ID.
     *
     * @var string
     */
    private $uid = '';

    /**
     * The attachment MIME type.
     *
     * @var string
     */
    private $mimeType = '';

    /**
     * The attachment filename.
     *
     * @var string
     */
    private $filename = '';

    /**
     * The attachment size in bytes.
     *
     * @var int
     */
    private $size = 0;

    /**
     * The attachment is supported by this plugin or not.
     *
     * @var bool
     */
    private $isSupported = false;

    /**
     * Construct a new instance.
     *
     * Use ::fromArray() to create an instance
     */
    private function __construct()
    {
    }

    /**
     * Return a string representation of the object.
     */
    public function __toString()
    {
        return json_encode($this, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
    }

    /**
     * Set the ID.
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * Get the ID.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set the unique message ID.
     */
    public function setUid(string $uid): void
    {
        $this->uid = $uid;
    }

    /**
     * Get the unique message ID.
     */
    public function getUid(): string
    {
        return $this->uid;
    }

    /**
     * Set the MIME type.
     */
    public function setMimeType(string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    /**
     * Get the MIME type.
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Set the filename.
     */
    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    /**
     * Get the filename.
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Set the file size in bytes.
     */
    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    /**
     * Get the file size in bytes.
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Set whether this file is supported by this plugin.
     */
    public function setIsSupported(bool $isSupported): void
    {
        $this->isSupported = $isSupported;
    }

    /**
     * Get whether this file is supported by this plugin.
     */
    public function getIsSupported(): bool
    {
        return $this->isSupported;
    }

    /**
     * Create instance from an array.
     *
     * @param array $attachment the attachment
     *
     * @return static
     */
    public static function fromArray(array $attachment): self
    {
        $ret = new static();

        foreach ($attachment as $key => $value) {
            $ret[$key] = $value;
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $ret = [];

        foreach (self::JSON_SERIALIZE_PROPERTIES as $prop) {
            $ret[$prop] = $this->{$prop};
        }

        return $ret;
    }
}
