<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Pages\ViewRole;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\Support\Utils;
use BezhanSalleh\FilamentShield\Traits\HasShieldFormComponents;
use BezhanSalleh\PluginEssentials\Concerns\Resource as Essentials;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Override;

class RoleResource extends Resource
{
    use Essentials\BelongsToParent;
    use Essentials\BelongsToTenant;
    use Essentials\HasGlobalSearch;
    use Essentials\HasLabels;
    use Essentials\HasNavigation;
    use HasShieldFormComponents;

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('filament-shield::filament-shield.field.name'))
                                    ->unique(
                                        ignoreRecord: true, /** @phpstan-ignore-next-line */
                                        modifyRuleUsing: fn (Unique $rule): Unique => Utils::isTenancyEnabled() ? $rule->where(Utils::getTenantModelForeignKey(), Filament::getTenant()?->id) : $rule
                                    )
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('guard_name')
                                    ->label(__('filament-shield::filament-shield.field.guard_name'))
                                    ->default(Utils::getFilamentAuthGuard())
                                    ->nullable()
                                    ->maxLength(255),

                                Select::make(config('permission.column_names.team_foreign_key'))
                                    ->label(__('filament-shield::filament-shield.field.team'))
                                    ->placeholder(__('filament-shield::filament-shield.field.team.placeholder'))
                                    /** @phpstan-ignore-next-line */
                                    ->default(Filament::getTenant()?->id)
                                    ->options(fn (): array => in_array(Utils::getTenantModel(), [null, '', '0'], true) ? [] : Utils::getTenantModel()::pluck('name', 'id')->toArray())
                                    ->visible(fn (): bool => static::shield()->isCentralApp() && Utils::isTenancyEnabled())
                                    ->dehydrated(fn (): bool => static::shield()->isCentralApp() && Utils::isTenancyEnabled()),

                                static::getSelectAllFormComponent(),
                            ])
                            ->columns([
                                'sm' => 2,
                                'lg' => 3,
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                static::getShieldFormComponents(),
            ]);
    }

    public static function getShieldFormComponents(): Component
    {
        return Tabs::make('Permissions')
            ->contained()
            ->tabs([
                static::getCustomTabFormComponentForResources(),
                static::getTabFormComponentForPage(),
                static::getTabFormComponentForWidget(),
                static::getTabFormComponentForCustomPermissions(),
            ])
            ->columnSpan('full');
    }

    public static function getCustomTabFormComponentForResources(): Component
    {
        $entities = FilamentShield::getResources();

        $groups = [
            'Transaction Pipeline' => [
                'App\Filament\Resources\BkashTransactions\BkashTransactionResource',
                'App\Filament\Resources\BkashTransactionAuthorizations\BkashTransactionAuthorizationResource',
                'App\Filament\Resources\BkashTransactionConfirmations\BkashTransactionConfirmationResource',
            ],
            'Audits & Reports' => [
                'App\Filament\Resources\BkashBatches\BkashBatchResource',
                'App\Filament\Resources\BkashFailedTransactions\BkashFailedTransactionResource',
                'App\Filament\Resources\EftReturns\EftReturnResource',
                'App\Filament\Resources\BkashReports\BkashReportsResource',
            ],
            'Administration' => [
                'App\Filament\Resources\Organizations\OrganizationResource',
                'App\Filament\Resources\Users\UserResource',
                'App\Filament\Resources\Roles\RoleResource',
            ],
        ];

        $entitiesByFqcn = collect($entities)->keyBy('resourceFqcn');
        $sectionSchemas = [];

        foreach ($groups as $groupName => $fqcns) {
            $cardSchemas = [];
            foreach ($fqcns as $fqcn) {
                if ($entity = $entitiesByFqcn->get($fqcn)) {
                    $cardSchemas[] = static::getCustomCardSectionForResource($entity);
                }
            }

            if (!empty($cardSchemas)) {
                $sectionSchemas[] = Section::make($groupName)
                    ->collapsible()
                    ->compact()
                    ->schema([
                        Grid::make()
                            ->schema($cardSchemas)
                            ->columns(static::shield()->getGridColumns()),
                    ]);
            }
        }

        $usedFqcns = collect($groups)->flatten()->toArray();
        $otherEntities = collect($entities)->filter(fn ($e) => !in_array($e['resourceFqcn'], $usedFqcns, true));
        if ($otherEntities->isNotEmpty()) {
            $otherCardSchemas = $otherEntities->map(fn ($e) => static::getCustomCardSectionForResource($e))->toArray();
            $sectionSchemas[] = Section::make('Other')
                ->collapsible()
                ->compact()
                ->schema([
                    Grid::make()
                        ->schema($otherCardSchemas)
                        ->columns(static::shield()->getGridColumns()),
                ]);
        }

        return Tab::make('resources')
            ->label(__('filament-shield::filament-shield.resources'))
            ->visible(fn (): bool => Utils::isResourceTabEnabled())
            ->badge(static::getResourceTabBadgeCount())
            ->schema($sectionSchemas);
    }

    public static function getCustomCardSectionForResource(array $entity): Section
    {
        return Section::make()
            ->heading(function ($get) use ($entity): HtmlString {
                $sectionLabel = strval(
                    static::shield()->hasLocalizedPermissionLabels()
                        ? FilamentShield::getLocalizedResourceLabel($entity['resourceFqcn'])
                        : $entity['model']
                );

                $state = $get($entity['resourceFqcn']) ?? [];
                $count = is_array($state) ? count($state) : 0;
                $total = count($entity['permissions']);

                $badgeStyle = match (true) {
                    $count === 0 => 'background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;',
                    $count >= $total => 'background: #d1fae5; color: #047857; border: 1px solid #34d399;',
                    default => 'background: #e0f2fe; color: #0369a1; border: 1px solid #7dd3fc;',
                };

                $badgeHtml = sprintf(
                    '<span style="padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; margin-left: 0.5rem; display: inline-block; %s">%d/%d</span>',
                    $badgeStyle,
                    $count,
                    $total
                );

                return new HtmlString('<span>' . e($sectionLabel) . '</span> ' . $badgeHtml);
            })
            ->description(fn (): HtmlString => new HtmlString('<span style="word-break: break-word;">' . Utils::showModelPath($entity['modelFqcn']) . '</span>'))
            ->compact()
            ->schema([
                static::getCustomCheckBoxListComponentForResource($entity),
            ])
            ->columnSpan(static::shield()->getSectionColumnSpan())
            ->collapsible();
    }

    public static function getCustomCheckBoxListComponentForResource(array $entity): Component
    {
        $permissionsArray = static::getResourcePermissionOptions($entity);
        $formattedOptions = [];
        $destructiveLabels = ['Delete', 'Delete Any', 'Force Delete', 'Force Delete Any'];

        foreach ($permissionsArray as $key => $label) {
            if (in_array($label, $destructiveLabels, true)) {
                $formattedOptions[$key] = new HtmlString(
                    '<span class="text-rose-600 dark:text-rose-400 font-medium">' . e($label) . '</span>'
                );
            } else {
                $formattedOptions[$key] = $label;
            }
        }

        return static::getCheckboxListFormComponent(
            name: $entity['resourceFqcn'],
            options: $formattedOptions,
            searchable: false,
            columns: ['default' => 2, 'sm' => 2, 'md' => 3, 'xl' => 3],
            columnSpan: static::shield()->getResourceCheckboxListColumnSpan()
        );
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->weight(FontWeight::Medium)
                    ->label(__('filament-shield::filament-shield.column.name'))
                    ->formatStateUsing(fn (string $state): string => Str::headline($state))
                    ->searchable(),
                TextColumn::make('guard_name')
                    ->badge()
                    ->color('warning')
                    ->label(__('filament-shield::filament-shield.column.guard_name')),
                TextColumn::make('team.name')
                    ->default('Global')
                    ->badge()
                    ->color(fn (mixed $state): string => str($state)->contains('Global') ? 'gray' : 'primary')
                    ->label(__('filament-shield::filament-shield.column.team'))
                    ->searchable()
                    ->visible(fn (): bool => static::shield()->isCentralApp() && Utils::isTenancyEnabled()),
                TextColumn::make('permissions_count')
                    ->badge()
                    ->label(__('filament-shield::filament-shield.column.permissions'))
                    ->counts('permissions')
                    ->color('primary'),
                TextColumn::make('updated_at')
                    ->label(__('filament-shield::filament-shield.column.updated_at'))
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'view' => ViewRole::route('/{record}'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModel(): string
    {
        return Utils::getRoleModel();
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return Utils::getResourceSlug();
    }

    public static function getCluster(): ?string
    {
        return Utils::getResourceCluster();
    }

    public static function getEssentialsPlugin(): ?FilamentShieldPlugin
    {
        return FilamentShieldPlugin::get();
    }
}
