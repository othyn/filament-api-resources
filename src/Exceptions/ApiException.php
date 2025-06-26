<?php

declare(strict_types=1);

namespace Othyn\FilamentApiResources\Exceptions;

class ApiException extends \Exception
{
    protected int $statusCode;
    protected string $rawResponseBody;

    public function __construct(string $message, int $statusCode = 0, string $rawResponseBody = '', ?\Exception $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);

        $this->statusCode = $statusCode;
        $this->rawResponseBody = $rawResponseBody;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getRawResponseBody(): string
    {
        return $this->rawResponseBody;
    }

    public static function fromResponse(int $statusCode, string $responseBody): self
    {
        $data = json_decode($responseBody, true) ?? [];
        $message = $data['message'] ?? $data['error'] ?? "API request failed with status {$statusCode}";

        return new self($message, $statusCode, $responseBody);
    }
}
