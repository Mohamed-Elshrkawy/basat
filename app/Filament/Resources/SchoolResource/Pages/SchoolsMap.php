<?php

namespace App\Filament\Resources\SchoolResource\Pages;

use App\Filament\Resources\SchoolResource;
use App\Models\School;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;

class SchoolsMap extends Page
{
    protected static string $resource = SchoolResource::class;

    protected static string $view = 'filament.resources.school-resource.pages.schools-map';

    protected static ?string $title = 'خريطة المدارس';

    protected static ?string $navigationIcon = 'heroicon-o-map';

    public function getSchools()
    {
        return School::all()->map(function ($school) {
            return [
                'id' => $school->id,
                'name' => [
                    'ar' => $school->name['ar'] ?? $school->getTranslation('name', 'ar') ?? 'غير محدد',
                    'en' => $school->name['en'] ?? $school->getTranslation('name', 'en') ?? 'Not Set',
                ],
                'lat' => (float) $school->lat,
                'lng' => (float) $school->lng,
                'packages_count' => $school->packages()->count(),
            ];
        });
    }

    public function createSchool(array $data)
    {
        try {
            $school = School::create([
                'name' => [
                    'ar' => $data['name_ar'],
                    'en' => $data['name_en'],
                ],
                'lat' => $data['lat'],
                'lng' => $data['lng'],
            ]);

            Notification::make()
                ->title('تم إنشاء المدرسة بنجاح')
                ->success()
                ->send();

            // إرجاع البيانات بنفس الصيغة
            return [
                'id' => $school->id,
                'name' => [
                    'ar' => $school->name['ar'] ?? $school->getTranslation('name', 'ar'),
                    'en' => $school->name['en'] ?? $school->getTranslation('name', 'en'),
                ],
                'lat' => (float) $school->lat,
                'lng' => (float) $school->lng,
                'packages_count' => 0,
            ];
        } catch (\Exception $e) {
            Notification::make()
                ->title('حدث خطأ أثناء الإنشاء')
                ->danger()
                ->send();

            return null;
        }
    }

    public function updateSchool($id, array $data)
    {
        try {
            $school = School::findOrFail($id);

            $school->update([
                'name' => [
                    'ar' => $data['name_ar'],
                    'en' => $data['name_en'],
                ],
                'lat' => $data['lat'],
                'lng' => $data['lng'],
            ]);

            Notification::make()
                ->title('تم تحديث المدرسة بنجاح')
                ->success()
                ->send();

            // إرجاع البيانات بنفس الصيغة
            return [
                'id' => $school->id,
                'name' => [
                    'ar' => $school->name['ar'] ?? $school->getTranslation('name', 'ar'),
                    'en' => $school->name['en'] ?? $school->getTranslation('name', 'en'),
                ],
                'lat' => (float) $school->lat,
                'lng' => (float) $school->lng,
                'packages_count' => $school->packages()->count(),
            ];
        } catch (\Exception $e) {
            Notification::make()
                ->title('حدث خطأ أثناء التحديث')
                ->danger()
                ->send();

            return null;
        }
    }

    public function updateSchoolLocation($id, $lat, $lng)
    {
        try {
            $school = School::findOrFail($id);
            $school->update([
                'lat' => $lat,
                'lng' => $lng,
            ]);

            Notification::make()
                ->title('تم تحديث الموقع بنجاح')
                ->success()
                ->send();

            return true;
        } catch (\Exception $e) {
            Notification::make()
                ->title('حدث خطأ أثناء تحديث الموقع')
                ->danger()
                ->send();

            return false;
        }
    }

    public function deleteSchool($id)
    {
        try {
            $school = School::findOrFail($id);
            $school->delete();

            Notification::make()
                ->title('تم حذف المدرسة بنجاح')
                ->success()
                ->send();

            return true;
        } catch (\Exception $e) {
            Notification::make()
                ->title('حدث خطأ أثناء الحذف')
                ->danger()
                ->send();

            return false;
        }
    }
}
