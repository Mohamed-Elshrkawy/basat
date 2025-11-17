<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class AdminResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';


    protected static ?int $navigationSort = 15;

    public static function getNavigationLabel(): string
    {
        return __('Admins');
    }

    public static function getModelLabel(): string
    {
        return __('Admin');
    }

    public static function getPluralModelLabel(): string
    {
        return  __('Admins');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('System Management');
    }

    // ✅ فلترة البيانات لعرض الـ Admins فقط
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_type', 'admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_type')
                    ->default('admin')
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
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),

                        Forms\Components\DateTimePicker::make('mobile_verified_at')
                            ->label(__('Mobile Verified At'))
                            ->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make(__('Roles & Permissions'))
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label(__('Role'))
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('national_id')
                    ->label(__('National ID'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label(__('Phone'))
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('gender')
                    ->label(__('Gender'))
                    ->formatStateUsing(fn (string $state): string => $state === 'male' ? __('Male') : __('Female'))
                    ->colors([
                        'primary' => 'male',
                        'success' => 'female',
                    ]),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label(__('Role'))
                    ->badge(),

                Tables\Columns\IconColumn::make('mobile_verified_at')
                    ->label(__('Verified'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

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

                Tables\Filters\SelectFilter::make('roles')
                    ->label(__('Role'))
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),

                Tables\Filters\Filter::make('verified')
                    ->label(__('Only Verified'))
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
                        ->hidden(fn ($record) => $record->mobile_verified_at !== null)
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update(['mobile_verified_at' => now()]);

                            Notification::make()
                                ->title(__('Mobile verified successfully'))
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('unverify_mobile')
                        ->label(__('Unverify Mobile'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->hidden(fn ($record) => $record->mobile_verified_at === null)
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update(['mobile_verified_at' => null]);

                            Notification::make()
                                ->title(__('Mobile unverified successfully'))
                                ->warning()
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
            'index' => Pages\ListAdmins::route('/'),
            'create' => Pages\CreateAdmin::route('/create'),
            'edit' => Pages\EditAdmin::route('/{record}/edit'),
        ];
    }
}
