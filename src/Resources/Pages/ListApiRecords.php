<?php

declare(strict_types=1);

namespace Othyn\FilamentApiResources\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

abstract class ListApiRecords extends ListRecords
{
    protected function paginateTableQuery(Builder $query): Paginator
    {
        return $this->getModel()::getRowsPaginated(currentPage: $this->getTablePage());
    }
}
