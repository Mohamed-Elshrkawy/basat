<?php

namespace App\Filament\Resources\SchoolResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PackagesRelationManager extends RelationManager
{
    protected static string $relationship = 'packages';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('Available packages');
    }

    public static function getModelLabel(): string
    {
        return __('Package');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Packages');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('name.ar')
                            ->label(__('Package name (Arabic)'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('name.en')
                            ->label(__('Package name (English)'))
                            ->required()
                            ->maxLength(255),
                    ]),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Textarea::make('description.ar')
                            ->label(__('Description (Arabic)'))
                            ->rows(3)
                            ->maxLength(500),

                        Forms\Components\Textarea::make('description.en')
                            ->label(__('Description (English)'))
                            ->rows(3)
                            ->maxLength(500),
                    ]),

                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label(__('Price'))
                            ->required()
                            ->numeric()
                            ->prefix(__('SAR'))
                            ->minValue(0)
                            ->step(0.01),

                        Forms\Components\TextInput::make('duration_days')
                            ->label(__('Duration'))
                            ->required()
                            ->numeric()
                            ->suffix(__(' day'))
                            ->minValue(1),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Active'))
                            ->default(true)
                            ->required(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Package name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('Description'))
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('price')
                    ->label(__('Price'))
                    ->money('SAR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_days')
                    ->label(__('Duration'))
                    ->numeric()
                    ->suffix(' ' . __('day'))
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Status'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Status'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Active'))
                    ->falseLabel(__('Inactive')),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label(__('Attach existing package'))
                    ->preloadRecordSelect(),

                Tables\Actions\CreateAction::make()
                    ->label(__('Create new package')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(__('Edit')),
                Tables\Actions\DetachAction::make()
                    ->label(__('Detach')),
                Tables\Actions\DeleteAction::make()
                    ->label(__('Delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label(__('Detach selected')),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label(__('Delete selected')),
                ]),
            ]);
    }
}
