<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountDeletionRequestResource\Pages;
use App\Models\AccountDeletionRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class AccountDeletionRequestResource extends Resource
{
    use Translatable;

    protected static ?string $model = AccountDeletionRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-trash';
    protected static ?int $navigationSort = 7;

    // 🔹 ترجمة العناوين والمجموعات
    public static function getNavigationLabel(): string
    {
        return __('Account Deletion Requests');
    }

    public static function getModelLabel(): string
    {
        return __('Account Deletion Request');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Account Deletion Requests');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('User Management');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('Request Information'))
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label(__('User'))
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn($operation) => $operation === 'edit'),

                    Forms\Components\Textarea::make('reason')
                        ->label(__('Deletion Reason'))
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Select::make('status')
                        ->label(__('Status'))
                        ->options([
                            'pending' => __('Pending'),
                            'approved' => __('Approved'),
                            'rejected' => __('Rejected'),
                        ])
                        ->default('pending')
                        ->required()
                        ->disabled(fn($operation) => $operation !== 'edit'),

                    Forms\Components\Textarea::make('notes')
                        ->label(__('Admin Notes'))
                        ->rows(3)
                        ->columnSpanFull()
                        ->visible(fn($operation) => $operation === 'edit'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label(__('ID'))->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label(__('User'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('user.email')->label(__('Email'))->searchable()->copyable(),
                Tables\Columns\TextColumn::make('user.phone')->label(__('Phone'))->searchable()->copyable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label(__('Reason'))
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->reason),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'pending' => __('Pending'),
                        'approved' => __('Approved'),
                        'rejected' => __('Rejected'),
                    })
                    ->color(fn($state) => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label(__('Reviewed By'))
                    ->default(__('Not Reviewed')),
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label(__('Reviewed At'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Requested At'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'pending' => __('Pending'),
                        'approved' => __('Approved'),
                        'rejected' => __('Rejected'),
                    ]),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label(__('User'))
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label(__('From Date')),
                        Forms\Components\DatePicker::make('created_until')->label(__('To Date')),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('approve')
                    ->label(__('Approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(AccountDeletionRequest $record) => $record->isPending())
                    ->requiresConfirmation()
                    ->modalHeading(__('Approve Account Deletion Request'))
                    ->modalDescription(fn(AccountDeletionRequest $record) =>
                    __('Are you sure you want to approve the deletion request for :name?', ['name' => $record->user->name])
                    )
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label(__('Notes (Optional)'))
                            ->rows(3),
                    ])
                    ->action(function (AccountDeletionRequest $record, array $data) {
                        $record->approve($data['notes'] ?? null);
                        $record->user->update(['is_active' => false]);

                        Notification::make()
                            ->title(__('Request Approved'))
                            ->body(__('Account deletion request for :name has been approved.', ['name' => $record->user->name]))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label(__('Reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(AccountDeletionRequest $record) => $record->isPending())
                    ->requiresConfirmation()
                    ->modalHeading(__('Reject Account Deletion Request'))
                    ->modalDescription(fn(AccountDeletionRequest $record) =>
                    __('Are you sure you want to reject the deletion request for :name?', ['name' => $record->user->name])
                    )
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label(__('Rejection Reason'))
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (AccountDeletionRequest $record, array $data) {
                        $record->reject($data['notes']);

                        Notification::make()
                            ->title(__('Request Rejected'))
                            ->body(__('Account deletion request for :name has been rejected.', ['name' => $record->user->name]))
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\Action::make('delete_account')
                    ->label(__('Permanently Delete Account'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn(AccountDeletionRequest $record) => $record->isApproved())
                    ->requiresConfirmation()
                    ->modalHeading(__('Permanently Delete Account'))
                    ->modalDescription(fn(AccountDeletionRequest $record) =>
                    __('⚠️ Warning: This will permanently delete the account for :name and all related data cannot be restored!', ['name' => $record->user->name])
                    )
                    ->action(function (AccountDeletionRequest $record) {
                        $userName = $record->user->name;
                        $record->user->delete();

                        Notification::make()
                            ->title(__('Account Deleted'))
                            ->body(__('Account for :name has been permanently deleted.', ['name' => $userName]))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label(__('Approve Selected'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $approved = 0;
                            foreach ($records as $record) {
                                if ($record->isPending()) {
                                    $record->approve();
                                    $record->user->update(['is_active' => false]);
                                    $approved++;
                                }
                            }
                            Notification::make()
                                ->title(__('Approved :count Requests', ['count' => $approved]))
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make()->label(__('Delete Selected')),
                ]),
            ]);
    }

    // 🔹 صفحة العرض (Infolist)
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make(__('User Information'))
                ->schema([
                    Infolists\Components\TextEntry::make('user.name')->label(__('Name')),
                    Infolists\Components\TextEntry::make('user.email')->label(__('Email'))->copyable(),
                    Infolists\Components\TextEntry::make('user.phone')->label(__('Phone'))->copyable(),
                    Infolists\Components\TextEntry::make('user.user_type')
                        ->label(__('User Type'))
                        ->badge()
                        ->formatStateUsing(fn($state) => match ($state) {
                            'admin' => __('Admin'),
                            'customer' => __('Customer'),
                            default => $state,
                        }),
                    Infolists\Components\TextEntry::make('user.is_active')
                        ->label(__('Account Status'))
                        ->badge()
                        ->formatStateUsing(fn($state) => $state ? __('Active') : __('Inactive'))
                        ->color(fn($state) => $state ? 'success' : 'danger'),
                    Infolists\Components\TextEntry::make('user.created_at')
                        ->label(__('Registered At'))
                        ->dateTime('d/m/Y H:i'),
                ])->columns(3),

            Infolists\Components\Section::make(__('Request Details'))
                ->schema([
                    Infolists\Components\TextEntry::make('reason')
                        ->label(__('Deletion Reason'))
                        ->default(__('No reason provided'))
                        ->columnSpanFull(),
                    Infolists\Components\TextEntry::make('status')
                        ->label(__('Status'))
                        ->badge()
                        ->formatStateUsing(fn($state) => match ($state) {
                            'pending' => __('Pending'),
                            'approved' => __('Approved'),
                            'rejected' => __('Rejected'),
                        })
                        ->color(fn($state) => match ($state) {
                            'pending' => 'warning',
                            'approved' => 'success',
                            'rejected' => 'danger',
                        }),
                    Infolists\Components\TextEntry::make('created_at')
                        ->label(__('Requested At'))
                        ->dateTime('d/m/Y H:i'),
                ])->columns(2),

            Infolists\Components\Section::make(__('Review Information'))
                ->schema([
                    Infolists\Components\TextEntry::make('reviewer.name')
                        ->label(__('Reviewed By'))
                        ->default(__('Not Reviewed')),
                    Infolists\Components\TextEntry::make('reviewed_at')
                        ->label(__('Reviewed At'))
                        ->dateTime('d/m/Y H:i')
                        ->default(__('Not Reviewed')),
                    Infolists\Components\TextEntry::make('notes')
                        ->label(__('Admin Notes'))
                        ->default(__('No Notes'))
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->visible(fn(AccountDeletionRequest $record) => !$record->isPending()),
        ]);
    }

    // 🔹 الصفحات
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountDeletionRequests::route('/'),
            'create' => Pages\CreateAccountDeletionRequest::route('/create'),
            'view' => Pages\ViewAccountDeletionRequest::route('/{record}'),
            'edit' => Pages\EditAccountDeletionRequest::route('/{record}/edit'),
        ];
    }

    // 🔹 شارة الإشعارات في القائمة
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::pending()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_account_deletion_requests');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_account_deletion_requests');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('edit_account_deletion_requests');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete_account_deletion_requests');
    }
}
