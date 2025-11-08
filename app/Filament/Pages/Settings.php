<?php

namespace App\Filament\Pages;

use App\Enums\Settings as SettingsEnum;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Storage;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static string $view = 'filament.pages.settings';

    protected static ?int $navigationSort = 10;


    public static function getNavigationLabel(): string
    {
        return __('Settings');
    }

    public function getTitle(): string
    {
        return __('Settings');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Settings');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Platform Settings');
    }


    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        $tabs = [];

        $settingGrouped=SettingsEnum::grouped();

        foreach (SettingsEnum::grouped() as $group => $info) {
            $fields = [];

            foreach ($info['settings'] as $key => $meta) {
                // ضمان أن المفتاح String 100%
                $key = is_string($key) ? $key : (string) $key;

                // إنشاء الحقل بناءً على النوع
                $field = match ($meta['type']) {

                    'text' => Forms\Components\TextInput::make($key)
                        ->label(__((string)$meta['label']))
                        ->columnSpan($meta['span'] ?? 1),

                    'email' => Forms\Components\TextInput::make($key)
                        ->email()
                        ->label(__((string)$meta['label']))
                        ->columnSpan($meta['span'] ?? 1),

                    'number' => Forms\Components\TextInput::make($key)
                        ->numeric()
                        ->label(__((string)$meta['label']))
                        ->columnSpan($meta['span'] ?? 1),

                    'url' => Forms\Components\TextInput::make($key)
                        ->label(__((string)$meta['label']))
                        ->columnSpan($meta['span'] ?? 1),

                    'rich_editor' => Forms\Components\RichEditor::make($key)
                        ->label(__((string)$meta['label']))
                        ->disableToolbarButtons(['attachFiles']) // منع رفع الملفات داخل النص
                        ->columnSpan($meta['span'] ?? 'full'),

                    'image' => Forms\Components\FileUpload::make($key)
                        ->label(__((string)$meta['label']))
                        ->directory('settings')
                        ->image()
                        ->imagePreviewHeight('100')
                        ->disk('public')
                        ->columns(2),

                    'boolean' => Forms\Components\Toggle::make($key)
                        ->label(__((string)$meta['label']))
                        ->inline(false)
                        ->columnSpan($meta['span'] ?? 1),

                    default => Forms\Components\TextInput::make($key)
                        ->label(__((string)$meta['label']))
                        ->columnSpan($meta['span'] ?? 1),
                };

                $fields[] = $field;
            }

            // كل تبويب فيه Grid متجاوب
            $tabs[] = Forms\Components\Tabs\Tab::make(__($info['label']))
                ->icon($info['icon'] ?? 'heroicon-o-cog')
                ->schema([
                    Forms\Components\Grid::make([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 2,
                    ])->schema($fields)
                ]);
        }

        return $form
            ->schema([
                Forms\Components\Tabs::make('SettingsTabs')
                    ->tabs($tabs)
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }




    /**
     * Get the form actions (Save button, etc.)
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('Save Settings'))
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            foreach ($data as $key => $value) {
                // التحقق من أن المفتاح موجود في enum
                $enum = is_string($key) && SettingsEnum::tryFrom($key)
                    ? SettingsEnum::from($key)
                    : null;

                $meta = $enum ? $enum->metadata() : [];

                // تخزين الصورة بشكل صحيح
                if (($meta['type'] ?? null) === 'image' && $value instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                    // الحصول على القيمة القديمة
                    $oldValue = Setting::where('key', $key)->value('value');

                    // حذف الصورة القديمة إذا وجدت
                    if ($oldValue) {
                        Storage::disk('public')->delete($oldValue);
                    }

                    // حفظ الصورة الجديدة
                    $value = $value->store('settings', 'public');
                }

                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }

            // مسح الكاش
            Setting::clearCache();

            Notification::make()
                ->title('Settings saved successfully')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error saving settings')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

//    public static function canAccess(): bool
//    {
//        return auth()->user()->can('view_settings');
//    }
}
