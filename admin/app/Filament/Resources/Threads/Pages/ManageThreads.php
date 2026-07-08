<?php

namespace App\Filament\Resources\Threads\Pages;

use App\Filament\Resources\Threads\ThreadResource;
use Filament\Resources\Pages\ManageRecords;

class ManageThreads extends ManageRecords
{
    protected static string $resource = ThreadResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
