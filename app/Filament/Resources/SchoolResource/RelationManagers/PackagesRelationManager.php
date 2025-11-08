<?php

namespace App\Filament\Resources\SchoolResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PackagesRelationManager extends RelationManager
{
    protected static string $relationship = 'packages';

    protected static ?string $title = 'الباقات المتاحة';

    protected static ?string $modelLabel = 'باقة';

    protected static ?string $pluralModelLabel = 'الباقات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('name.ar')
                            ->label('اسم الباقة (عربي)')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('name.en')
                            ->label('اسم الباقة (English)')
                            ->required()
                            ->maxLength(255),
                    ]),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Textarea::make('description.ar')
                            ->label('الوصف (عربي)')
                            ->rows(3)
                            ->maxLength(500),

                        Forms\Components\Textarea::make('description.en')
                            ->label('الوصف (English)')
                            ->rows(3)
                            ->maxLength(500),
                    ]),

                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('السعر')
                            ->required()
                            ->numeric()
                            ->prefix('SAR')
                            ->minValue(0)
                            ->step(0.01),

                        Forms\Components\TextInput::make('duration_days')
                            ->label('المدة')
                            ->required()
                            ->numeric()
                            ->suffix('يوم')
                            ->minValue(1),

                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true)
                            ->required(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name.ar')
                    ->label('اسم الباقة')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description.ar')
                    ->label('الوصف')
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('السعر')
                    ->money('SAR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_days')
                    ->label('المدة')
                    ->numeric()
                    ->suffix(' يوم')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('ربط باقة موجودة')
                    ->preloadRecordSelect(),
                Tables\Actions\CreateAction::make()
                    ->label('إنشاء باقة جديدة'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make()
                    ->label('فك الربط'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('فك ربط المحدد'),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
