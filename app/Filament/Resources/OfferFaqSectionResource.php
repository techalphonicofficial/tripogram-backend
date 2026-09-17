<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfferFaqSectionResource\Pages;
use App\Models\OfferFaqSection;
use App\Models\Role;
use Filament\Forms\Form;
use Filament\Forms\Components\{TextInput, Toggle, Textarea, Section};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OfferFaqSectionResource extends Resource
{
    protected static ?string $model = OfferFaqSection::class;

    protected static ?string $navigationIcon    = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationGroup   = 'Offer Management';
    protected static ?string $navigationLabel   = 'Offer FAQs Section';
    protected static ?int    $navigationSort    = 5;

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        if ($user->role == 'admin') return true;

        $role = Role::where('name', $user->role)->first();
        if (!$role) return false;

        $rolePermissionIds = DB::table('role_has_permissions')
            ->where('role_id', $role->id)->pluck('permission_id');
        if ($rolePermissionIds->isEmpty()) return false;

        $permissionResources = DB::table('permissions')
            ->whereIn('id', $rolePermissionIds)->pluck('resource')->toArray();

        return in_array('offer-faq-sections', $permissionResources)
            || in_array('admin', $permissionResources);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Offer FAQs Section Headings')
                ->schema([
                    TextInput::make('heading')
                        ->label('Section Heading')
                        ->placeholder('e.g. Grand Travel Sale FAQs')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(2)
                        ->columnSpanFull(),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->width('60px'),
                Tables\Columns\TextColumn::make('heading')->label('Heading')->searchable(),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('d M Y H:i')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListOfferFaqSections::route('/'),
            'create' => Pages\CreateOfferFaqSection::route('/create'),
            'edit'   => Pages\EditOfferFaqSection::route('/{record}/edit'),
        ];
    }
}
