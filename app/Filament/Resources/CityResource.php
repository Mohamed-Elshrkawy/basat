<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CityResource\Pages;
use App\Models\City;
use App\Services\RouteCreationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class CityResource extends Resource
{
    protected static ?string $model = City::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'المدن';

    protected static ?string $modelLabel = 'مدينة';

    protected static ?string $pluralModelLabel = 'المدن';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationGroup = 'إدارة المواقع';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المدينة')
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

                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true)
                            ->required(),

                        // حقول مخفية للإحداثيات
                        Forms\Components\Hidden::make('lat')
                            ->default(24.7136),

                        Forms\Components\Hidden::make('lng')
                            ->default(46.6753),
                    ]),

                Forms\Components\Section::make('تحديد الموقع على الخريطة')
                    ->description('انقر على الخريطة لتحديد موقع المدينة')
                    ->schema([
                        Forms\Components\View::make('filament.forms.components.map-picker')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المدينة')
                    ->getStateUsing(fn ($record) => $record->getTranslation('name', 'ar'))
                    ->searchable(query: function ($query, $search) {
                        $query->where('name->ar', 'like', "%{$search}%");
                    })
                    ->sortable(),


                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('routes_count')
                    ->label('عدد المسارات')
                    ->getStateUsing(fn ($record) => $record->getAllRelatedRoutes()->count())
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط'),
            ])
            ->actions([
                Tables\Actions\Action::make('edit_on_map')
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil')
                    ->color('primary')
                    ->url(fn () => CityResource::getUrl('map'))
                    ->openUrlInNewTab(),

                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                // زر عرض الخريطة الكاملة
                Tables\Actions\Action::make('view_cities_map')
                    ->label('عرض خريطة المدن')
                    ->icon('heroicon-o-map')
                    ->color('success')
                    ->url(fn () => CityResource::getUrl('map'))
                    ->button(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCities::route('/'),
            'create' => Pages\CreateCity::route("/create"),
            'map' => Pages\CitiesMap::route('/map'),
        ];
    }
}
