<?php

namespace App\Filament\Resources\StaticPageResource\Pages;

use App\Filament\Resources\StaticPageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewStaticPage extends ViewRecord
{
    protected static string $resource = StaticPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([

                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\ImageEntry::make('image')
                            ->label(__('Page Image'))
                            ->defaultImageUrl(asset('images/placeholder-static-page.png'))
                            ->size(300)
                            ->extraAttributes([
                                'class' => 'rounded-xl shadow-md block mx-auto max-w-full',
                            ]),
                    ])
                    ->extraAttributes([
                        'class' => 'flex justify-center items-center',
                    ])
                    ->columns(1),

                Infolists\Components\Section::make(__('Title'))
                    ->label(__('Title'))
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('title.ar')
                                    ->label(__('Title (Arabic)'))
                                    ->getStateUsing(fn ($record) => $record->getTranslation('title', 'ar'))
                                    ->columnSpan(1),

                                Infolists\Components\TextEntry::make('title.en')
                                    ->label(__('Title (English)'))
                                    ->getStateUsing(fn ($record) => $record->getTranslation('title', 'en'))
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Infolists\Components\Section::make(__('Content'))
                    ->label(__('Content'))
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('content.ar')
                                    ->label(__('Content (Arabic)'))
                                    ->getStateUsing(fn ($record) => $record->getTranslation('content', 'ar'))
                                    ->html()
                                    ->columnSpan(1),

                                Infolists\Components\TextEntry::make('content.en')
                                    ->label(__('Content (English)'))
                                    ->getStateUsing(fn ($record) => $record->getTranslation('content', 'en'))
                                    ->html()
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Infolists\Components\Section::make(__('Timestamps'))
                    ->label(__('Timestamps'))
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('Created At'))
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label(__('Last Updated'))
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

}
