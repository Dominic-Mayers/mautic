<?php declare(strict_types=1);

namespace Mautic\Transifex;

use Mautic\Transifex\Exception\InvalidConfigurationException;
use Mautic\Transifex\Exception\MissingCredentialsException;

interface ConfigInterface
{
    /**
     * Loads the config values from environment variables.
     */
    public static function fromEnv(): self;

    public function setBaseUri(string $baseUri): void;

    public function getBaseUri(): string;

    public function setApiToken(?string $apiToken): void;

    public function getApiToken(): ?string;

    public function setOrganization(?string $organization): void;

    public function getOrganization(): ?string;

    public function setProject(?string $project): void;

    public function getProject(): ?string;

    /**
     * @throws MissingCredentialsException
     * @throws InvalidConfigurationException
     */
    public function validate(): void;
}
