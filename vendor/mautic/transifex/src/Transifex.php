<?php declare(strict_types=1);

namespace Mautic\Transifex;

use Mautic\Transifex\Exception\InvalidConfigurationException;
use Mautic\Transifex\Exception\UnknownApiConnectorException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * Base class for interacting with the Transifex API.
 */
final class Transifex implements TransifexInterface
{
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;
    private UriFactoryInterface $uriFactory;
    private ConfigInterface $config;
    private ApiConnector $apiConnector;

    /**
     * @throws InvalidConfigurationException
     */
    public function __construct(
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        UriFactoryInterface $uriFactory,
        ConfigInterface $config
    ) {
        $config->validate();

        $this->client         = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory  = $streamFactory;
        $this->uriFactory     = $uriFactory;
        $this->config         = $config;
        $this->apiConnector   = new ApiConnector($this->client, $this->requestFactory, $this->streamFactory, $this->uriFactory, $this->config);
    }

    public function getConnector(string $connectorFQCN)
    {
        if (!\class_exists($connectorFQCN)) {
            throw new UnknownApiConnectorException("Class for connector {$connectorFQCN} not found");
        }

        return new $connectorFQCN($this->apiConnector);
    }

    public function getApiConnector(): ApiConnector
    {
        return $this->apiConnector;
    }
}
