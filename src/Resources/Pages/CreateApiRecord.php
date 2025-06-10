<?php

declare(strict_types=1);

namespace Othyn\FilamentApiResources\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

abstract class CreateApiRecord extends CreateRecord
{
    /**
     * Handle the creation of a new record via the API.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $record = $this->getModel()::create($data);

        if (! $record) {
            throw new \Exception('Failed to create record via API');
        }

        return $record;
    }
}
