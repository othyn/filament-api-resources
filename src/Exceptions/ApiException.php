<?php

declare(strict_types=1);

namespace Othyn\FilamentApiResources\Exceptions;

class ApiException extends \Exception
{
    protected int $statusCode;
    protected array $responseData;

    public function __construct(string $message, int $statusCode = 0, array $responseData = [], ?\Exception $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);

        $this->statusCode = $statusCode;
        $this->responseData = $responseData;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseData(): array
    {
        return $this->responseData;
    }

    public static function fromResponse(int $statusCode, string $responseBody): self
    {
        $data = json_decode($responseBody, true) ?? [];
        $message = $data['message'] ?? $data['error'] ?? "API request failed with status {$statusCode}";

        return new self($message, $statusCode, $data);
    }
}
