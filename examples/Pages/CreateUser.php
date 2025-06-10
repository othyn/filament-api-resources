<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Othyn\FilamentApiResources\Resources\Pages\CreateApiRecord;

/**
 * Example Create page for API-backed User resource.
 *
 * This demonstrates how to create a create page that works with API-backed models.
 * The form data will be sent to the API via the model's create method.
 *
 * The CreateApiRecord base class handles the API creation automatically.
 */
class CreateUser extends CreateApiRecord
{
    protected static string $resource = UserResource::class;
}
