<?php

declare(strict_types=1);

namespace Othyn\FilamentApiResources\Services;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Othyn\FilamentApiResources\Exceptions\ApiException;

class ApiService
{
    protected string $baseUrl;
    protected array $defaultHeaders;
    protected int $timeout;
    protected int $retryAttempts;
    protected int $retryDelay;

    public function __construct()
    {
        $this->baseUrl = config('filament-api-resources.base_url', '');
        $this->defaultHeaders = config('filament-api-resources.default_headers', []);
        $this->timeout = config('filament-api-resources.http.timeout', 30);
        $this->retryAttempts = config('filament-api-resources.http.retry_attempts', 3);
        $this->retryDelay = config('filament-api-resources.http.retry_delay', 100);
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
        return array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], $this->defaultHeaders, $additionalHeaders);
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

        if (! empty($params)) {
            $endpoint .= '?' . http_build_query($params);
        }

        return $endpoint;
    }

    /**
     * Get the cache key for a request.
     */
    public function getRequestKey(string $endpoint): string
    {
        $prefix = config('filament-api-resources.cache.prefix', 'filament_api_');

        return $prefix . md5($endpoint);
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
                ->get($this->baseUrl . $endpoint);

            if (! $response->successful()) {
                throw ApiException::fromResponse($response->status(), $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
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
                ->post($this->baseUrl . $endpoint, $data);

            if (! $response->successful()) {
                throw ApiException::fromResponse($response->status(), $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
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
                ->patch($this->baseUrl . $endpoint, $data);

            if (! $response->successful()) {
                throw ApiException::fromResponse($response->status(), $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
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
                ->put($this->baseUrl . $endpoint, $data);

            if (! $response->successful()) {
                throw ApiException::fromResponse($response->status(), $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
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
                ->delete($this->baseUrl . $endpoint, $data);

            if (! $response->successful()) {
                throw ApiException::fromResponse($response->status(), $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to delete resource')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return [];
        }
    }
}
