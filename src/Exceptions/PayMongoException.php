<?php

namespace Kirame\PayMongo\Exceptions;

use RuntimeException;

class PayMongoException extends RuntimeException
{
    protected ?array $responseBody;

    protected ?int $statusCode;

    public function __construct(string $message, ?int $statusCode = null, ?array $responseBody = null, ?\Throwable $previous = null)
    {
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;

        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }

    public function getErrorType(): ?string
    {
        return $this->responseBody['errors'][0]['code'] ?? null;
    }

    public function getErrorDetail(): ?string
    {
        return $this->responseBody['errors'][0]['detail'] ?? null;
    }
}
