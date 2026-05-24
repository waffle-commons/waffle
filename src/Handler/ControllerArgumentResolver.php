<?php

declare(strict_types=1);

namespace Waffle\Handler;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionNamedType;
use RuntimeException;
use TypeError;
use Waffle\Commons\Contracts\Attribute\Dto;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Commons\Contracts\Exception\Validation\ValidationExceptionInterface;
use Waffle\Commons\Contracts\Handler\ArgumentResolverInterface;
use Waffle\Commons\Contracts\Service\ReflectionServiceInterface;
use Waffle\Exception\ValidationException;

final readonly class ControllerArgumentResolver implements ArgumentResolverInterface
{
    public function __construct(
        private ContainerInterface $container,
        private ReflectionServiceInterface $reflectionService,
    ) {}

    #[\Override]
    public function resolve(
        string|object $controller,
        string $method,
        ServerRequestInterface $request,
        array $routeParams,
    ): array {
        $args = [];

        foreach ($this->reflectionService->getMethodParameters($controller, $method) as $parameter) {
            $type = $parameter->getType();
            $typeName = $type?->getName();
            $name = $parameter->getName();

            // A. Handle Route Parameters (by name)
            if (array_key_exists($name, $routeParams)) {
                $val = $routeParams[$name];

                // Auto-cast if type matches
                if ($type instanceof ReflectionNamedType && $type->isBuiltin()) {
                    $val = match ($type->getName()) {
                        'int' => (int) $val,
                        'float' => (float) $val,
                        'bool' => filter_var($val, FILTER_VALIDATE_BOOLEAN),
                        default => $val,
                    };
                }

                $args[] = $val;
                continue;
            }

            // B. Handle Typed Dependencies (Services & Request)
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                // 1. Inject Request if requested
                if (
                    $typeName === ServerRequestInterface::class
                    || is_subclass_of($typeName, ServerRequestInterface::class)
                ) {
                    $args[] = $request;
                    continue;
                }

                // 2. Hydrate DTOs marked with #[Dto] from the parsed request body (RFC-011).
                //    Property Hooks in the DTO perform the validation; we only assemble args.
                if ($typeName !== null && $this->reflectionService->hasAttribute($typeName, Dto::class)) {
                    $args[] = $this->hydrateDto($typeName, $request);
                    continue;
                }

                // 3. Inject Service from Container
                if ($this->container->has($type->getName())) {
                    $args[] = $this->container->get($type->getName());
                    continue;
                }
            }

            // C. Handle Default Value
            if ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
                continue;
            }

            // D. Handle Nullable
            if ($parameter->allowsNull()) {
                $args[] = null;
                continue;
            }

            throw new RuntimeException(sprintf(
                'Controller Error: Argument "$%s" in "%s::%s" cannot be resolved. Check type hints or route parameters.',
                $name,
                is_object($controller) ? get_class($controller) : $controller,
                $method,
            ));
        }

        return $args;
    }

    /**
     * Hydrates a `#[Dto]`-marked class from the request's parsed body.
     *
     * Validation is delegated to the DTO's Property Hooks (RFC-011 §3.1). This
     * method only:
     *   - Guards that the parsed body is an associative array,
     *   - Restricts the constructor input to declared parameter names,
     *   - Surfaces missing-required-key failures as ValidationException.
     *
     * @param class-string $dtoClass
     * @throws ValidationException
     */
    private function hydrateDto(string $dtoClass, ServerRequestInterface $request): object
    {
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            throw new ValidationException(message: sprintf('Expected JSON object body for "%s".', $dtoClass));
        }

        $params = $this->reflectionService->getConstructorParameters($dtoClass);

        // No constructor → no inputs to hydrate; the DTO is its own default.
        if ($params === null) {
            return $this->reflectionService->newInstance($dtoClass);
        }

        $args = [];
        foreach ($params as $param) {
            $paramName = $param->getName();

            if (array_key_exists($paramName, $body)) {
                $args[$paramName] = $body[$paramName];
                continue;
            }

            if ($param->isDefaultValueAvailable()) {
                continue; // omit — PHP fills in default at call time.
            }

            if ($param->allowsNull()) {
                $args[$paramName] = null;
                continue;
            }

            throw new ValidationException(
                message: sprintf('Missing required field "%s" for "%s".', $paramName, $dtoClass),
                field: $paramName,
            );
        }

        // Construction may fail inside the DTO's Property Hooks (validation logic
        // lives there per RFC-011). Translate every hook-driven rejection into a
        // unified ValidationException so ErrorHandlerMiddleware can emit an
        // RFC 7807 422 response — regardless of whether the DTO threw a typed
        // ValidationExceptionInterface, a plain \InvalidArgumentException, or
        // tripped a \TypeError from the constructor signature.
        try {
            return $this->reflectionService->newInstance($dtoClass, $args);
        } catch (ValidationExceptionInterface $e) {
            // DTO emitted a typed validation error — preserve `field` + bubble.
            throw $e;
        } catch (InvalidArgumentException|TypeError $e) {
            // TypeError at constructor entry is reachable from user input (e.g. body
            // sends string "thirty" where the DTO declares int $age). Translate to a
            // 422 instead of letting a 500 surface — the input is invalid, not the code.
            throw new ValidationException(message: $e->getMessage(), field: null, code: 422, previous: $e);
        }
    }
}
