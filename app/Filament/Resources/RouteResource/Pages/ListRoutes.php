<?php

namespace App\Filament\Resources\RouteResource\Pages;

use App\Filament\Resources\RouteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRoutes extends ListRecords
{
    protected static string $resource = RouteResource::class;

    // ✅ عنوان الصفحة مترجم
    public  function getTitle(): string
    {
        return __('Routes');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('➕ Add new route')),
        ];
    }
}
