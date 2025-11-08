<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StopResource\Pages;
use App\Models\Stop;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StopResource extends Resource
{
    protected static ?string $model = Stop::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';


    protected static ?int $navigationSort = 11;


    public static function getNavigationLabel(): string
    {
        return __('Stops');
    }

    public static function getModelLabel(): string
    {
        return __('Stop');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Stops');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Locations Management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Stop Information'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name.ar')
                                    ->label(__('Stop Name (Arabic)'))
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('name.en')
                                    ->label(__('Stop Name (English)'))
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Active'))
                            ->default(true)
                            ->required(),

                        Forms\Components\Hidden::make('lat')
                            ->default(24.7136),

                        Forms\Components\Hidden::make('lng')
                            ->default(46.6753),
                    ]),

                Forms\Components\Section::make(__('Select Location On Map'))
                    ->description(__('Click on the map to select the stop location'))
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
                    ->label(__('Stop Name'))
                    ->getStateUsing(fn ($record) => $record->getTranslation('name', 'ar'))
                    ->searchable(query: function ($query, $search) {
                        $query->where('name->ar', 'like', "%{$search}%");
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Status'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Status'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Active'))
                    ->falseLabel(__('Inactive')),
            ])
            ->actions([
                Tables\Actions\Action::make('edit_on_map')
                    ->label(__('Edit'))
                    ->icon('heroicon-o-pencil')
                    ->color('primary')
                    ->url(fn () => StopResource::getUrl('map'))
                    ->openUrlInNewTab(),

                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('view_stops_map')
                    ->label(__('View Stops Map'))
                    ->icon('heroicon-o-map')
                    ->color('success')
                    ->url(fn () => StopResource::getUrl('map'))
                    ->button(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStops::route('/'),
            'create' => Pages\CreateStop::route('/create'),
            'map' => Pages\StopsMap::route('/map'),
        ];
    }
}
