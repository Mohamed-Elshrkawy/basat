<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?int $navigationSort = 15;

    public static function getNavigationLabel(): string
    {
        return __('Roles And Permissions');
    }

    public static function getModelLabel(): string
    {
        return __('Role');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Roles');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('System Management');
    }

    public static function form(Form $form): Form
    {
        $permissions = Permission::all();

        $permissionsByGroup = [
            __('Auctions') => $permissions->filter(fn($p) => str_contains($p->name, '_auctions')),
            __('Users') => $permissions->filter(fn($p) => str_contains($p->name, '_users')),
            __('Cities') => $permissions->filter(fn($p) => str_contains($p->name, '_cities')),
            __('Charities') => $permissions->filter(fn($p) => str_contains($p->name, '_charities')),
            __('Categories') => $permissions->filter(fn($p) => str_contains($p->name, '_categories')),
            __('Properties') => $permissions->filter(fn($p) => str_contains($p->name, '_properties')),
            __('Banners') => $permissions->filter(fn($p) => str_contains($p->name, '_banners')),
            __('Reports') => $permissions->filter(fn($p) =>
                str_contains($p->name, '_payments') ||
                str_contains($p->name, '_wallet_transactions')
            ),
            __('Messages') => $permissions->filter(fn($p) => str_contains($p->name, '_contact_messages')),
            __('Settings') => $permissions->filter(fn($p) => str_contains($p->name, '_settings')),
            __('Roles') => $permissions->filter(fn($p) => str_contains($p->name, '_roles')),
            __('Account Deletion Requests') => $permissions->filter(fn($p) => str_contains($p->name, '_account_deletion_requests')),
            __('Conversations') => $permissions->filter(fn($p) => str_contains($p->name, '_conversations') || str_contains($p->name, '_conversation')),
            __('Home') => $permissions->filter(fn($p) => str_starts_with($p->name, 'home_')),
        ];

        $checkboxSections = [];

        foreach ($permissionsByGroup as $groupName => $groupPermissions) {
            if ($groupPermissions->isEmpty()) continue;

            $checkboxSections[] = Forms\Components\Section::make(__($groupName))
                ->description(__('Manage') . ' ' . __($groupName) . ' ' . __('permissions'))
                ->icon('heroicon-o-shield-check')
                ->schema([
                    Forms\Components\Group::make([
                        Forms\Components\CheckboxList::make('permissions')
                            ->label(false)
                            ->options(
                                $groupPermissions->mapWithKeys(function ($permission) {
                                    return [$permission->id => __($permission->name)];
                                })->toArray()
                            )
                            ->columns(1)
                            ->bulkToggleable()
                            ->gridDirection('row'),
                    ])
                        ->extraAttributes([
                            'class' => 'overflow-y-auto p-2',
                            'style' => 'max-height: 320px;',
                        ]),
                ])
                ->columnSpan(1)
                ->collapsible()
                ->compact()
                ->extraAttributes([
                    'style' => 'min-height: 400px; display: flex; flex-direction: column;',
                ]);
        }

        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label(__('Role Name'))
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->helperText(__('Example: Auctions_Admin, Clients_Admin'))
                ->columnSpanFull(),

            Forms\Components\Grid::make([
                'default' => 2,
                'sm' => 2,
                'md' => 3,
                'lg' => 4,
                'xl' => 4,
            ])
                ->schema($checkboxSections)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label(__('ID'))->sortable(),
                Tables\Columns\TextColumn::make('name')->label(__('Role Name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('permissions_count')->label(__('Permissions Count'))->counts('permissions')->badge()->color('success')->sortable(),
                Tables\Columns\TextColumn::make('users_count')->label(__('Users Count'))->counts('users')->badge()->color('info')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label(__('Created At'))->dateTime('d/m/Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'asc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Role $record) {
                        if ($record->users()->exists()) {
                            throw new \Exception(__('Cannot delete this role because it is assigned to users'));
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

//    public static function canViewAny(): bool
//    {
//        return auth()->user()->can('view_roles');
//    }
//
//
//    public static function canCreate(): bool
//    {
//        return auth()->user()->can('create_roles');
//    }
//
//    public static function canUpdate(Model $record): bool
//    {
//        return auth()->user()->can('update_roles');
//    }
//
//    public static function canDelete(Model $record): bool
//    {
//        return auth()->user()->can('delete_roles');
//    }
}
