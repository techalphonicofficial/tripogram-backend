<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CareerApplicationsResource\Pages;
use App\Models\CareerApplication;
use App\Models\Career;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\{Select, TextInput, Textarea, Section};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\SelectColumn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CareerApplicationsResource extends Resource
{
    protected static ?string $model = CareerApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'Career Management';
    protected static ?string $navigationLabel = 'Job Applications';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return (string) CareerApplication::where('status', 'new')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
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

        return in_array('career-applications', $permissionResources);
    }

    // =============================================
    // FORM — for view/edit
    // =============================================

    public static function form(Form $form): Form
    {
        return $form->schema([

            Section::make('Applicant Information')
                ->schema([
                    TextInput::make('name')
                        ->label('Applicant Name')
                        ->disabled(),

                    TextInput::make('email')
                        ->label('Email')
                        ->disabled(),

                    TextInput::make('phone')
                        ->label('Phone')
                        ->disabled(),

                    Select::make('career_id')
                        ->label('Applied For')
                        ->options(Career::pluck('title', 'id'))
                        ->disabled(),
                ])
                ->columns(2),

            Section::make('Application Details')
                ->schema([
                    TextInput::make('linkedin_url')
                        ->label('LinkedIn URL')
                        ->url()
                        ->disabled(),

                    TextInput::make('portfolio_url')
                        ->label('Portfolio URL')
                        ->url()
                        ->disabled(),

                    Textarea::make('cover_letter')
                        ->label('Cover Letter')
                        ->rows(5)
                        ->disabled()
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Application Status')
                ->schema([
                    Select::make('status')
                        ->label('Status')
                        ->required()
                        ->options([
                            'new'         => 'New',
                            'shortlisted' => 'Shortlisted',
                            'interview'   => 'Interview',
                            'selected'    => 'Selected',
                            'rejected'    => 'Rejected',
                        ]),
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
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->width('60px'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Applicant Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('career.title')
                    ->label('Job')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->career?->title),

                Tables\Columns\TextColumn::make('career.location')
                    ->label('Location')
                    ->toggleable(),

                SelectColumn::make('status')
                    ->label('Status')
                    ->options([
                        'new'         => 'New',
                        'shortlisted' => 'Shortlisted',
                        'interview'   => 'Interview',
                        'selected'    => 'Selected',
                        'rejected'    => 'Rejected',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Applied At')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new'         => 'New',
                        'shortlisted' => 'Shortlisted',
                        'interview'   => 'Interview',
                        'selected'    => 'Selected',
                        'rejected'    => 'Rejected',
                    ]),

                Tables\Filters\SelectFilter::make('career_id')
                    ->label('Job Position')
                    ->options(Career::pluck('title', 'id')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                // Download Resume action
                Tables\Actions\Action::make('download_resume')
                    ->label('Resume')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->url(fn (CareerApplication $record) => route('admin.career-applications.resume', $record->id))
                    ->openUrlInNewTab()
                    ->visible(fn (CareerApplication $record) => !empty($record->resume)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCareerApplications::route('/'),
            'view'   => Pages\ViewCareerApplication::route('/{record}'),
            'edit'   => Pages\EditCareerApplication::route('/{record}/edit'),
        ];
    }
}
