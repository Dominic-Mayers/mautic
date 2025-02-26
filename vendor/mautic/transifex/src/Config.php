<?php declare(strict_types=1);

namespace Mautic\Transifex;

use Mautic\Transifex\Exception\InvalidConfigurationException;
use Mautic\Transifex\Exception\MissingCredentialsException;

final class Config implements ConfigInterface
{
    private string $baseUri = 'https://rest.api.transifex.com';
    private ?string $apiToken;
    private ?string $organization;
    private ?string $project;

    public static function fromEnv(): self
    {
        $config = new self();

        $config->setApiToken(self::getEnv('TRANSIFEX_API_TOKEN'));
        $config->setOrganization(self::getEnv('TRANSIFEX_ORGANIZATION'));
        $config->setProject(self::getEnv('TRANSIFEX_PROJECT'));

        return $config;
    }

    public function setBaseUri(string $baseUri): void
    {
        $this->baseUri = $baseUri;
    }

    public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    public function setApiToken(?string $apiToken): void
    {
        $this->apiToken = $apiToken;
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setOrganization(?string $organization): void
    {
        $this->organization = $organization;
    }

    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    public function setProject(?string $project): void
    {
        $this->project = $project;
    }

    public function getProject(): ?string
    {
        return $this->project;
    }

    public function validate(): void
    {
        if (empty($this->apiToken)) {
            throw new MissingCredentialsException('The API token is required.');
        }

        if (empty($this->baseUri)) {
            throw new InvalidConfigurationException('Base URI is required.');
        }

        if (empty($this->organization)) {
            throw new InvalidConfigurationException('The organization is required.');
        }

        if (empty($this->project)) {
            throw new InvalidConfigurationException('The project is required.');
        }
    }

    private static function getEnv(string $name): ?string
    {
        $value = \getenv($name);

        return (false === $value) ? null : $value;
    }
}
