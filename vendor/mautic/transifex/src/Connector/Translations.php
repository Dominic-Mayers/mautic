<?php declare(strict_types=1);

namespace Mautic\Transifex\Connector;

use Mautic\Transifex\ApiConnector;
use Psr\Http\Message\ResponseInterface;

final class Translations
{
    private ApiConnector $apiConnector;

    public function __construct(ApiConnector $apiConnector)
    {
        $this->apiConnector = $apiConnector;
    }

    /**
     * @see https://developers.transifex.com/reference/post_resource-translations-async-uploads
     */
    public function upload(string $resource, string $language, string $content): ResponseInterface
    {
        $endpoint = 'resource_translations_async_uploads';
        $uri      = $this->apiConnector->createUri($endpoint);
        $content  = \base64_encode($content);
        $body     = <<<EOT
{
    "data": {
        "attributes": {
            "content": "{$content}",
            "content_encoding": "base64",
            "file_type": "default"
        },
        "relationships": {
            "language": {
                "data": {
                    "type": "languages",
                    "id": "l:{$language}"
                }
            },
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
     * @see https://developers.transifex.com/reference/post_resource-translations-async-downloads
     */
    public function download(string $resource, string $language): ResponseInterface
    {
        $endpoint = 'resource_translations_async_downloads';
        $uri      = $this->apiConnector->createUri($endpoint);
        $body     = <<<EOT
{
    "data": {
        "relationships": {
            "language": {
                "data": {
                    "type": "languages",
                    "id": "l:{$language}"
                }
            },
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
}
