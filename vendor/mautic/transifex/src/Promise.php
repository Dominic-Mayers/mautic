<?php declare(strict_types=1);

namespace Mautic\Transifex;

/**
 * Holds an ID of an asynchronous task that needs to be polled for the result.
 */
final class Promise
{
    private string $id;
    private string $checkLink;
    private ?string $filePath = null;

    public function __construct(string $id, string $checkLink)
    {
        $this->id        = $id;
        $this->checkLink = $checkLink;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCheckLink(): string
    {
        return $this->checkLink;
    }

    public function setFilePath(?string $filePath): void
    {
        $this->filePath = $filePath;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }
}
