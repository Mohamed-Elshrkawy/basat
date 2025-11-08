<?php
//
//namespace App\Filament\Resources;
//
//use App\Filament\Resources\SchoolPackageResource\Pages;
//use App\Models\SchoolPackage;
//use Filament\Forms;
//use Filament\Forms\Form;
//use Filament\Resources\Resource;
//use Filament\Tables;
//use Filament\Tables\Table;
//
//class SchoolPackageResource extends Resource
//{
//    protected static ?string $model = SchoolPackage::class;
//
//    protected static ?string $navigationIcon = 'heroicon-o-cube';
//
//    protected static ?string $navigationLabel = 'باقات المدارس';
//
//    protected static ?string $modelLabel = 'باقة';
//
//    protected static ?string $pluralModelLabel = 'باقات المدارس';
//
//    protected static ?int $navigationSort = 7;
//
//    protected static ?string $navigationGroup = 'إدارة المدارس';
//
//    public static function form(Form $form): Form
//    {
//        return $form
//            ->schema([
//                Forms\Components\Section::make('معلومات الباقة')
//                    ->schema([
//                        Forms\Components\Grid::make(2)
//                            ->schema([
//                                Forms\Components\TextInput::make('name.ar')
//                                    ->label('اسم الباقة (عربي)')
//                                    ->required()
//                                    ->maxLength(255),
//
//                                Forms\Components\TextInput::make('name.en')
//                                    ->label('اسم الباقة (English)')
//                                    ->required()
//                                    ->maxLength(255),
//                            ]),
//
//                        Forms\Components\Grid::make(2)
//                            ->schema([
//                                Forms\Components\Textarea::make('description.ar')
//                                    ->label('الوصف (عربي)')
//                                    ->rows(3)
//                                    ->maxLength(500),
//
//                                Forms\Components\Textarea::make('description.en')
//                                    ->label('الوصف (English)')
//                                    ->rows(3)
//                                    ->maxLength(500),
//                            ]),
//
//                        Forms\Components\Grid::make(3)
//                            ->schema([
//                                Forms\Components\TextInput::make('price')
//                                    ->label('السعر')
//                                    ->required()
//                                    ->numeric()
//                                    ->prefix('SAR')
//                                    ->minValue(0)
//                                    ->step(0.01),
//
//                                Forms\Components\TextInput::make('duration_days')
//                                    ->label('المدة')
//                                    ->required()
//                                    ->numeric()
//                                    ->suffix('يوم')
//                                    ->minValue(1),
//
//                                Forms\Components\Toggle::make('is_active')
//                                    ->label('نشط')
//                                    ->default(true)
//                                    ->required(),
//                            ]),
//                    ]),
//            ]);
//    }
//
//    public static function table(Table $table): Table
//    {
//        return $table
//            ->columns([
//                Tables\Columns\TextColumn::make('name.ar')
//                    ->label('اسم الباقة')
//                    ->searchable()
//                    ->sortable(),
//
//                Tables\Columns\TextColumn::make('description.ar')
//                    ->label('الوصف')
//                    ->limit(50)
//                    ->toggleable(),
//
//                Tables\Columns\TextColumn::make('price')
//                    ->label('السعر')
//                    ->money('SAR')
//                    ->sortable(),
//
//                Tables\Columns\TextColumn::make('duration_days')
//                    ->label('المدة')
//                    ->numeric()
//                    ->suffix(' يوم')
//                    ->sortable(),
//
//                Tables\Columns\IconColumn::make('is_active')
//                    ->label('الحالة')
//                    ->boolean()
//                    ->trueIcon('heroicon-o-check-circle')
//                    ->falseIcon('heroicon-o-x-circle')
//                    ->trueColor('success')
//                    ->falseColor('danger'),
//
//                Tables\Columns\TextColumn::make('schools_count')
//                    ->label('عدد المدارس')
//                    ->counts('schools')
//                    ->badge()
//                    ->color('info'),
//
//                Tables\Columns\TextColumn::make('created_at')
//                    ->label('تاريخ الإنشاء')
//                    ->dateTime()
//                    ->sortable()
//                    ->toggleable(isToggledHiddenByDefault: true),
//            ])
//            ->filters([
//                Tables\Filters\TernaryFilter::make('is_active')
//                    ->label('الحالة')
//                    ->placeholder('الكل')
//                    ->trueLabel('نشط')
//                    ->falseLabel('غير نشط'),
//            ])
//            ->actions([
//                Tables\Actions\ViewAction::make(),
//                Tables\Actions\EditAction::make(),
//                Tables\Actions\DeleteAction::make(),
//            ])
//            ->bulkActions([
//                Tables\Actions\BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
//                ]),
//            ]);
//    }
//
//    public static function getRelations(): array
//    {
//        return [
//            //
//        ];
//    }
//
//    public static function getPages(): array
//    {
//        return [
//            'index' => Pages\ListSchoolPackages::route('/'),
//            'create' => Pages\CreateSchoolPackage::route('/create'),
//            'edit' => Pages\EditSchoolPackage::route('/{record}/edit'),
//        ];
//    }
//}
