<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Othyn\FilamentApiResources\Resources\Pages\ViewApiRecord;

/**
 * Example View page for API-backed User resource.
 *
 * This demonstrates how to create a view page that works with API-backed models.
 * The ViewApiRecord base class handles loading the record from the API automatically.
 */
class ViewUser extends ViewApiRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Example of how to refresh data from the API.
     * This can be useful after performing actions that modify the data.
     */
    public function refreshData(): void
    {
        $this->refreshRecord(forceCacheRefresh: true);

        $this->notify('User data refreshed successfully!');
    }
}
