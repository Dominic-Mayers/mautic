<?php declare(strict_types=1);

namespace Mautic\Transifex\Connector;

use Mautic\Transifex\ApiConnector;
use Psr\Http\Message\ResponseInterface;

/**
 * @link https://developers.transifex.com/reference/post_resources
 */
final class Resources
{
    private ApiConnector $apiConnector;

    public function __construct(ApiConnector $apiConnector)
    {
        $this->apiConnector = $apiConnector;
    }

    public function create(
        string $name,
        string $slug,
        string $fileType
    ): ResponseInterface {
        $endpoint = 'resources';
        $uri      = $this->apiConnector->createUri($endpoint);
        $body     = <<<EOT
{
    "data": {
        "attributes": {
            "name": "{$name}",
            "slug": "{$slug}"
        },
        "relationships": {
            "i18n_format": {
                "data": {
                    "type": "i18n_formats",
                    "id": "{$fileType}"
                }
            },
            "project": {
                "data": {
                    "type": "projects",
                    "id": "{$this->apiConnector->buildProjectString()}"
                }
            }
        },
        "type": "{$endpoint}"
    }
}
EOT;
        $request = $this->apiConnector->createRequest('POST', $uri);
        $request = $request->withBody($this->apiConnector->createStream($body));

        return $this->apiConnector->sendRequest($request);
    }

    /**
     * @see https://developers.transifex.com/reference/delete_resources-resource-id
     */
    public function delete(string $resource): ResponseInterface
    {
        $endpoint = "resources/{$this->apiConnector->buildResourceString($resource)}";
        $uri      = $this->apiConnector->createUri($endpoint);
        $request  = $this->apiConnector->createRequest('DELETE', $uri);

        return $this->apiConnector->sendRequest($request);
    }

    /**
     * @see https://developers.transifex.com/reference/get_resources
     */
    public function getAll(): ResponseInterface
    {
        $query = \http_build_query(['filter[project]' => $this->apiConnector->buildProjectString()]);

        return $this->apiConnector->sendRequest($this->apiConnector->createRequest('GET', $this->apiConnector->createUri('resources')->withQuery($query)));
    }

    /**
     * @see https://developers.transifex.com/reference/get_resource-strings
     */
    public function getContent(string $resource): ResponseInterface
    {
        $query = \http_build_query(['filter[resource]' => $this->apiConnector->buildResourceString($resource)]);

        return $this->apiConnector->sendRequest($this->apiConnector->createRequest('GET', $this->apiConnector->createUri('resource_strings')->withQuery($query)));
    }

    /**
     * @see https://developers.transifex.com/reference/post_resource-strings-async-uploads
     */
    public function uploadContent(string $resource, string $content, bool $replaceEditedStrings = false): ResponseInterface
    {
        $endpoint             = 'resource_strings_async_uploads';
        $uri                  = $this->apiConnector->createUri($endpoint);
        $content              = \base64_encode($content);
        $replaceEditedStrings = $replaceEditedStrings ? 'true' : 'false';
        $body                 = <<<EOT
{
    "data": {
        "attributes": {
            "replace_edited_strings": {$replaceEditedStrings},
            "content": "{$content}",
            "content_encoding": "base64"
        },
        "relationships": {
            "resource": {
                "data": {
                    "type": "resources",
                    "id": "{$this->apiConnector->buildResourceString($resource)}"
                }
            }
        },
        "type": "{$endpoint}"
    }
}
EOT;
        $request = $this->apiConnector->createRequest('POST', $uri);
        $request = $request->withBody($this->apiConnector->createStream($body));

        return $this->apiConnector->sendRequest($request);
    }

    /**
     * Helper function that goes through the resources data array (use the getAll() method to get it) and checks if the resource exists.
     */
    public function resourceExists(array $existingResources, string $alias): bool
    {
        foreach ($existingResources as $resource) {
            if ($resource['attributes']['slug'] === $alias) {
                return true;
            }
        }

        return false;
    }
}
