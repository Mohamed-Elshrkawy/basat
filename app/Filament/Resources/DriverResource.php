<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DriverResource\Pages;
use App\Filament\Resources\DriverResource\RelationManagers;
use App\Models\User;
use App\Models\Brand;
use App\Models\VehicleModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class DriverResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('Drivers');
    }

    public static function getModelLabel(): string
    {
        return __('Driver');
    }

    public static function getPluralModelLabel(): string
    {
        return  __('Drivers');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('User Management');
    }

    // ✅ فلترة البيانات لعرض السائقين فقط
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_type', 'driver');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ✅ حقل مخفي لتحديد user_type تلقائياً
                Forms\Components\Hidden::make('user_type')
                    ->default('driver')
                    ->dehydrated(),

                Forms\Components\Tabs::make(__('Tabs'))
                    ->tabs([
                        // تبويب المعلومات الأساسية
                        Forms\Components\Tabs\Tab::make(__('Basic Information'))
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\SpatieMediaLibraryFileUpload::make('avatar_url')
                                            ->label(__('Avatar'))
                                            ->collection('avatar')
                                            ->image()
                                            ->imageEditor()
                                            ->circleCropper()
                                            ->imageResizeMode('cover')
                                            ->imageCropAspectRatio('1:1')
                                            ->imageResizeTargetWidth('300')
                                            ->imageResizeTargetHeight('300')
                                            ->maxSize(2048)
                                            ->helperText(__('Max size: 2MB, Recommended: 300x300px'))
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('name')
                                            ->label(__('Name'))
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('national_id')
                                            ->label(__('NationalId'))
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(10)
                                            ->minLength(10),

                                        Forms\Components\Select::make('gender')
                                            ->label(__('Gender'))
                                            ->options([
                                                'male' => __('Male'),
                                                'female' => __('Female'),
                                            ])
                                            ->required(),

                                        Forms\Components\TextInput::make('phone')
                                            ->label(__('Phone'))
                                            ->tel()
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->prefix('+966')
                                            ->maxLength(12),

                                        Forms\Components\TextInput::make('password')
                                            ->label(__('Password'))
                                            ->password()
                                            ->revealable()
                                            ->minLength(8)
                                            ->maxLength(16)
                                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                            ->dehydrated(fn ($state) => filled($state))
                                            ->required(fn (string $context): bool => $context === 'create'),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label(__('Verify Mobile'))
                                            ->inline(false)
                                            ->default(false)
                                            ->nullable(),
                                    ])->columns(2),
                            ]),

                        // تبويب معلومات السائق
                        Forms\Components\Tabs\Tab::make(__('Driver Information'))
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Textarea::make('driver.bio')
                                            ->label(__('DriverBio'))
                                            ->rows(3)
                                            ->maxLength(500)
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('driver.availability_status')
                                            ->label(__('AvailabilityStatus'))
                                            ->options([
                                                'available' => __('Available'),
                                                'on_trip' => __('OnTrip'),
                                                'unavailable' => __('Unavailable'),
                                            ])
                                            ->default('unavailable')
                                            ->required(),

                                        Forms\Components\TextInput::make('driver.avg_rating')
                                            ->label(__('Rating'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(5)
                                            ->step(0.1)
                                            ->suffix('⭐')
                                            ->default(0)
                                            ->disabled(),
                                    ])->columns(2),
                            ]),

                        // تبويب السيارة
                        Forms\Components\Tabs\Tab::make(__('Vehicle Information'))
                            ->icon('heroicon-o-truck')
                            ->schema([
                                Forms\Components\Section::make()
                                    ->schema([
                                        // ✅ استخدام options بدلاً من relationship
                                        Forms\Components\Select::make('vehicle.brand_id')
                                            ->label(__('Brand'))
                                            ->options(Brand::where('is_active', true)->pluck('name', 'id'))
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                $set('vehicle.vehicle_model_id', null);
                                            }),

                                        // ✅ استخدام options ديناميكية
                                        Forms\Components\Select::make('vehicle.vehicle_model_id')
                                            ->label(__('Model'))
                                            ->options(function (Forms\Get $get) {
                                                $brandId = $get('vehicle.brand_id');
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
                                                        $set('vehicle.seat_count', $model->default_seat_count);
                                                    }
                                                }
                                            })
                                            ->helperText(__('ChooseBrandFirst')),

                                        Forms\Components\TextInput::make('vehicle.plate_number')
                                            ->label(__('PlateNumber'))
                                            ->required()
                                            ->unique(
                                                table: 'vehicles',
                                                column: 'plate_number',
                                                ignorable: fn ($record) => $record?->vehicle
                                            )
                                            ->maxLength(255)
                                            ->placeholder('مثال: أ ب ج 1234'),

                                        Forms\Components\TextInput::make('vehicle.seat_count')
                                            ->label(__('SeatCount'))
                                            ->required()
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(100)
                                            ->default(50),

                                        Forms\Components\Select::make('vehicle.type')
                                            ->label(__('VehicleType'))
                                            ->options([
                                                'public_bus' => __('PublicBus'),
                                                'private_bus' => __('PrivateBus'),
                                                'school_bus' => __('SchoolBus'),
                                            ])
                                            ->required()
                                            ->default('public_bus'),



                                        Forms\Components\Toggle::make('vehicle.is_active')
                                            ->label(__('IsActive'))
                                            ->default(true)
                                            ->inline(false),
                                    ])->columns(2),

                                Forms\Components\Section::make('الوسائل المتاحة')
                                    ->schema([
                                        Forms\Components\Repeater::make('vehicle_amenities')
                                            ->label('')
                                            ->schema([
                                                Forms\Components\Select::make('amenity_id')
                                                    ->label('الوسيلة')
                                                    ->options(\App\Models\Amenity::pluck('name', 'id'))
                                                    ->required()
                                                    ->searchable()
                                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('price')
                                                    ->label('السعر')
                                                    ->required()
                                                    ->numeric()
                                                    ->prefix('SAR')
                                                    ->minValue(0)
                                                    ->step(0.01)
                                                    ->default(0)
                                                    ->columnSpan(1),
                                            ])
                                            ->columns(2)
                                            ->addActionLabel('إضافة وسيلة')
                                            ->reorderable(false)
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string =>
                                            isset($state['amenity_id']) ? \App\Models\Amenity::find($state['amenity_id'])?->name : null
                                            )
                                            ->defaultItems(0)
                                            ->columnSpanFull()
                                            ->hidden(fn (string $operation): bool => $operation === 'create'),
                                    ])
                                    ->description('يمكنك إضافة الوسائل بعد حفظ السائق والسيارة')
                                    ->collapsible()
                                    ->collapsed(),
                            ]), // ← نهاية Tab 3
                    ]) // ← نهاية Tabs
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label(__('Avatar'))
                    ->circular()
                    ->size(50),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('Full Name'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('phone')
                    ->label(__('Phone Number'))
                    ->searchable()
                    ->copyable(),

                Tables\Columns\BadgeColumn::make('driver.availability_status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn ($state): string => match($state) {
                        'available' => __('✅ Available'),
                        'on_trip' => __('🚌 On Trip'),
                        'unavailable' => __('❌ Unavailable'),
                        default => $state ?? __('Undefined'),
                    })
                    ->colors([
                        'success' => 'available',
                        'warning' => 'on_trip',
                        'danger' => 'unavailable',
                    ]),

                Tables\Columns\TextColumn::make('vehicle.plate_number')
                    ->label(__('Plate Number'))
                    ->searchable()
                    ->default(__('Not Found')),

                Tables\Columns\TextColumn::make('vehicle.type')
                    ->label(__('Vehicle Type'))
                    ->formatStateUsing(fn ($state) => match($state) {
                        'public_bus' => __('🚌 Public'),
                        'private_bus' => __('🚐 Private'),
                        'school_bus' => __('🚍 School'),
                        default => __('Undefined'),
                    })
                    ->badge(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Verified'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('wallet.balance')
                    ->label(__('Balance'))
                    ->money('SAR')
                    ->sortable()
                    ->default('0.00'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gender')
                    ->label(__('Gender'))
                    ->options([
                        'male' => __('Male'),
                        'female' => __('Female'),
                    ]),

                Tables\Filters\SelectFilter::make('vehicle.type')
                    ->label(__('Vehicle Type'))
                    ->relationship('vehicle', 'type')
                    ->options([
                        'public_bus' => __('🚌 Public Bus'),
                        'private_bus' => __('🚐 Private Bus'),
                        'school_bus' => __('🚍 School Bus'),
                    ]),

                Tables\Filters\Filter::make('verified')
                    ->label(__('Verified Only'))
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('mobile_verified_at')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),

                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('verify_mobile')
                        ->label(__('Verify Mobile'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->hidden(fn ($record) => $record->is_active)
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update(['is_active' => true]);

                            Notification::make()
                                ->title(__('Mobile verified successfully'))
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('unverify_mobile')
                        ->label(__('Unverify Mobile'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->hidden(fn ($record) => !$record->is_active)
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update(['is_active' => false]);

                            Notification::make()
                                ->title(__('Mobile unverified successfully'))
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\Action::make('add_balance')
                        ->label(__('Add Balance'))
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->label(__('Amount'))
                                ->required()
                                ->numeric()
                                ->prefix('SAR')
                                ->minValue(0.01)
                                ->step(0.01),

                            Forms\Components\Textarea::make('note')
                                ->label(__('Note'))
                                ->maxLength(255),
                        ])
                        ->action(function ($record, array $data) {

                            $record->deposit($data['amount'], ['en' => $data['note'], 'ar' => $data['note']]);

                            Notification::make()
                                ->title(__('Balance added successfully'))
                                ->body(__('Added :amount SAR', ['amount' => $data['amount']]))
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('deduct_balance')
                        ->label(__('Deduct Balance'))
                        ->icon('heroicon-o-minus-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->label(__('Amount'))
                                ->required()
                                ->numeric()
                                ->prefix('SAR')
                                ->minValue(0.01)
                                ->step(0.01),

                            Forms\Components\Textarea::make('note')
                                ->label(__('Note'))
                                ->maxLength(255),
                        ])
                        ->action(function ($record, array $data) {
                            if ($record->balance() < $data['amount']) {
                                Notification::make()
                                    ->title(__('Insufficient balance'))
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $record->withdraw($data['amount'], ['en' => $data['note'], 'ar' => $data['note']]);

                            Notification::make()
                                ->title(__('Balance deducted successfully'))
                                ->body(__('Deducted :amount SAR', ['amount' => $data['amount']]))
                                ->success()
                                ->send();
                        }),

                    // Reset password
                    Tables\Actions\Action::make('reset_password')
                        ->label(__('Reset Password'))
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->form([
                            Forms\Components\TextInput::make('new_password')
                                ->label(__('New Password'))
                                ->password()
                                ->required()
                                ->minLength(8)
                                ->maxLength(255),
                        ])
                        ->requiresConfirmation()
                        ->action(function ($record, array $data) {
                            $record->update([
                                'password' => Hash::make($data['new_password'])
                            ]);

                            Notification::make()
                                ->title(__('Password changed successfully'))
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make(),
                ])
                    ->label(__('Actions'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('primary')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }



    public static function getRelations(): array
    {
        return [
//            RelationManagers\VehicleRelationManager::class,
            RelationManagers\TripsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDrivers::route('/'),
            'create' => Pages\CreateDriver::route('/create'),
            'edit' => Pages\EditDriver::route('/{record}/edit'),
            'view' => Pages\ViewDriver::route('/{record}'),
        ];
    }
}
