<?php

declare(strict_types=1);

namespace Othyn\FilamentApiResources\Resources\Pages;

use Filament\Resources\Pages\ViewRecord;

abstract class ViewApiRecord extends ViewRecord
{
    public function mount(int|string $record): void
    {
        if (! $this->record = $this->getModel()::get($record)) {
            abort(404);
        }

        // The below is the same as the parent class, but we need to call it here to ensure the record is set
        $this->authorizeAccess();

        if (! $this->hasInfolist()) {
            $this->fillForm();
        }
    }

    /**
     * Refresh the record data from the API.
     *
     * Helper method for if you need to refresh the page state and repaint, such as on a custom page after a Livewire action.
     */
    protected function refreshRecord(bool $forceCacheRefresh = true): void
    {
        $freshRecord = $this->getModel()::get($this->record->getKey(), $forceCacheRefresh);

        if ($freshRecord) {
            $this->record = $freshRecord;
        }
    }
}
