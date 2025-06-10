<?php

declare(strict_types=1);

namespace Othyn\FilamentApiResources\Models;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Othyn\FilamentApiResources\Services\ApiService;

abstract class BaseApiModel extends Model
{
    /**
     * The endpoint to fetch data from.
     */
    protected static string $endpoint;

    /**
     * The key to use for the total number of results.
     * Default matches Laravel's paginated API response: data.total.
     */
    protected static string $totalKey = 'data.total';

    /**
     * The key to use for the results.
     * Default matches Laravel's paginated API response: data.data.
     */
    protected static string $resultsKey = 'data.data';

    /**
     * The type to use for the model's ID.
     */
    protected $keyType = 'string';

    /**
     * Get the API service instance.
     */
    protected function getApiService(): ApiService
    {
        return app(ApiService::class);
    }

    /**
     * Set the endpoint for this model.
     */
    public static function setEndpoint(string $endpoint): void
    {
        static::$endpoint = $endpoint;
    }

    /**
     * Get the endpoint for this model.
     */
    public static function getEndpoint(): string
    {
        return static::$endpoint;
    }

    /**
     * Set the total key for API responses.
     */
    public static function setTotalKey(string $totalKey): void
    {
        static::$totalKey = $totalKey;
    }

    /**
     * Set the results key for API responses.
     */
    public static function setResultsKey(string $resultsKey): void
    {
        static::$resultsKey = $resultsKey;
    }

    /**
     * Get the total count for the given endpoint.
     */
    public static function getTotalCount(): ?int
    {
        $instance = new static();
        $response = $instance->fetchResource(cacheSeconds: 60);

        return data_get($response, static::$totalKey, 0);
    }

    /**
     * Get paginated data for Filament tables.
     */
    public static function getRowsPaginated(int $currentPage = 1, ?int $perPage = null): Paginator
    {
        $instance = new static();

        // Use provided perPage, or fall back to Laravel's default (15)
        $perPage ??= $instance->getPerPage();

        $response = $instance->getAll(currentPage: $currentPage, perPage: $perPage);
        $entities = $instance->transformData($response);

        return new LengthAwarePaginator(
            items: $entities,
            total: data_get($response, static::$totalKey, $entities->count()),
            perPage: $perPage,
            currentPage: $currentPage,
        )->onEachSide(0);
    }

    /**
     * Get all resources from the API.
     */
    protected function getAll(int $currentPage = 1, ?int $perPage = null): array
    {
        return $this->fetchResource(currentPage: $currentPage, cacheSeconds: 60, perPage: $perPage);
    }

    /**
     * Get a specific resource from the API.
     */
    public static function get(int|string $id, bool $forceCacheRefresh = false): ?self
    {
        $instance = new static();

        try {
            $response = $instance->fetchResource(
                params: ['id' => $id],
                cacheSeconds: 60,
                forceCacheRefresh: $forceCacheRefresh
            );

            // Handle single resource response (not paginated)
            // Check if response.data is a single object (not an array of objects)
            if (isset($response['data']) && !isset($response['data']['data'])) {
                return $instance->makeInstance($response['data']);
            }

            // Handle paginated response - data is nested in data.data
            if (isset($response['data']['data']) && is_array($response['data']['data']) && count($response['data']['data']) > 0) {
                return $instance->makeInstance($response['data']['data'][0]);
            }

            return null;
        } catch (\Exception $e) {
            // Log the error or handle it as needed
            return null;
        }
    }

    /**
     * Create a new resource via the API.
     */
    public static function create(array $attributes): ?self
    {
        $instance = new static();

        try {
            $response = $instance->postResource(
                endpoint: static::$endpoint,
                data: $attributes
            );

            if (isset($response['data'])) {
                return $instance->makeInstance($response['data']);
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Save the model to the API.
     *
     * This method handles both creating new resources and updating existing ones.
     */
    public function save(array $options = []): bool
    {
        try {
            if ($this->exists) {
                // Update existing resource
                $response = $this->patchResource(
                    endpoint: static::$endpoint.'/'.$this->getKey(),
                    data: $this->getDirty()
                );
            } else {
                // Create new resource
                $response = $this->postResource(
                    endpoint: static::$endpoint,
                    data: $this->getAttributes()
                );
            }

            // Update the model with the response data
            if (isset($response['data'])) {
                $this->fill($response['data']);
                $this->exists = true;
                $this->syncOriginal();
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Update the resource with the given attributes.
     *
     * This method is used by EditApiRecord for consistent API updates.
     */
    public function updateRecord(array $attributes): bool
    {
        $this->fill($attributes);

        return $this->save();
    }

    /**
     * Fetch data from the API.
     */
    protected function fetchResource(array $params = [], ?int $currentPage = null, ?int $cacheSeconds = null, bool $forceCacheRefresh = false, ?int $perPage = null): array
    {
        return $this->getApiService()->fetch(
            endpoint: static::$endpoint,
            params: $params,
            currentPage: $currentPage,
            cacheSeconds: $cacheSeconds,
            forceCacheRefresh: $forceCacheRefresh,
            perPage: $perPage,
        );
    }

    /**
     * Create a resource via the API.
     */
    protected function postResource(string $endpoint, array $data = []): array
    {
        return $this->getApiService()->post($endpoint, $data);
    }

    /**
     * Update a resource via the API.
     */
    protected function patchResource(string $endpoint, array $data = []): array
    {
        return $this->getApiService()->patch($endpoint, $data);
    }

    /**
     * Delete a resource via the API.
     */
    public function delete()
    {
        return $this->deleteResource(endpoint: static::$endpoint.'/'.$this->getKey());
    }

    /**
     * Delete a resource from the API.
     */
    protected function deleteResource(string $endpoint): array
    {
        return $this->getApiService()->delete($endpoint);
    }

    /**
     * Transform API data into model instances.
     */
    protected function transformData(array $data): Collection
    {
        return collect(data_get($data, static::$resultsKey, []))
            ->map(fn ($data) => $this->makeInstance($data));
    }

    /**
     * Make a new instance of the model from the response data.
     */
    abstract protected function makeInstance(array $data): self;
}
