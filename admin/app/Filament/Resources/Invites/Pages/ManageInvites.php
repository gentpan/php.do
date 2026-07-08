<?php

namespace App\Filament\Resources\Invites\Pages;

use App\Filament\Resources\Invites\InviteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Str;

class ManageInvites extends ManageRecords
{
    protected static string $resource = InviteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['code'] = $data['code'] ?? strtoupper(Str::random(10));
                    $data['created_by'] = auth()->id() ?? 0;
                    $data['created_at'] = now();
                    $data['used_by'] = 0;

                    return $data;
                }),
        ];
    }
}
