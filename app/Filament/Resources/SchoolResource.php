<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolResource\Pages;
use App\Filament\Resources\SchoolResource\RelationManagers;
use App\Models\School;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'المدارس';

    protected static ?string $modelLabel = 'مدرسة';

    protected static ?string $pluralModelLabel = 'المدارس';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationGroup = 'إدارة المواقع';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المدرسة')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name.ar')
                                    ->label('اسم المدينة (عربي)')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('name.en')
                                    ->label('اسم المدينة (English)')
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        // حقول مخفية للإحداثيات
                        Forms\Components\Hidden::make('lat')
                            ->default(24.7136),

                        Forms\Components\Hidden::make('lng')
                            ->default(46.6753),
                    ]),

                Forms\Components\Section::make('تحديد الموقع على الخريطة')
                    ->description('انقر على الخريطة لتحديد موقع المدرسة')
                    ->schema([
                        Forms\Components\View::make('filament.forms.components.map-picker')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('الباقات المتاحة')
                    ->schema([
                        Forms\Components\Select::make('packages')
                            ->label('الباقات')
                            ->relationship('packages', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المدرسة')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),


                Tables\Columns\TextColumn::make('packages_count')
                    ->label('عدد الباقات')
                    ->counts('packages')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),

                    // إضافة باقة سريعة
                    Tables\Actions\Action::make('add_package')
                        ->label('إضافة باقة')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('package_ids')
                                ->label('الباقات')
                                ->multiple()
                                ->relationship('packages', 'name')
                                ->preload()
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $record->packages()->syncWithoutDetaching($data['package_ids']);

                            Notification::make()
                                ->title('تمت إضافة الباقات بنجاح')
                                ->success()
                                ->send();
                        }),

                    // عرض الموقع على الخريطة
                    Tables\Actions\Action::make('view_location')
                        ->label('عرض الموقع')
                        ->icon('heroicon-o-map-pin')
                        ->color('info')
                        ->url(fn ($record) => "https://www.google.com/maps?q={$record->lat},{$record->lng}")
                        ->openUrlInNewTab(),

                    Tables\Actions\DeleteAction::make(),
                ])
                    ->label('إجراءات')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('primary')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                // زر عرض خريطة المدارس
                Tables\Actions\Action::make('view_schools_map')
                    ->label('عرض خريطة المدارس')
                    ->icon('heroicon-o-map')
                    ->color('success')
                    ->url(fn () => SchoolResource::getUrl('map'))
                    ->button(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PackagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchools::route('/'),
            'create' => Pages\CreateSchool::route('/create'),
            'edit' => Pages\EditSchool::route('/{record}/edit'),
            'map' => Pages\SchoolsMap::route('/map'),
        ];
    }
}
