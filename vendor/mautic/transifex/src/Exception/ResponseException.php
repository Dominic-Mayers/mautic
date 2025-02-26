<?php declare(strict_types=1);

namespace Mautic\Transifex\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * When API Response returns response code greater than 400 it will be throws as this exception.
 */
class ResponseException extends \RuntimeException implements TransifexException
{
    private ResponseInterface $response;
    private RequestInterface $request;

    public function __construct(RequestInterface $request, ResponseInterface $response)
    {
        $this->request  = $request;
        $this->response = $response;

        $message = "Request to {$request->getUri()}: {$request->getBody()->__toString()} failed with response {$response->getStatusCode()}: {$response->getBody()->getContents()}";
    
        parent::__construct($message, $response->getStatusCode());
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
