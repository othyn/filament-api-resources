<?php

declare(strict_types=1);

namespace App\Models;

use Othyn\FilamentApiResources\Models\BaseApiModel;

/**
 * Example API Model for a User resource.
 *
 * This demonstrates how to create an API-backed model for use with Filament.
 * This example assumes your API returns responses in the following Laravel-like format:
 *
 * For paginated responses:
 * {
 *   "data": {
 *     "current_page": 1,
 *     "data": [
 *       {
 *         "id": 1,
 *         "name": "John Doe",
 *         "email": "john@example.com",
 *         "created_at": "2024-01-01T00:00:00Z"
 *       }
 *     ],
 *     "total": 100,
 *     "per_page": 15
 *   }
 * }
 *
 * For single resource responses:
 * {
 *   "data": {
 *     "id": 1,
 *     "name": "John Doe",
 *     "email": "john@example.com",
 *     "created_at": "2024-01-01T00:00:00Z"
 *   }
 * }
 */
class User extends BaseApiModel
{
    /**
     * The API endpoint for this resource.
     */
    protected static string $endpoint = '/users';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'email_verified_at',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Create a model instance from API response data.
     *
     * Note: In production, you should validate the API response data
     * before creating the model instance to ensure data integrity.
     */
    protected function makeInstance(array $data): self
    {
        $user = new self();

        // In production, add validation here:
        // $validator = Validator::make($data, [
        //     'id' => 'required|integer',
        //     'name' => 'required|string|max:255',
        //     'email' => 'required|email|max:255',
        // ]);
        // if ($validator->fails()) {
        //     throw new \InvalidArgumentException('Invalid API response data');
        // }

        $user->fill([
            'id' => $data['id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'email_verified_at' => $data['email_verified_at'] ?? null,
            'created_at' => $data['created_at'] ?? null,
            'updated_at' => $data['updated_at'] ?? null,
        ]);

        $user->exists = true;

        return $user;
    }

    /**
     * Get the user's display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->email;
    }

    /**
     * Check if the user's email is verified.
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }
}
