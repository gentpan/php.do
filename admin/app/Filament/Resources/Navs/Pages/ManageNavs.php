<?php

namespace App\Filament\Resources\Navs\Pages;

use App\Filament\Resources\Navs\NavResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageNavs extends ManageRecords
{
    protected static string $resource = NavResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
