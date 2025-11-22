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


    protected static ?int $navigationSort = 6;


    public static function getNavigationLabel(): string
    {
        return __('Schools');
    }

    public static function getModelLabel(): string
    {
        return __('School');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Schools');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Locations Management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('School Information'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name.ar')
                                    ->label(__('School Name (Arabic)'))
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('name.en')
                                    ->label(__('School Name (English)'))
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Hidden::make('lat')
                            ->default(24.7136),

                        Forms\Components\Hidden::make('lng')
                            ->default(46.6753),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Active'))
                            ->default(true)
                            ->inline(false),
                    ]),

                Forms\Components\Section::make(__('Select Location On Map'))
                    ->description(__('Click on the map to select the school location'))
                    ->schema([
                        Forms\Components\View::make('filament.forms.components.map-picker')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make(__('Schedule & Working Days'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('departure_time')
                                    ->label(__('Departure Time'))
                                    ->seconds(false)
                                    ->displayFormat('H:i')
                                    ->helperText(__('Time students are picked up in the morning')),

                                Forms\Components\TimePicker::make('return_time')
                                    ->label(__('Return Time'))
                                    ->seconds(false)
                                    ->displayFormat('H:i')
                                    ->helperText(__('Time students are dropped off in the afternoon')),
                            ]),

                        Forms\Components\CheckboxList::make('working_days')
                            ->label(__('Working Days'))
                            ->options([
                                'sunday' => __('Sunday'),
                                'monday' => __('Monday'),
                                'tuesday' => __('Tuesday'),
                                'wednesday' => __('Wednesday'),
                                'thursday' => __('Thursday'),
                                'friday' => __('Friday'),
                                'saturday' => __('Saturday'),
                            ])
                            ->columns(3)
                            ->gridDirection('row')
                            ->helperText(__('Select the days the school operates')),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make(__('Available Packages'))
                    ->schema([
                        Forms\Components\Select::make('packages')
                            ->label(__('Packages'))
                            ->relationship('packages', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText(__('Select packages available for this school')),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('School Name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                Tables\Columns\TextColumn::make('departure_time')
                    ->label(__('Departure'))
                    ->time('H:i')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->sortable(),

                Tables\Columns\TextColumn::make('return_time')
                    ->label(__('Return'))
                    ->time('H:i')
                    ->icon('heroicon-o-arrow-down-circle')
                    ->sortable(),

                Tables\Columns\TextColumn::make('working_days')
                    ->label(__('Working Days'))
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('packages_count')
                    ->label(__('Packages'))
                    ->counts('packages')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('drivers_count')
                    ->label(__('Drivers'))
                    ->counts('drivers')
                    ->badge()
                    ->color('warning'),

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
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('add_package')
                        ->label(__('Add Package'))
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('package_ids')
                                ->label(__('Packages'))
                                ->multiple()
                                ->relationship('packages', 'name')
                                ->preload()
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $record->packages()->syncWithoutDetaching($data['package_ids']);

                            Notification::make()
                                ->title(__('Packages Added Successfully'))
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('view_location')
                        ->label(__('View Location'))
                        ->icon('heroicon-o-map-pin')
                        ->color('info')
                        ->url(fn ($record) => "https://www.google.com/maps?q={$record->lat},{$record->lng}")
                        ->openUrlInNewTab(),

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
            ])
            ->headerActions([
                Tables\Actions\Action::make('view_schools_map')
                    ->label(__('View Schools Map'))
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
            RelationManagers\DriversRelationManager::class,
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
