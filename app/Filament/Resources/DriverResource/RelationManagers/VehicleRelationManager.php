<?php

namespace App\Filament\Resources\DriverResource\RelationManagers;

use App\Models\Brand;
use App\Models\VehicleModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class VehicleRelationManager extends RelationManager
{
    protected static string $relationship = 'vehicle';


    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('Vehicles');
    }


    public static function getPluralModelLabel(): string
    {
        return __('Vehicles');
    }

    public static function getModelLabel(): string
    {
        return __('Vehicle');
    }

    protected static ?string $icon = 'heroicon-o-truck';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Vehicle Information'))
                    ->schema([
                        Forms\Components\Select::make('brand_id')
                            ->label(__('Brand'))
                            ->options(Brand::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('vehicle_model_id', null);
                            })
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('Brand Name'))
                                    ->required()
                                    ->unique()
                                    ->maxLength(255),

                                Forms\Components\Toggle::make('is_active')
                                    ->label(__('Active'))
                                    ->default(true),
                            ]),

                        Forms\Components\Select::make('vehicle_model_id')
                            ->label(__('Model'))
                            ->options(function (Forms\Get $get) {
                                $brandId = $get('brand_id');
                                if (!$brandId) {
                                    return [];
                                }
                                return VehicleModel::where('brand_id', $brandId)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $model = VehicleModel::find($state);
                                    if ($model) {
                                        $set('seat_count', $model->default_seat_count);
                                    }
                                }
                            })
                            ->helperText(__('Select brand first')),

                        Forms\Components\TextInput::make('plate_number')
                            ->label(__('Plate Number'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder(__('Example: ABC 1234'))
                            ->helperText(__('The plate number must be unique')),

                        Forms\Components\TextInput::make('seat_count')
                            ->label(__('Seats Count'))
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->default(50)
                            ->suffix(__('Seat')),

                        Forms\Components\Select::make('type')
                            ->label(__('Vehicle Type'))
                            ->options([
                                'public_bus' => __('🚌 Public Bus'),
                                'private_bus' => __('🚐 Private Bus'),
                                'school_bus' => __('🚍 School Bus'),
                            ])
                            ->required()
                            ->default('public_bus')
                            ->native(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Vehicle Active'))
                            ->default(true)
                            ->inline(false)
                            ->helperText(__('Inactive vehicles will not appear to customers')),


                        Forms\Components\Section::make('الوسائل المتاحة')
                            ->schema([
                                Forms\Components\Repeater::make('amenities')
                                    ->label('')
                                    ->relationship('amenities')
                                    ->schema([
                                        Forms\Components\Select::make('amenity_id')
                                            ->label('الوسيلة')
                                            ->options(\App\Models\Amenity::pluck('name', 'id'))
                                            ->required()
                                            ->searchable()
                                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('price')
                                            ->label(__('Price'))
                                            ->required()
                                            ->numeric()
                                            ->prefix('SAR')
                                            ->minValue(0)
                                            ->step(0.01)
                                            ->default(0)
                                            ->columnSpan(1),
                                    ])
                                    ->columns(2)
                                    ->addActionLabel(__('Add Method'))
                                    ->reorderable(false)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string =>
                                        \App\Models\Amenity::find($state['amenity_id'])?->name ?? null
                                    )
                                    ->defaultItems(0)
                                    ->columnSpanFull(),
                            ])
                            ->collapsible()
                            ->collapsed(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('plate_number')
            ->columns([
                Tables\Columns\TextColumn::make('brand.name')
                    ->label(__('Brand'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('vehicleModel.name')
                    ->label(__('Model'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('plate_number')
                    ->label(__('Plate Number'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage(__('Plate number copied'))
                    ->weight('medium')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('seat_count')
                    ->label(__('Seats'))
                    ->sortable()
                    ->suffix(__(' Seat'))
                    ->badge()
                    ->color('info'),

                Tables\Columns\BadgeColumn::make('type')
                    ->label(__('Vehicle Type'))
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'public_bus' => __('🚌 Public Bus'),
                        'private_bus' => __('🚐 Private Bus'),
                        'school_bus' => __('🚍 School Bus'),
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'public_bus',
                        'success' => 'private_bus',
                        'warning' => 'school_bus',
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Status'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('Vehicle Type'))
                    ->options([
                        'public_bus' => __('🚌 Public Bus'),
                        'private_bus' => __('🚐 Private Bus'),
                        'school_bus' => __('🚍 School Bus'),
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Vehicle Status'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Active Only'))
                    ->falseLabel(__('Inactive Only')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('Add Vehicle'))
                    ->icon('heroicon-o-plus')
                    ->modalHeading(__('Add New Vehicle'))
                    ->modalDescription(__('Add a vehicle for the driver (only one vehicle per driver)'))
                    ->successNotificationTitle(__('Vehicle added successfully'))
                    ->hidden(fn (): bool => $this->ownerRecord->vehicle !== null)
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['driver_id'] = $this->ownerRecord->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(__('Edit'))
                    ->successNotificationTitle(__('Vehicle updated successfully')),

                Tables\Actions\Action::make('toggle_status')
                    ->label(fn ($record) => $record->is_active ? __('Deactivate Vehicle') : __('Activate Vehicle'))
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->is_active ? __('Deactivate Vehicle') : __('Activate Vehicle'))
                    ->modalDescription(fn ($record) => $record->is_active
                        ? __('Are you sure you want to deactivate this vehicle? It will not appear to customers.')
                        : __('Are you sure you want to activate this vehicle? It will appear to customers.'))
                    ->action(function ($record) {
                        $record->update(['is_active' => !$record->is_active]);

                        Notification::make()
                            ->title($record->is_active ? __('Vehicle activated successfully') : __('Vehicle deactivated successfully'))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label(__('Delete'))
                    ->requiresConfirmation()
                    ->modalHeading(__('Delete Vehicle'))
                    ->modalDescription(__('Are you sure you want to delete this vehicle? This action cannot be undone.'))
                    ->successNotificationTitle(__('Vehicle deleted successfully')),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('Add Vehicle'))
                    ->icon('heroicon-o-plus')
                    ->modalHeading(__('Add Vehicle for Driver'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['driver_id'] = $this->ownerRecord->id;
                        return $data;
                    }),
            ])
            ->emptyStateHeading(__('No Vehicle Found'))
            ->emptyStateDescription(__('No vehicle has been added for this driver yet.'))
            ->emptyStateIcon('heroicon-o-truck');
    }

    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }
}
