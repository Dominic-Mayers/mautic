<?php declare(strict_types=1);

namespace Mautic\Transifex\Exception;

/**
 * Exception defining an invalid configuration for an API request's parameters.
 */
final class InvalidConfigurationException extends \InvalidArgumentException implements TransifexException
{
}
