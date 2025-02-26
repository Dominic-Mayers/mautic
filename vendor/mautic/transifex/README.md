*This is fork of [archived repository](https://github.com/mautic/Transifex-API) by [Michael Babker](https://github.com/mbabker). Mautic use this library and so we decided to maintain it.*

Transifex API Package [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mautic/Transifex-API/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mautic/Transifex-API/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/mautic/Transifex-API/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/mautic/Transifex-API/?branch=master) [![Build Status](https://scrutinizer-ci.com/g/mautic/Transifex-API/badges/build.png?b=master)](https://scrutinizer-ci.com/g/mautic/Transifex-API/build-status/master) [![Build Status](https://travis-ci.org/mautic/Transifex-API.svg?branch=master)](https://travis-ci.org/mautic/Transifex-API)
===============

[![Latest Stable Version](https://poser.pugx.org/mautic/transifex/v/stable.svg)](https://packagist.org/packages/mautic/transifex)
[![Total Downloads](https://poser.pugx.org/mautic/transifex/downloads.svg)](https://packagist.org/packages/mautic/transifex)
[![Latest Unstable Version](https://poser.pugx.org/mautic/transifex/v/unstable.svg)](https://packagist.org/packages/mautic/transifex)
[![License](https://poser.pugx.org/mautic/transifex/license.svg)](https://packagist.org/packages/mautic/transifex)

The *Transifex API Package* is a PHP client for accessing the [Transifex API](http://docs.transifex.com/api/).

Requirements
------------

* PHP 7.4 or later
* Any [PSR-17 compatible](https://www.php-fig.org/psr/psr-17/) factories
* Any [PSR-18 compatible](https://www.php-fig.org/psr/psr-18/) HTTP client

Installation
------------

## Installation via GIT

Get the source code from GIT:

```sh
git clone git://github.com/mautic/Transifex-API.git
```

## Installation via Composer

To include this package in your Composer project, run the following command from the command line:

```sh
composer require mautic/transifex
```

Documentation
-------------

The Transifex API package provides a PHP interface for interacting with the [Transifex API](https://developers.transifex.com/reference/api-introduction).

### Basic Use

The primary interface for interacting with the Transifex package is the `Transifex` class. This class serves as the API factory and allows developers to manage the options used by the API objects and HTTP connector as well as retrieve instances of the API objects. To create a `Transifex` object, you only need to instantiate it with the appropriate dependencies.

```php
use Mautic\Transifex\ApiFactory;
use Mautic\Transifex\Config;
use Mautic\Transifex\Transifex;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

$client = new ClientInterface(); // or any PSR-18 HTTP client
$requestFactory = new RequestFactoryInterface(); // or any PSR-17 Request factory
$streamFactory = new StreamFactoryInterface(); // or any PSR-17 Stream factory
$uriFactory = new UriFactoryInterface(); // or any PSR-17 URI factory
$config = new Config();
$config->setApiToken('some-api-token');
$config->setOrganization('some-organization');
$config->setProject('some-project');
$transifex = new Transifex($client, $requestFactory, $streamFactory, $uriFactory, $config);
```

[Follow the Transifex documentation on how to get the API Token.](https://developers.transifex.com/reference/api-authentication)

The `Config` object can be also created from environmental variables:
```php
use Mautic\Transifex\Config;
putenv('TRANSIFEX_API_TOKEN=some-api-token');
putenv('TRANSIFEX_ORGANIZATION=some-organization');
putenv('TRANSIFEX_PROJECT=some-project');
$config = Config::fromEnv();
```

To retrieve an instance of an API object, you would use the `get()` method. API objects are named based on the documented sections of the Transifex API. To retrieve an object that can interface with the "resources" API section, you would use the following code:

```php
use \Mautic\Transifex\Connector\Resources;
$resources = $transifex->getConnector(Resources::class);
\assert($resources instanceof Resources);
```

### API Responses

This package returns a [PSR-7 compatible](https://www.php-fig.org/psr/psr-7/) response created by the underlying [PSR-18 HTTP client](https://www.php-fig.org/psr/psr-18/).

This package is not catching Exceptions thrown by the HTTP client so users implementing this package should implement appropriate error handling mechanisms.

An exception of `Mautic\Transifex\Exception\ResponseException` will be thrown when the response code is > 400. The error message contains the URI, request and response. This simplifies debugging. The Request and Response object can be retreived from this exception.

### Resources

Resources are the basic files that should be translated.

Get the Resource connector for the bellow examples like so:
```php
use Mautic\Transifex\Connector\Resources;

$resources = $transifex->getConnector(Resources::class);
\assert($resources instanceof Resources);
```

#### Get all resources

Docs: [https://developers.transifex.com/reference/get_resources](https://developers.transifex.com/reference/get_resources)

```php
$response = $resources->getAll();
$body     = json_decode($response->getBody()->__toString(), true);
```

#### Create a resource

This is a syncronous operation so you get the response rightaway. You need another request to upload a content to this resource though. See the [docs](https://developers.transifex.com/reference/post_resources) for example responses.

For the list of available i18n formats (the third param) trigger [this endpoint](https://developers.transifex.com/reference/get_i18n-formats).

```php
$response = $resources->create('Resource A', 'resource-a', 'INI');
$body     = json_decode($response->getBody()->__toString(), true);
```

#### Upload resource content

This is an asynchronous operation so the first request will just add the task to the queue and returns a unique ID See the [docs](https://developers.transifex.com/reference/post_resource-strings-async-uploads). Then we have to poll another endpoint for the status of this task.

```php
use Mautic\Transifex\Promise;
use Mautic\Transifex\Exception\ResponseException;
use Psr\Http\Message\ResponseInterface;

$response = $resources->uploadContent('resource-a', "something=\"Something\"\nsomething.else=\"Something Else\"\n");
$promise  = $transifex->getApiConnector()->createPromise($response);
$promises = new \SplQueue();
$promise->setFilePath('/some/file.ini'); // In the real world it is useful to map your file to this promise. Useful for later processing.
$promises->enqueue($promise); // In the real world, there would be multiple promises.
usleep(500000); // Give Transifex a 1/2 second so we make 1 request instead of 2.
$transifex->getApiConnector()->fulfillPromises(
    $promises,
    function (ResponseInterface $response, Promise $promise) {
        echo "Resource for {$promise->getFilePath()} was uploaded successfully";
    },
    function (ResponseException $exception, Promise $promise) {
        echo "Resource upload for {$promise->getFilePath()} failed with {$exception->getMessage()}";
    }
);
```

The polling for promise status happens automatically every 0.5 seconds.

As fetching for the promise status happens asynchronously and the order of processed promises is not cretain there are callbacks for your implementation to react on successful and failed statuses. Another perk is that further processing is not blocked until all promisses are resolved.

#### Deleting a resource

Docs: [https://developers.transifex.com/reference/delete_resources-resource-id](https://developers.transifex.com/reference/delete_resources-resource-id)

```php
$response = $resources->delete('resource-a');
```

### Translations

Translations translate the resource content to another languages.

Get the Translations connector for the bellow examples like so:
```php
use Mautic\Transifex\Connector\Translations;

$translations = $transifex->getConnector(Translations::class);
\assert($translations instanceof Translations);
```

#### Upload a translation

[Uploading a translation](https://developers.transifex.com/reference/post_resource-translations-async-uploads) is also asynchronous. The worflow is similar to downloading translations or resources.

```php
$response = $translations->upload(self::RESOURCE_SLUG, 'cs', "something=\"Něco\"\n");
$promise  = $transifex->getApiConnector()->createPromise($response);
$promises = new SplQueue();
$promise->setFilePath('/some/file.ini'); // In the real world it is useful to map your file to this promise. Useful for later processing.
$promises->enqueue($promise); // In the real world, there would be multiple promises.
usleep(500000); // Give Transifex a 1/2 second so we make 1 request instead of 2.
$transifex->getApiConnector()->fulfillPromises(
    $promises,
    function (ResponseInterface $response, Promise $promise) {
        echo "Translation for {$promise->getFilePath()} was uploaded successfully";
    },
    function (ResponseException $exception, Promise $promise) {
        echo "Translation upload for {$promise->getFilePath()} failed with {$exception->getMessage()}";
    }
);
```

#### Download a translation

This is the endpoint you need to get the work of the translators back to your app. Also an async operation. [Check the docs](https://developers.transifex.com/reference/post_resource-translations-async-downloads). It uses the same pattern as the others:

```php
$response = $translations->download(self::RESOURCE_SLUG, 'cs');
$promise  = $transifex->getApiConnector()->createPromise($response);
$promises = new SplQueue();
$promise->setFilePath('/some/file.ini'); // In the real world it is useful to map your file to this promise. Useful for later processing.
$promises->enqueue($promise); // In the real world, there would be multiple promises.

usleep(500000); // Give Transifex a 1/2 second so we make 1 request instead of 2.


// Assert that the translation content was downloaded successfully.
$transifex->getApiConnector()->fulfillPromises(
    $promises,
    function (ResponseInterface $response) use (&$successCounter, &$translationContent) {
        $translationContent = $response->getBody()->__toString();
        echo "Translation for {$promise->getFilePath()} was downloaded successfully. Here's the content:\n{$translationContent}";
    },
    function (ResponseException $exception) {
        echo "Translation download for {$promise->getFilePath()} failed with {$exception->getMessage()}";
    }
);
```

### Tests

There is one functional test that can run all the covered API requests against an existing Transifex project. It needs a configuration to connect to that project. Copy the `phpunit.xml.dist` file and rename it to `phpunit.xml`. This creates an override of the default PHPUNIT configuration. Fill in the organization, project and API token values. The test project must have the `cs` translation available.

To run the live test execute `composer test -- --testsuite=Functional`
To run the unit tests execute `composer test -- --testsuite=Unit`

Run the whole test suite: `composer test`
Run static analysis checks: `composer phpstan`
Run code style checks: `composer cs`
Fix code style issues: `composer fixcs`
