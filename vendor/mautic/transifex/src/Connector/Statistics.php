<?php declare(strict_types=1);

namespace Mautic\Transifex\Connector;

use Mautic\Transifex\ApiConnector;
use Psr\Http\Message\ResponseInterface;

/**
 * @see https://developers.transifex.com/reference/get_resource-language-stats
 */
final class Statistics
{
    private ApiConnector $apiConnector;

    public function __construct(ApiConnector $apiConnector)
    {
        $this->apiConnector = $apiConnector;
    }

    public function getLanguageStats(string $resource, string $lang = ''): ResponseInterface
    {
        $query = [
            'filter[resource]' => $this->apiConnector->buildResourceString($resource),
            'filter[project]'  => $this->apiConnector->buildProjectString()
        ];

        if ($lang) {
            $query['filter[language]'] = "l:{$lang}";
        }

        $uri = $this->apiConnector->createUri('resource_language_stats')->withQuery(\http_build_query($query));

        return $this->apiConnector->sendRequest($this->apiConnector->createRequest('GET', $uri));
    }
}
