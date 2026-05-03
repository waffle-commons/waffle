<?php

declare(strict_types=1);

namespace Waffle\Handler;

use JsonException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Waffle\Commons\Contracts\Handler\ResponseConverterInterface;

final readonly class ControllerResponseConverter implements ResponseConverterInterface
{
    public function __construct(
        private ResponseFactoryInterface $factory,
    ) {}

    /**
     * @throws JsonException
     */
    #[\Override]
    public function convert(mixed $result): ResponseInterface
    {
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        if ($result === null) {
            return $this->factory->createResponse(204);
        }

        if (is_array($result) || $result instanceof \JsonSerializable) {
            $response = $this->factory->createResponse(200)->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode($result, JSON_THROW_ON_ERROR));
            return $response;
        }

        if (is_string($result)) {
            $response = $this->factory->createResponse(200)->withHeader('Content-Type', 'text/html');
            $response->getBody()->write($result);
            return $response;
        }

        throw new RuntimeException(sprintf(
            'Controller Error: Returned "%s", but ResponseInterface was expected and no conversion strategy matched.',
            get_debug_type($result),
        ));
    }
}
