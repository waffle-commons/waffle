<?php

declare(strict_types=1);

namespace WaffleTests\Helper;

use stdClass;
use Waffle\Commons\Contracts\Serializer\DtoSerializerInterface;

/**
 * A serializer that records every call and answers with a fixed DTO and a fixed
 * JSON document.
 */
final class RecordingSerializer implements DtoSerializerInterface
{
    public const string JSON = '{"serialized":true}';

    /** @var list<array{array, string}> */
    public array $denormalized = [];

    /** @var list<array{string, string}> */
    public array $deserialized = [];

    /** @var list<object> */
    public array $serialized = [];

    public function __construct(
        private readonly ?object $dto = null,
        private readonly bool $handles = true,
    ) {}

    #[\Override]
    public function supports(string $class): bool
    {
        return $this->handles;
    }

    #[\Override]
    public function denormalize(array $data, string $class): object
    {
        $this->denormalized[] = [$data, $class];

        return $this->dto ?? new stdClass();
    }

    #[\Override]
    public function deserialize(string $json, string $class): object
    {
        $this->deserialized[] = [$json, $class];

        return $this->dto ?? new stdClass();
    }

    #[\Override]
    public function serialize(object $dto): string
    {
        $this->serialized[] = $dto;

        return self::JSON;
    }
}
