<?php declare(strict_types=1);

namespace Mautic\Transifex;

/**
 * Interface defining a Transifex API client.
 *
 * @see https://developers.transifex.com/reference/api-introduction
 */
interface TransifexInterface
{
    /**
     * Factory method to fetch speicific API objects.
     *
     * @template T
     * @param  class-string<T> $connectorFQCN Name of the API object to retrieve
     * @return T
     *
     * @throws Exception\UnknownApiConnectorException
     */
    public function getConnector(string $connectorFQCN);

    /**
     * Api connector is there for common API operations like working with promises (async polling).
     */
    public function getApiConnector(): ApiConnector;
}
