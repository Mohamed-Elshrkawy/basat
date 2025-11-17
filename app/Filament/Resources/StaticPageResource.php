<?php

namespace App\Filament\Resources;

use App\Enums\StaticPageType;
use App\Filament\Resources\StaticPageResource\Pages;
use App\Models\StaticPage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class StaticPageResource extends Resource
{
    protected static ?string $model = StaticPage::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 19;

    public static function getNavigationGroup(): ?string
    {
        return __('Platform Settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('Static Pages');
    }

    public static function getPluralLabel(): string
    {
        return __('Static Pages');
    }

    public static function getLabel(): string
    {
        return __('Static Page');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Section::make(__('English Content'))
                            ->schema([
                                Forms\Components\TextInput::make('title.en')
                                    ->label(__('Title (English)'))
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\RichEditor::make('content.en')
                                    ->label(__('Content (English)'))
                                    ->required()
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'underline',
                                        'strike',
                                        'link',
                                        'bulletList',
                                        'orderedList',
                                        'h2',
                                        'h3',
                                        'blockquote',
                                        'codeBlock',
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->columnSpan(1),

                        Forms\Components\Section::make(__('Arabic Content'))
                            ->schema([
                                Forms\Components\TextInput::make('title.ar')
                                    ->label(__('Title (Arabic)'))
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\RichEditor::make('content.ar')
                                    ->label(__('Content (Arabic)'))
                                    ->required()
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'underline',
                                        'strike',
                                        'link',
                                        'bulletList',
                                        'orderedList',
                                        'h2',
                                        'h3',
                                        'blockquote',
                                        'codeBlock',
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->columnSpan(1),
                    ]),

                Forms\Components\Section::make(__('Image'))
                    ->description(__('Upload the image'))
                    ->icon('heroicon-o-photo')
                    ->iconColor('warning')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('image')
                            ->label(__('Page Image'))
                            ->collection('image')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120) // 5MB
                            ->helperText(__('Recommended size: 1920x500 pixels | Max size: 5MB'))
                            ->downloadable()
                            ->openable()
                            ->previewable(),
                    ])
                    ->collapsible()
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Last Updated'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('key')
                    ->label(__('Page Type'))
                    ->options(collect(StaticPageType::cases())->mapWithKeys(function ($case) {
                        return [$case->value => $case->getLabel()];
                    })),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label(__('View')),
                Tables\Actions\EditAction::make()->label(__('Edit')),
                // ✅ تم إزالة DeleteAction
            ])
            ->bulkActions([
                // ✅ تم إزالة DeleteBulkAction
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStaticPages::route('/'),
            // ✅ تم إزالة Create Page
            'edit'   => Pages\EditStaticPage::route('/{record}/edit'),
            'view'   => Pages\ViewStaticPage::route('/{record}'),
        ];
    }

    // ✅ منع إنشاء صفحات جديدة
    public static function canCreate(): bool
    {
        return false;
    }

    // ✅ منع حذف الصفحات
    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
