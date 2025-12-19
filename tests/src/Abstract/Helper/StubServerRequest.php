<?php

declare(strict_types=1);

namespace WaffleTests\Abstract\Helper;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

class StubServerRequest implements ServerRequestInterface
{
    private array $attributes = [];
    private array $queryParams = [];
    private array $parsedBody = [];
    private array $cookieParams = [];
    private array $uploadedFiles = [];
    private array $serverParams = [];
    private string $method;
    private UriInterface|null $uri;

    public function __construct(string $method = 'GET', string|UriInterface $uri = '/')
    {
        $this->method = $method;
        if (is_string($uri)) {
            $this->uri = new class($uri) implements UriInterface {
                public function __construct(
                    private string $path,
                ) {}

                public function getScheme(): string
                {
                    return '';
                }

                public function getAuthority(): string
                {
                    return '';
                }

                public function getUserInfo(): string
                {
                    return '';
                }

                public function getHost(): string
                {
                    return '';
                }

                public function getPort(): null|int
                {
                    return null;
                }

                public function getPath(): string
                {
                    return $this->path;
                }

                public function getQuery(): string
                {
                    return '';
                }

                public function getFragment(): string
                {
                    return '';
                }

                public function withScheme($scheme): UriInterface
                {
                    return $this;
                }

                public function withUserInfo($user, $password = null): UriInterface
                {
                    return $this;
                }

                public function withHost($host): UriInterface
                {
                    return $this;
                }

                public function withPort($port): UriInterface
                {
                    return $this;
                }

                public function withPath($path): UriInterface
                {
                    $n = clone $this;
                    $n->path = $path;
                    return $n;
                }

                public function withQuery($query): UriInterface
                {
                    return $this;
                }

                public function withFragment($fragment): UriInterface
                {
                    return $this;
                }

                public function __toString(): string
                {
                    return $this->path;
                }
            };
        } else {
            $this->uri = $uri;
        }
    }

    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $n = clone $this;
        $n->cookieParams = $cookies;
        return $n;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query): ServerRequestInterface
    {
        $n = clone $this;
        $n->queryParams = $query;
        return $n;
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        $n = clone $this;
        $n->uploadedFiles = $uploadedFiles;
        return $n;
    }

    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    public function withParsedBody($data): ServerRequestInterface
    {
        $n = clone $this;
        $n->parsedBody = (array) $data;
        return $n;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute($name, $value): ServerRequestInterface
    {
        $n = clone $this;
        $n->attributes[$name] = $value;
        return $n;
    }

    public function withoutAttribute($name): ServerRequestInterface
    {
        $n = clone $this;
        unset($n->attributes[$name]);
        return $n;
    }

    // MessageInterface
    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function withProtocolVersion($version): ServerRequestInterface
    {
        return $this;
    }

    public function getHeaders(): array
    {
        return [];
    }

    public function hasHeader($name): bool
    {
        return false;
    }

    public function getHeader($name): array
    {
        return [];
    }

    public function getHeaderLine($name): string
    {
        return '';
    }

    public function withHeader($name, $value): ServerRequestInterface
    {
        return $this;
    }

    public function withAddedHeader($name, $value): ServerRequestInterface
    {
        return $this;
    }

    public function withoutHeader($name): ServerRequestInterface
    {
        return $this;
    }

    public function getBody(): StreamInterface
    {
        return new class implements StreamInterface {
            public function __toString(): string
            {
                return '';
            }

            public function close(): void
            {
            }

            public function detach()
            {
                return null;
            }

            public function getSize(): null|int
            {
                return 0;
            }

            public function tell(): int
            {
                return 0;
            }

            public function eof(): bool
            {
                return true;
            }

            public function isSeekable(): bool
            {
                return false;
            }

            public function seek($offset, $whence = SEEK_SET): void
            {
            }

            public function rewind(): void
            {
            }

            public function isWritable(): bool
            {
                return false;
            }

            public function write($string): int
            {
                return 0;
            }

            public function isReadable(): bool
            {
                return true;
            }

            public function read($length): string
            {
                return '';
            }

            public function getContents(): string
            {
                return '';
            }

            public function getMetadata($key = null)
            {
                return null;
            }
        };
    }

    public function withBody(StreamInterface $body): ServerRequestInterface
    {
        return $this;
    }

    // RequestInterface
    public function getRequestTarget(): string
    {
        return '/';
    }

    public function withRequestTarget($requestTarget): ServerRequestInterface
    {
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod($method): ServerRequestInterface
    {
        $n = clone $this;
        $n->method = $method;
        return $n;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false): ServerRequestInterface
    {
        $n = clone $this;
        $n->uri = $uri;
        return $n;
    }
}
