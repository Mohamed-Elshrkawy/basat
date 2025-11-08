<?php

namespace App\Filament\Resources\PublicBusRouteResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class SchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';

    protected static ?string $title = 'جداول الرحلات';

    protected static ?string $modelLabel = 'رحلة';

    protected static ?string $pluralModelLabel = 'جداول الرحلات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الرحلة')
                    ->schema([
                        Forms\Components\Select::make('vehicle_id')
                            ->label('الحافلة')
                            ->relationship('vehicle', 'plate_number')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->brand} {$record->model} - {$record->plate_number}")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('اختر الحافلة التي ستقوم بتنفيذ الرحلة'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('departure_time')
                                    ->label('وقت المغادرة')
                                    ->required()
                                    ->seconds(false)
                                    ->helperText('وقت المغادرة من المحطة الأولى'),

                                Forms\Components\TextInput::make('price_per_km')
                                    ->label('سعر الكيلومتر الواحد')
                                    ->numeric()
                                    ->prefix('SAR')
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->default(2.00)
                                    ->helperText('سيتم حساب سعر كل محطة تلقائياً'),
                            ]),

                        Forms\Components\CheckboxList::make('days_of_week')
                            ->label('أيام التشغيل')
                            ->options([
                                'saturday' => 'السبت',
                                'sunday' => 'الأحد',
                                'monday' => 'الإثنين',
                                'tuesday' => 'الثلاثاء',
                                'wednesday' => 'الأربعاء',
                                'thursday' => 'الخميس',
                                'friday' => 'الجمعة',
                            ])
                            ->required()
                            ->columns(4)
                            ->gridDirection('row')
                            ->default(['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday']),

                        Forms\Components\Toggle::make('is_active')
                            ->label('تفعيل الرحلة')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('departure_time')
            ->columns([
                Tables\Columns\TextColumn::make('vehicle.plate_number')
                    ->label('الحافلة')
                    ->searchable()
                    ->weight('semibold')
                    ->description(fn ($record) => $record->vehicle ? "{$record->vehicle->brand} {$record->vehicle->model}" : '-')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('departure_time')
                    ->label('وقت المغادرة')
                    ->time('H:i')
                    ->sortable()
                    ->badge()
                    ->size('md')
                    ->color('success')
                    ->icon('heroicon-o-clock'),

                Tables\Columns\TextColumn::make('arrival_time')
                    ->label('وقت الوصول النهائي')
                    ->getStateUsing(function ($record) {
                        $lastStop = $record->stopSchedules()->orderBy('order', 'desc')->first();
                        return $lastStop ? \Carbon\Carbon::parse($lastStop->arrival_time)->format('H:i') : '-';
                    })
                    ->badge()
                    ->size('md')
                    ->color('danger')
                    ->icon('heroicon-o-flag'),

                Tables\Columns\TextColumn::make('duration')
                    ->label('المدة')
                    ->getStateUsing(function ($record) {
                        $firstStop = $record->stopSchedules()->orderBy('order')->first();
                        $lastStop = $record->stopSchedules()->orderBy('order', 'desc')->first();

                        if (!$firstStop || !$lastStop) return '-';

                        $start = \Carbon\Carbon::parse($firstStop->departure_time);
                        $end = \Carbon\Carbon::parse($lastStop->arrival_time);
                        $diff = $start->diffInMinutes($end);

                        if ($diff < 60) return $diff . ' د';
                        $hours = floor($diff / 60);
                        $mins = $diff % 60;
                        return $mins > 0 ? "{$hours} س {$mins} د" : "{$hours} س";
                    })
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('stops_count')
                    ->label('عدد المحطات')
                    ->getStateUsing(fn ($record) => $record->stopSchedules()->count())
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('days_of_week')
                    ->label('أيام العمل')
                    ->formatStateUsing(function ($record) {
                        $daysArray = $record->days_of_week;

                        if (!$daysArray || !is_array($daysArray) || count($daysArray) === 0) {
                            return '-';
                        }

                        $days = [
                            'saturday' => 'سبت',
                            'sunday' => 'أحد',
                            'monday' => 'إثنين',
                            'tuesday' => 'ثلاثاء',
                            'wednesday' => 'أربعاء',
                            'thursday' => 'خميس',
                            'friday' => 'جمعة',
                        ];

                        $dayNames = array_map(fn($day) => $days[$day] ?? $day, $daysArray);
                        return implode(', ', $dayNames);
                    })
                    ->wrap()
                    ->limit(30),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vehicle_id')
                    ->label('الحافلة')
                    ->relationship('vehicle', 'plate_number')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->brand} {$record->model} - {$record->plate_number}")
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create_schedule')
                    ->label('إضافة رحلة')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->modalHeading('إنشاء رحلة جديدة')
                    ->modalWidth('2xl')
                    ->visible(function ($livewire) {
                        try {
                            $route = $livewire->getOwnerRecord();
                            return $route && $route->stops()->count() >= 2;
                        } catch (\Exception $e) {
                            return false;
                        }
                    })
                    ->form([
                        Forms\Components\Select::make('vehicle_id')
                            ->label('الحافلة')
                            ->relationship('vehicle', 'plate_number')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->brand} {$record->model} - {$record->plate_number}")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('اختر الحافلة التي ستقوم بتنفيذ الرحلة'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('departure_time')
                                    ->label('وقت المغادرة')
                                    ->required()
                                    ->seconds(false)
                                    ->helperText('وقت المغادرة من المحطة الأولى'),

                                Forms\Components\TextInput::make('price_per_km')
                                    ->label('سعر الكيلومتر الواحد')
                                    ->numeric()
                                    ->prefix('SAR')
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->default(2.00)
                                    ->helperText('سيتم حساب سعر كل محطة تلقائياً'),
                            ]),

                        Forms\Components\CheckboxList::make('days_of_week')
                            ->label('أيام التشغيل')
                            ->options([
                                'saturday' => 'السبت',
                                'sunday' => 'الأحد',
                                'monday' => 'الإثنين',
                                'tuesday' => 'الثلاثاء',
                                'wednesday' => 'الأربعاء',
                                'thursday' => 'الخميس',
                                'friday' => 'الجمعة',
                            ])
                            ->required()
                            ->columns(4)
                            ->gridDirection('row')
                            ->default(['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday']),

                        Forms\Components\Toggle::make('is_active')
                            ->label('تفعيل الرحلة')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->action(function (array $data, $livewire) {
                        try {
                            $route = $livewire->getOwnerRecord();

                            if (!$route || $route->stops()->count() < 2) {
                                Notification::make()
                                    ->title('لا يمكن إنشاء رحلة')
                                    ->body('يجب أن يحتوي المسار على محطتين على الأقل')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            \DB::beginTransaction();

                            $schedule = $route->schedules()->create([
                                'vehicle_id' => $data['vehicle_id'],
                                'departure_time' => $data['departure_time'],
                                'days_of_week' => $data['days_of_week'],
                                'is_active' => $data['is_active'] ?? true,
                            ]);

                            if (!$schedule || !$schedule->id) {
                                throw new \Exception('فشل إنشاء الرحلة');
                            }

                            $stops = $route->stops()->orderBy('order')->get();

                            if ($stops->isEmpty()) {
                                throw new \Exception('لا توجد محطات في المسار');
                            }

                            $pricePerKm = $data['price_per_km'] ?? 2.00;

                            foreach ($stops as $index => $stop) {
                                $stopDuration = ($stop->cumulative_time_minutes ?? 0) + ($index * 15);

                                $departureDateTime = \Carbon\Carbon::parse($data['departure_time']);
                                $arrivalTime = $departureDateTime->copy()->addMinutes($stopDuration);
                                $departureTimeForStop = $index === 0
                                    ? $data['departure_time']
                                    : $arrivalTime->copy()->addMinutes(15)->format('H:i:s');

                                $cumulativeDistance = 0;
                                for ($i = 1; $i <= $index; $i++) {
                                    $cumulativeDistance += $stops[$i]->distance_from_previous_km ?? 0;
                                }
                                $fare = round($cumulativeDistance * $pricePerKm, 2);

                                $schedule->stopSchedules()->create([
                                    'route_stop_id' => $stop->id,
                                    'arrival_time' => $arrivalTime->format('H:i:s'),
                                    'departure_time' => $departureTimeForStop,
                                    'fare' => $fare,
                                    'order' => $stop->order,
                                ]);
                            }

                            \DB::commit();

                            Notification::make()
                                ->title('تم إنشاء الرحلة بنجاح')
                                ->body("تم إنشاء مواعيد لـ {$stops->count()} محطة")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            \DB::rollBack();

                            Notification::make()
                                ->title('حدث خطأ أثناء إنشاء الرحلة')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            \Log::error('Schedule creation error: ' . $e->getMessage());
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view_stops')
                        ->label('عرض المحطات والمواعيد')
                        ->icon('heroicon-o-map-pin')
                        ->color('info')
                        ->modalHeading('مواعيد المحطات')
                        ->modalContent(fn ($record) => view('filament.modals.schedule-stops', [
                            'schedule' => $record,
                            'stopSchedules' => $record->stopSchedules()->with('stop.city')->orderBy('order')->get()
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('إغلاق')
                        ->slideOver(),

                    Tables\Actions\EditAction::make()
                        ->modalHeading('تعديل الرحلة')
                        ->modalWidth('2xl')
                        ->mutateFormDataUsing(function (array $data): array {
                            unset($data['price_per_km']);
                            return $data;
                        })
                        ->successNotificationTitle('تم تحديث الرحلة بنجاح'),

                    Tables\Actions\Action::make('toggle_status')
                        ->label(fn ($record) => $record->is_active ? 'تعطيل' : 'تفعيل')
                        ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update(['is_active' => !$record->is_active]);

                            Notification::make()
                                ->title($record->is_active ? 'تم تفعيل الرحلة' : 'تم تعطيل الرحلة')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->modalHeading('حذف الرحلة')
                        ->modalDescription('سيتم حذف جميع مواعيد المحطات المرتبطة بهذه الرحلة')
                        ->successNotificationTitle('تم حذف الرحلة بنجاح'),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('تفعيل المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each->update(['is_active' => true]);

                            Notification::make()
                                ->title('تم تفعيل الرحلات المحددة')
                                ->body("تم تفعيل {$records->count()} رحلة")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('تعطيل المحدد')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each->update(['is_active' => false]);

                            Notification::make()
                                ->title('تم تعطيل الرحلات المحددة')
                                ->body("تم تعطيل {$records->count()} رحلة")
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->modalHeading('حذف الرحلات المحددة')
                        ->modalDescription('سيتم حذف جميع مواعيد المحطات المرتبطة بهذه الرحلات'),
                ]),
            ])
            ->defaultSort('departure_time', 'asc')
            ->emptyStateHeading('لا توجد رحلات')
            ->emptyStateDescription('قم بإضافة رحلة للمسار من خلال الزر "إضافة رحلة" أعلى الجدول')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }
}
