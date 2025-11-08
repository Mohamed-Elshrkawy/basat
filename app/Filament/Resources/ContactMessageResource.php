<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactMessageResource\Pages;
use App\Models\ContactMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ContactMessageResource extends Resource
{
    use Translatable;

    protected static ?string $model = ContactMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';


    protected static ?int $navigationSort = 13;

    public static function getNavigationLabel(): string
    {
        return __('Contact Messages');
    }

    public static function getModelLabel(): string
    {
        return __('Message');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Messages');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Platform Settings');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('reference_number')
                            ->label(__('Reference Number'))
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('name')
                            ->label(__('Sender Name'))
                            ->disabled(),

                        Forms\Components\TextInput::make('phone')
                            ->label(__('Phone Number'))
                            ->disabled(),

                        Forms\Components\Select::make('type')
                            ->label(__('Message Type'))
                            ->options([
                                'suggestion' => __('Suggestion'),
                                'complaint' => __('Complaint'),
                            ])
                            ->disabled(),

                        Forms\Components\TextInput::make('subject')
                            ->label(__('Message Subject'))
                            ->disabled(),

                        Forms\Components\Textarea::make('message')
                            ->label(__('Message Text'))
                            ->rows(5)
                            ->disabled(),

                        Forms\Components\Toggle::make('is_read')
                            ->label(__('Is Read'))
                            ->default(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('ID'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference_number')
                    ->label(__('Reference Number'))
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label(__('Phone'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'suggestion' => __('Suggestion'),
                        'complaint' => __('Complaint'),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'suggestion' => 'success',
                        'complaint' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('subject')
                    ->label(__('Subject'))
                    ->searchable()
                    ->limit(30),

                Tables\Columns\IconColumn::make('is_read')
                    ->label(__('Status'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('Message Type'))
                    ->options([
                        'suggestion' => __('Suggestion'),
                        'complaint' => __('Complaint'),
                    ]),

                Tables\Filters\TernaryFilter::make('is_read')
                    ->label(__('Status'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Read'))
                    ->falseLabel(__('Unread')),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label(__('From Date')),
                        Forms\Components\DatePicker::make('created_until')
                            ->label(__('To Date')),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(__('Message Details'))
                    ->after(function (ContactMessage $record) {
                        if (!$record->is_read) {
                            $record->update([
                                'is_read' => true,
                                'read_at' => now(),
                            ]);
                        }
                    }),

                Tables\Actions\Action::make('toggle_read')
                    ->label(fn (ContactMessage $record) => $record->is_read ? __('Mark As Unread') : __('Mark As Read'))
                    ->icon(fn (ContactMessage $record) => $record->is_read ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (ContactMessage $record) => $record->is_read ? 'danger' : 'success')
                    ->action(function (ContactMessage $record) {
                        $record->update([
                            'is_read' => !$record->is_read,
                            'read_at' => !$record->is_read ? now() : null,
                        ]);
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_as_read')
                        ->label(__('Mark As Read'))
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($records) {
                            $records->each->update([
                                'is_read' => true,
                                'read_at' => now(),
                            ]);
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactMessages::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

//    public static function canViewAny(): bool
//    {
//        return auth()->user()->can('view_contact_messages');
//    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

//    public static function canDelete(Model $record): bool
//    {
//        return auth()->user()->can('delete_contact_messages');
//    }
}
