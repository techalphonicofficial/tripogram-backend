<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CareersResource\Pages;
use App\Models\Career;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Forms\Components\{FileUpload, RichEditor, Select, TextInput, Toggle, Textarea, Repeater, Section, DatePicker};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\ReplicateAction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CareersResource extends Resource
{
    protected static ?string $model = Career::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Career Management';
    protected static ?string $navigationLabel = 'Careers / Jobs';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) Career::count();
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($user->role == 'admin') {
            return true;
        }

        $role = Role::where('name', $user->role)->first();
        if (!$role) {
            return false;
        }

        $rolePermissionIds = DB::table('role_has_permissions')
            ->where('role_id', $role->id)
            ->pluck('permission_id');

        if ($rolePermissionIds->isEmpty()) {
            return false;
        }

        $permissionResources = DB::table('permissions')
            ->whereIn('id', $rolePermissionIds)
            ->pluck('resource')
            ->toArray();

        return in_array('careers', $permissionResources);
    }

    // =============================================
    // FORM
    // =============================================

    public static function form(Form $form): Form
    {
        return $form->schema([

            Section::make('Basic Information')
                ->schema([
                    TextInput::make('title')
                        ->label('Job Title')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state)))
                        ->columnSpanFull(),

                    TextInput::make('slug')
                        ->required()
                        ->unique(column: 'slug', ignoreRecord: true)
                        ->maxLength(255)
                        ->helperText('Auto-generated from Job Title. You can edit it.')
                        ->columnSpanFull(),

                    Select::make('department')
                        ->label('Department')
                        ->required()
                        ->options([
                            'Sales'          => 'Sales',
                            'Marketing'      => 'Marketing',
                            'Operations'     => 'Operations',
                            'Human Resource' => 'Human Resource',
                            'Technology'     => 'Technology',
                            'Design'         => 'Design',
                            'Finance'        => 'Finance',
                            'Other'          => 'Other',
                        ])
                        ->searchable()
                        ->createOptionForm([
                            TextInput::make('department')
                                ->label('New Department')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->createOptionUsing(fn (array $data): string => $data['department']),

                    TextInput::make('location')
                        ->label('Location')
                        ->required()
                        ->maxLength(255),

                    Select::make('job_type')
                        ->label('Job Type')
                        ->required()
                        ->options([
                            'Full Time'  => 'Full Time',
                            'Part Time'  => 'Part Time',
                            'Internship' => 'Internship',
                            'Contract'   => 'Contract',
                            'Remote'     => 'Remote',
                        ]),

                    TextInput::make('experience')
                        ->label('Experience')
                        ->maxLength(255)
                        ->placeholder('e.g. 1–3 Years'),

                    TextInput::make('salary')
                        ->label('Salary')
                        ->maxLength(255)
                        ->nullable()
                        ->placeholder('e.g. ₹25,000 – ₹40,000'),

                    DatePicker::make('application_deadline')
                        ->label('Application Deadline')
                        ->nullable(),

                    TextInput::make('sort_order')
                        ->label('Sort Order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Lower number = higher priority'),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])
                ->columns(2),

            Section::make('Job Description')
                ->schema([
                    Textarea::make('short_description')
                        ->label('Short Description')
                        ->rows(3)
                        ->columnSpanFull(),

                    RichEditor::make('description')
                        ->label('Full Job Description')
                        ->columnSpanFull(),

                    RichEditor::make('requirements')
                        ->label('Requirements')
                        ->columnSpanFull(),
                ]),

            Section::make('Skills')
                ->schema([
                    Repeater::make('skills')
                        ->label('Required Skills')
                        ->schema([
                            TextInput::make('skill')
                                ->label('Skill')
                                ->required()
                                ->placeholder('e.g. Communication, MS Excel, Tally'),
                        ])
                        ->addActionLabel('+ Add Skill')
                        ->collapsible()
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ]),

        ]);
    }

    // =============================================
    // TABLE
    // =============================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->width('60px'),

                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->width('60px'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Job Title')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->title),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('department')
                    ->label('Department')
                    ->searchable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->searchable(),

                Tables\Columns\TextColumn::make('job_type')
                    ->label('Job Type')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Full Time'  => 'success',
                        'Part Time'  => 'warning',
                        'Internship' => 'info',
                        'Contract'   => 'primary',
                        'Remote'     => 'gray',
                        default      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('experience')
                    ->label('Experience')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('salary')
                    ->label('Salary')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('application_deadline')
                    ->label('Deadline')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('department')
                    ->label('Department')
                    ->options([
                        'Sales'          => 'Sales',
                        'Marketing'      => 'Marketing',
                        'Operations'     => 'Operations',
                        'Human Resource' => 'Human Resource',
                        'Technology'     => 'Technology',
                        'Design'         => 'Design',
                        'Finance'        => 'Finance',
                        'Other'          => 'Other',
                    ]),

                Tables\Filters\SelectFilter::make('location')
                    ->label('Location')
                    ->options(fn () => Career::distinct()->pluck('location', 'location')->toArray()),

                Tables\Filters\SelectFilter::make('job_type')
                    ->label('Job Type')
                    ->options([
                        'Full Time'  => 'Full Time',
                        'Part Time'  => 'Part Time',
                        'Internship' => 'Internship',
                        'Contract'   => 'Contract',
                        'Remote'     => 'Remote',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                ReplicateAction::make()
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->beforeReplicaSaved(function (Career $replica): void {
                        $replica->title     = $replica->title . ' (Copy)';
                        $replica->slug      = $replica->slug . '-copy-' . time();
                        $replica->is_active = false;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // =============================================
    // RELATIONS & PAGES
    // =============================================

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCareers::route('/'),
            'create' => Pages\CreateCareers::route('/create'),
            'edit'   => Pages\EditCareers::route('/{record}/edit'),
        ];
    }
}
