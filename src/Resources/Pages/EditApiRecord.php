<?php

declare(strict_types=1);

namespace Othyn\FilamentApiResources\Resources\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

abstract class EditApiRecord extends EditRecord
{
    /**
     * Mount the page and load the record from the API.
     */
    public function mount(int|string $record): void
    {
        if (! $this->record = $this->getModel()::get($record)) {
            abort(404);
        }

        $this->authorizeAccess();
        $this->fillForm();
    }

    /**
     * Handle the update of the record via the API.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (method_exists($record, 'updateRecord')) {
            $success = $record->updateRecord($data);
        } else {
            $record->fill($data);
            $success = $record->save();
        }

        if (! $success) {
            throw new \Exception('Failed to update record via API');
        }

        return $record;
    }
}
