<?php

declare(strict_types=1);

namespace Othyn\FilamentApiResources\Services;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Othyn\FilamentApiResources\Exceptions\ApiException;

class ApiService
{
    protected string $baseUrl;
    protected array $defaultHeaders;
    protected int $timeout;
    protected int $retryAttempts;
    protected int $retryDelay;
    protected bool $loggingEnabled;
    protected string $logChannel;
    protected string $logLevel;
    protected bool $includeRequestData;
    protected bool $includeResponseData;

    public function __construct()
    {
        $this->baseUrl = config('filament-api-resources.base_url', '');
        $this->defaultHeaders = config('filament-api-resources.default_headers', []);
        $this->timeout = config('filament-api-resources.http.timeout', 30);
        $this->retryAttempts = config('filament-api-resources.http.retry_attempts', 3);
        $this->retryDelay = config('filament-api-resources.http.retry_delay', 100);
        $this->loggingEnabled = config('filament-api-resources.logging.enabled', true);
        $this->logChannel = config('filament-api-resources.logging.channel', 'default');
        $this->logLevel = config('filament-api-resources.logging.level', 'error');
        $this->includeRequestData = config('filament-api-resources.logging.include_request_data', true);
        $this->includeResponseData = config('filament-api-resources.logging.include_response_data', false);
    }

    /**
     * Set the base URL for API requests.
     */
    public function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    /**
     * Set default headers for API requests.
     */
    public function setDefaultHeaders(array $headers): self
    {
        $this->defaultHeaders = $headers;

        return $this;
    }

    /**
     * Add a default header for API requests.
     */
    public function addDefaultHeader(string $key, string $value): self
    {
        $this->defaultHeaders[$key] = $value;

        return $this;
    }

    /**
     * Set the timeout for API requests.
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Set the retry attempts for API requests.
     */
    public function setRetryAttempts(int $attempts): self
    {
        $this->retryAttempts = $attempts;

        return $this;
    }

    /**
     * Set the retry delay for API requests.
     */
    public function setRetryDelay(int $delay): self
    {
        $this->retryDelay = $delay;

        return $this;
    }

    /**
     * Get the headers for API requests.
     */
    protected function getHeaders(array $additionalHeaders = []): array
    {
        $resolvedDefaultHeaders = [];
        foreach ($this->defaultHeaders as $key => $value) {
            if ($value instanceof \Closure) {
                $resolvedDefaultHeaders[$key] = $value();
            } else {
                $resolvedDefaultHeaders[$key] = $value;
            }
        }

        $resolvedAdditionalHeaders = [];
        foreach ($additionalHeaders as $key => $value) {
            if ($value instanceof \Closure) {
                $resolvedAdditionalHeaders[$key] = $value();
            } else {
                $resolvedAdditionalHeaders[$key] = $value;
            }
        }

        return array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], $resolvedDefaultHeaders, $resolvedAdditionalHeaders);
    }

    /**
     * Compile the endpoint with the given params and current page.
     */
    public function compileEndpoint(string $endpoint, array $params = [], ?int $currentPage = null, ?int $perPage = null): string
    {
        if ($currentPage) {
            $pageParam = config('filament-api-resources.pagination_params.page', 'page');
            $perPageParam = config('filament-api-resources.pagination_params.per_page', 'per_page');

            $params[$pageParam] = $currentPage;
            $params[$perPageParam] = $perPage ?? 15;
        }

        if (!empty($params)) {
            $endpoint .= '?'.http_build_query($params);
        }

        return $endpoint;
    }

    /**
     * Get the cache key for a request.
     */
    public function getRequestKey(string $endpoint): string
    {
        $prefix = config('filament-api-resources.cache.prefix', 'filament_api_');

        return $prefix.md5($endpoint);
    }

    /**
     * Log an API exception with configurable details.
     */
    protected function logException(\Exception $exception, string $method, string $endpoint, array $data = [], array $headers = [], ?array $responseData = null): void
    {
        if (!$this->loggingEnabled) {
            return;
        }

        $logData = [
            'method' => $method,
            'endpoint' => $this->baseUrl.$endpoint,
            'exception' => [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ],
        ];

        if ($this->includeRequestData && !empty($data)) {
            $logData['request_data'] = $data;
        }

        if (!empty($headers)) {
            $logData['headers'] = $this->getHeaders($headers);
        }

        if ($this->includeResponseData && $responseData !== null) {
            $logData['response_data'] = $responseData;
        }

        $logger = $this->logChannel === 'default' ? Log::getFacadeRoot() : Log::channel($this->logChannel);

        $logger->log($this->logLevel, 'API request failed', $logData);
    }

    /**
     * Fetch data from the API, optionally caching the response.
     */
    public function fetch(string $endpoint, array $params = [], ?int $currentPage = null, ?int $cacheSeconds = null, bool $forceCacheRefresh = false, ?int $perPage = null): array
    {
        $endpoint = $this->compileEndpoint($endpoint, $params, $currentPage, $perPage);
        $requestKey = $this->getRequestKey($endpoint);

        // If no caching is requested, fetch directly
        if ($cacheSeconds === null) {
            return $this->rawFetch($endpoint);
        }

        // If force refresh is requested, forget the cache first
        if ($forceCacheRefresh) {
            Cache::forget($requestKey);
        }

        return Cache::remember($requestKey, $cacheSeconds, fn () => $this->rawFetch($endpoint));
    }

    /**
     * Fetch data from the API.
     */
    protected function rawFetch(string $endpoint, array $headers = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryAttempts, $this->retryDelay)
                ->withHeaders($this->getHeaders($headers))
                ->get($this->baseUrl.$endpoint);

            if (!$response->successful()) {
                throw ApiException::fromResponse($response->status(), $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->logException($e, 'GET', $endpoint, [], $headers, isset($response) ? $response->json() : null);

            Notification::make()
                ->title('Failed to fetch data')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return [];
        }
    }

    /**
     * Create a resource in the API.
     */
    public function post(string $endpoint, array $data = [], array $headers = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryAttempts, $this->retryDelay)
                ->withHeaders($this->getHeaders($headers))
                ->post($this->baseUrl.$endpoint, $data);

            if (!$response->successful()) {
                throw ApiException::fromResponse($response->status(), $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->logException($e, 'POST', $endpoint, $data, $headers, isset($response) ? $response->json() : null);

            Notification::make()
                ->title('Failed to create resource')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return [];
        }
    }

    /**
     * Update a resource in the API.
     */
    public function patch(string $endpoint, array $data = [], array $headers = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryAttempts, $this->retryDelay)
                ->withHeaders($this->getHeaders($headers))
                ->patch($this->baseUrl.$endpoint, $data);

            if (!$response->successful()) {
                throw ApiException::fromResponse($response->status(), $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->logException($e, 'PATCH', $endpoint, $data, $headers, isset($response) ? $response->json() : null);

            Notification::make()
                ->title('Failed to update resource')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return [];
        }
    }

    /**
     * Put a resource in the API.
     */
    public function put(string $endpoint, array $data = [], array $headers = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryAttempts, $this->retryDelay)
                ->withHeaders($this->getHeaders($headers))
                ->put($this->baseUrl.$endpoint, $data);

            if (!$response->successful()) {
                throw ApiException::fromResponse($response->status(), $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->logException($e, 'PUT', $endpoint, $data, $headers, isset($response) ? $response->json() : null);

            Notification::make()
                ->title('Failed to update resource')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return [];
        }
    }

    /**
     * Delete a resource from the API.
     */
    public function delete(string $endpoint, array $data = [], array $headers = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryAttempts, $this->retryDelay)
                ->withHeaders($this->getHeaders($headers))
                ->delete($this->baseUrl.$endpoint, $data);

            if (!$response->successful()) {
                throw ApiException::fromResponse($response->status(), $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            $this->logException($e, 'DELETE', $endpoint, $data, $headers, isset($response) ? $response->json() : null);

            Notification::make()
                ->title('Failed to delete resource')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return [];
        }
    }
}
