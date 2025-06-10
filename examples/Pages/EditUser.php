<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Othyn\FilamentApiResources\Resources\Pages\EditApiRecord;

/**
 * Example Edit page for API-backed User resource.
 *
 * This demonstrates how to create an edit page that works with API-backed models.
 * The form data will be sent to the API via the model's save method.
 *
 * The EditApiRecord base class handles the API loading and updating automatically.
 */
class EditUser extends EditApiRecord
{
    protected static string $resource = UserResource::class;
}
