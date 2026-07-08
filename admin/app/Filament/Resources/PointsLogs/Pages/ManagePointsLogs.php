<?php

namespace App\Filament\Resources\PointsLogs\Pages;

use App\Filament\Resources\PointsLogs\PointsLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePointsLogs extends ManageRecords
{
    protected static string $resource = PointsLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
