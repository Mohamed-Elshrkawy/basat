<?php

namespace App\Filament\Resources\SchoolResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DriversRelationManager extends RelationManager
{
    protected static string $relationship = 'drivers';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('School Drivers');
    }

    public static function getModelLabel(): string
    {
        return __('Driver');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Drivers');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Driver Name'))
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label(__('Avatar'))
                    ->circular()
                    ->size(50),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('Driver Name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('phone')
                    ->label(__('Phone'))
                    ->icon('heroicon-o-phone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('national_id')
                    ->label(__('National ID'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('vehicle.plate_number')
                    ->label(__('Vehicle'))
                    ->searchable()
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
                    ->label(__('Attach Driver'))
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name', 'phone', 'national_id'])
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                    ]),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label(__('Detach')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label(__('Detach selected')),
                ]),
            ])
            ->emptyStateHeading(__('No drivers assigned yet'))
            ->emptyStateDescription(__('Attach drivers to this school using the button above'))
            ->emptyStateIcon('heroicon-o-user-group');
    }
}
