<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_type', 'customer');
    }

    public static function getNavigationLabel(): string
    {
        return __('Customers');
    }

    public static function getModelLabel(): string
    {
        return __('Customer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Customers');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('User Management');
    }

    public static function form(Form $form): Form
    {
        return $form
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

                Forms\Components\Hidden::make('user_type')
                    ->default('customer')
                    ->dehydrated(),

                Forms\Components\Section::make(__('Basic Information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('national_id')
                            ->label(__('National ID'))
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
                            ->label(__('Phone Number'))
                            ->tel()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(15),

                        Forms\Components\TextInput::make('password')
                            ->label(__('Password'))
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Verify Mobile'))
                            ->inline(false)
                            ->default(false)
                            ->nullable(),
                    ])->columns(2),

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
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('national_id')
                    ->label(__('National ID'))
                    ->searchable()
                    ->copyable()
                    ->copyMessage(__('Copied')),

                Tables\Columns\TextColumn::make('phone')
                    ->label(__('Phone Number'))
                    ->searchable()
                    ->copyable()
                    ->copyMessage(__('Copied')),

                Tables\Columns\BadgeColumn::make('gender')
                    ->label(__('Gender'))
                    ->formatStateUsing(fn (string $state): string => $state === 'male' ? __('Male') : __('Female'))
                    ->colors([
                        'primary' => 'male',
                        'success' => 'female',
                    ]),

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

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gender')
                    ->label(__('Gender'))
                    ->options([
                        'male' => __('Male'),
                        'female' => __('Female'),
                    ]),

                Tables\Filters\Filter::make('verified')
                    ->label(__('Verified Only'))
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
