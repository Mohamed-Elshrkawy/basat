<?php

namespace App\Filament\Resources\BrandResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class VehicleModelsRelationManager extends RelationManager
{
    protected static string $relationship = 'vehicleModels';

    protected static ?string $icon = 'heroicon-o-rectangle-stack';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('Models');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Models');
    }

    public static function getModelLabel(): string
    {
        return __('Model');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Model info'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Model name'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('Example models')),

                        Forms\Components\TextInput::make('default_seat_count')
                            ->label(__('Default seats'))
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(100)
                            ->default(50)
                            ->suffix(__('Seat'))
                            ->helperText(__('Seat hint')),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Model active'))
                            ->default(true)
                            ->inline(false)
                            ->helperText(__('Inactive hint')),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Model name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                Tables\Columns\TextColumn::make('default_seat_count')
                    ->label(__('Default seats'))
                    ->sortable()
                    ->suffix(__(' Seat'))
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('vehicles_count')
                    ->label(__('Vehicles count'))
                    ->counts('vehicles')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Status'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Status'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Active only'))
                    ->falseLabel(__('Inactive only')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('Add model'))
                    ->icon('heroicon-o-plus')
                    ->modalHeading(__('Add new model'))
                    ->successNotificationTitle(__('Model added successfully'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['brand_id'] = $this->ownerRecord->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(__('Edit'))
                    ->successNotificationTitle(__('Model updated successfully')),

                Tables\Actions\Action::make('toggle_status')
                    ->label(fn ($record) => $record->is_active ? __('Deactivate') : __('Activate'))
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['is_active' => !$record->is_active]);

                        Notification::make()
                            ->title($record->is_active ? __('Model activated') : __('Model deactivated'))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label(__('Delete'))
                    ->requiresConfirmation()
                    ->successNotificationTitle(__('Model deleted successfully')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('activate')
                        ->label(__('Activate selected'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);

                            Notification::make()
                                ->title(__('Selected models activated'))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label(__('Deactivate selected'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);

                            Notification::make()
                                ->title(__('Selected models deactivated'))
                                ->warning()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('Add model'))
                    ->icon('heroicon-o-plus')
                    ->modalHeading(__('Add model for brand'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['brand_id'] = $this->ownerRecord->id;
                        return $data;
                    }),
            ])
            ->emptyStateHeading(__('No models'))
            ->emptyStateDescription(__('No models description'))
            ->emptyStateIcon('heroicon-o-rectangle-stack');
    }
}
