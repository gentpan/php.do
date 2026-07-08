<?php

namespace App\Filament\Resources\OnlineDailies\Pages;

use App\Filament\Resources\OnlineDailies\OnlineDailyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageOnlineDailies extends ManageRecords
{
    protected static string $resource = OnlineDailyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
