<?php declare(strict_types=1);

namespace Mautic\Transifex;

use Mautic\Transifex\Exception\MissingCredentialsException;
use Mautic\Transifex\Exception\NotReadyException;
use Mautic\Transifex\Exception\ResponseException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use SplQueue;

final class ApiConnector
{
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;
    private UriFactoryInterface $uriFactory;
    private ConfigInterface $config;

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
    }

    /**
     * Create a Request object for the given URI
     *
     * This method will also set the Authorization header for the request
     */
    public function createRequest(string $method, UriInterface $uri): RequestInterface
    {
        $request = $this->requestFactory->createRequest($method, $uri);
        $request = $request->withHeader('accept', 'application/vnd.api+json');
        $request = $request->withHeader('content-type', 'application/vnd.api+json');

        return $request->withHeader('authorization', $this->createAuthorizationHeader());
    }

    public function createUri(string $endpoint): UriInterface
    {
        if (\substr($endpoint, 0, 8) === 'https://') {
            $link = $endpoint;
        } else {
            $link = "{$this->config->getBaseUri()}/{$endpoint}";
        }

        return $this->uriFactory->createUri($link);
    }

    public function createStream(string $content): StreamInterface
    {
        return $this->streamFactory->createStream($content);
    }

    /**
     * Send a request to the API.
     *
     * @throws ResponseException if the response is not successful
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $response = $this->client->sendRequest($request);

        if ($response->getStatusCode() >= 400) {
            throw new ResponseException($request, $response);
        }

        return $response;
    }

    public function buildResourceString(string $resource): string
    {
        return "{$this->buildProjectString()}:r:{$resource}";
    }

    public function buildProjectString(): string
    {
        return "o:{$this->config->getOrganization()}:p:{$this->config->getProject()}";
    }

    /**
     * Helper method to create a Promise for further processing.
     */
    public function createPromise(ResponseInterface $response): Promise
    {
        $body = \json_decode($response->getBody()->__toString(), true);

        return new Promise($body['data']['id'], $body['data']['links']['self']);
    }

    /**
     * @param SplQueue<Promise>                          $promises
     * @param callable(ResponseInterface, Promise): void $onSuccess
     * @param callable(ResponseException, Promise): void $onError
     */
    public function fulfillPromises(SplQueue $promises, callable $onSuccess, callable $onError): void
    {
        $notReady = new SplQueue();

        while (!$promises->isEmpty()) {
            /** @var Promise $promise */
            $promise = $promises->dequeue();

            try {
                $onSuccess($this->getPromiseStatus($promise), $promise);
            } catch (NotReadyException $notReadyException) {
                $notReady->enqueue($promise);
            } catch (ResponseException $responseException) {
                $onError($responseException, $promise);
            }
        }

        if (!$notReady->isEmpty()) {
            \usleep(500000);
            $this->fulfillPromises($notReady, $onSuccess, $onError);
        }
    }

    public function getPromiseStatus(Promise $promise): ResponseInterface
    {
        $request  = $this->createRequest('GET', $this->createUri($promise->getCheckLink()));
        $response = $this->sendRequest($request);

        if ($response->getStatusCode() === 303) {
            $request  = $this->createRequest('GET', $this->createUri($response->getHeader('location')[0]));
            $response = $this->sendRequest($request);
        }

        $body   = $response->getBody()->__toString();
        $data   = \json_decode($body, true);
        $isJson = \json_last_error() === \JSON_ERROR_NONE;

        if ($isJson && isset($data['data']['attributes']['status'])) {
            if (\in_array($data['data']['attributes']['status'], ['pending', 'processing'])) {
                throw new NotReadyException($request, $response);
            }

            if (\in_array($data['data']['attributes']['status'], ['failed'])) {
                throw new ResponseException($request, $response);
            }
        }

        return $response;
    }

    /**
     * Creates the Authorization header for the request
     *
     * @throws MissingCredentialsException if credentials are not set
     */
    private function createAuthorizationHeader(): string
    {
        if (!$this->config->getApiToken()) {
            throw new MissingCredentialsException('Missing API token API authentication.');
        }

        return "Bearer {$this->config->getApiToken()}";
    }
}
