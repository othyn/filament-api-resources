<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Othyn\FilamentApiResources\Resources\Pages\ListApiRecords;

/**
 * Example List page for API-backed User resource.
 *
 * This demonstrates how to create a list page that works with API-backed models.
 * The ListApiRecords base class handles pagination automatically.
 */
class ListUsers extends ListApiRecords
{
    protected static string $resource = UserResource::class;
}
