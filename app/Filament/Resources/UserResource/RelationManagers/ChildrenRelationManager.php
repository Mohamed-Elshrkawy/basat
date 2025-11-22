<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('Children');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Child Information'))
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('profile_image')
                            ->label(__('Profile Image'))
                            ->collection('profile_image')
                            ->image()
                            ->imageEditor()
                            ->circleCropper()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('300')
                            ->imageResizeTargetHeight('300')
                            ->maxSize(5120)
                            ->helperText(__('Max size: 5MB'))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\Select::make('gender')
                            ->label(__('Gender'))
                            ->options([
                                'male' => __('Male'),
                                'female' => __('Female'),
                            ])
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('phone')
                            ->label(__('Phone Number'))
                            ->tel()
                            ->maxLength(20)
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('birth_date')
                            ->label(__('Birth Date'))
                            ->maxDate(now())
                            ->displayFormat('Y-m-d')
                            ->native(false)
                            ->columnSpan(1),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('profile_image')
                    ->label(__('Image'))
                    ->collection('profile_image')
                    ->circular()
                    ->size(50),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('gender')
                    ->label(__('Gender'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __($state === 'male' ? 'Male' : 'Female'))
                    ->color(fn (string $state): string => match ($state) {
                        'male' => 'info',
                        'female' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('phone')
                    ->label(__('Phone'))
                    ->icon('heroicon-o-phone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('birth_date')
                    ->label(__('Birth Date'))
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('age')
                    ->label(__('Age'))
                    ->badge()
                    ->suffix(' ' . __('years'))
                    ->color('success'),

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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('Add Child'))
                    ->icon('heroicon-o-plus'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('No children yet'))
            ->emptyStateDescription(__('Add the first child using the button above'))
            ->emptyStateIcon('heroicon-o-user-group');
    }
}
