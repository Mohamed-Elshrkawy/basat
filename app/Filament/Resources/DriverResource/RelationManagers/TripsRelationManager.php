<?php

namespace App\Filament\Resources\DriverResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TripsRelationManager extends RelationManager
{
    protected static string $relationship = 'trips';


    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('Trips');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Trips');
    }

    public static function getModelLabel(): string
    {
        return __('Trip');
    }

    protected static ?string $icon = 'heroicon-o-map';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('Trip Number'))
                    ->prefix('#')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('pickup_location')
                    ->label(__('Pickup Location'))
                    ->limit(30)
                    ->searchable()
                    ->icon('heroicon-m-map-pin')
                    ->tooltip(fn ($record) => $record->pickup_location),

                Tables\Columns\TextColumn::make('dropoff_location')
                    ->label(__('Dropoff Location'))
                    ->limit(30)
                    ->searchable()
                    ->icon('heroicon-m-flag')
                    ->tooltip(fn ($record) => $record->dropoff_location),

                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => __('⏳ Pending'),
                        'accepted' => __('✅ Accepted'),
                        'in_progress' => __('🚌 In Progress'),
                        'completed' => __('✔️ Completed'),
                        'cancelled' => __('❌ Cancelled'),
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'accepted',
                        'primary' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label(__('Customer'))
                    ->searchable()
                    ->sortable()
                    ->default(__('Not Specified')),

                Tables\Columns\TextColumn::make('passengers_count')
                    ->label(__('Passengers Count'))
                    ->suffix(__(' Passenger'))
                    ->badge()
                    ->color('info')
                    ->default(0),

                Tables\Columns\TextColumn::make('total_price')
                    ->label(__('Total Price'))
                    ->money('SAR')
                    ->sortable()
                    ->default('0.00'),

                Tables\Columns\TextColumn::make('pickup_time')
                    ->label(__('Pickup Time'))
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->icon('heroicon-m-clock'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'pending' => __('⏳ Pending'),
                        'accepted' => __('✅ Accepted'),
                        'in_progress' => __('🚌 In Progress'),
                        'completed' => __('✔️ Completed'),
                        'cancelled' => __('❌ Cancelled'),
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('pickup_time')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('From Date')),
                        Forms\Components\DatePicker::make('until')
                            ->label(__('To Date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('pickup_time', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('pickup_time', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                // You can add actions here if needed
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(__('View')),
            ])
            ->bulkActions([
                // You can add bulk actions here if needed
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('No Trips Found'))
            ->emptyStateDescription(__('This driver has not completed any trips yet.'))
            ->emptyStateIcon('heroicon-o-map');
    }
}
