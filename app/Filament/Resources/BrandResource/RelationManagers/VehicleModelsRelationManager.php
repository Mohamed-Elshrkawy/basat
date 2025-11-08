<?php

namespace App\Filament\Resources\BrandResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class VehicleModelsRelationManager extends RelationManager
{
    protected static string $relationship = 'vehicleModels';

    protected static ?string $title = 'الموديلات';

    protected static ?string $modelLabel = 'موديل';

    protected static ?string $pluralModelLabel = 'الموديلات';

    protected static ?string $icon = 'heroicon-o-rectangle-stack';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الموديل')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الموديل')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('مثال: كوستر، سبرنتر، كاونتي'),

                        Forms\Components\TextInput::make('default_seat_count')
                            ->label('عدد المقاعد الافتراضي')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(100)
                            ->default(50)
                            ->suffix('مقعد')
                            ->helperText('يمكن تغييره لاحقاً عند إضافة المركبة'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('الموديل نشط')
                            ->default(true)
                            ->inline(false)
                            ->helperText('الموديلات غير النشطة لن تظهر في القوائم'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الموديل')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                Tables\Columns\TextColumn::make('default_seat_count')
                    ->label('المقاعد الافتراضية')
                    ->sortable()
                    ->suffix(' مقعد')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('vehicles_count')
                    ->label('عدد المركبات')
                    ->counts('vehicles')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('النشطة فقط')
                    ->falseLabel('غير النشطة فقط'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة موديل')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('إضافة موديل جديد')
                    ->successNotificationTitle('تم إضافة الموديل بنجاح')
                    // تحديد brand_id تلقائياً
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['brand_id'] = $this->ownerRecord->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->successNotificationTitle('تم تحديث الموديل بنجاح'),

                Tables\Actions\Action::make('toggle_status')
                    ->label(fn ($record) => $record->is_active ? 'تعطيل' : 'تفعيل')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['is_active' => !$record->is_active]);

                        Notification::make()
                            ->title($record->is_active ? 'تم تفعيل الموديل' : 'تم تعطيل الموديل')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->requiresConfirmation()
                    ->successNotificationTitle('تم حذف الموديل بنجاح'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('تفعيل المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);

                            Notification::make()
                                ->title('تم تفعيل الموديلات المحددة')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('تعطيل المحدد')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);

                            Notification::make()
                                ->title('تم تعطيل الموديلات المحددة')
                                ->warning()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة موديل')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('إضافة موديل لهذه الماركة')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['brand_id'] = $this->ownerRecord->id;
                        return $data;
                    }),
            ])
            ->emptyStateHeading('لا توجد موديلات')
            ->emptyStateDescription('لم يتم إضافة موديلات لهذه الماركة بعد.')
            ->emptyStateIcon('heroicon-o-rectangle-stack');
    }
}
