<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfferFaqResource\Pages;
use App\Models\OfferFaq;
use App\Models\Role;
use Filament\Forms\Form;
use Filament\Forms\Components\{TextInput, Toggle, Textarea, Section};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OfferFaqResource extends Resource
{
    protected static ?string $model = OfferFaq::class;

    protected static ?string $navigationIcon    = 'heroicon-o-chat-bubble-bottom-center-text';
    protected static ?string $navigationGroup   = 'Offer Management';
    protected static ?string $navigationLabel   = 'Grand Sale FAQs';
    protected static ?int    $navigationSort    = 6;

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

        return in_array('offer-faqs', $permissionResources)
            || in_array('admin', $permissionResources);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Offer FAQ Item')
                ->schema([
                    TextInput::make('question')
                        ->label('Question')
                        ->required()
                        ->placeholder('e.g. How can I get the offer during the Grand Travel Sale?')
                        ->columnSpanFull(),

                    Textarea::make('answer')
                        ->label('Answer')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),

                    TextInput::make('sort_order')
                        ->label('Sort Order')
                        ->numeric()
                        ->default(0),

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
                Tables\Columns\TextColumn::make('sort_order')->label('Order')->sortable()->width('60px'),
                Tables\Columns\TextColumn::make('question')->label('Question')->searchable()->limit(60),
                Tables\Columns\TextColumn::make('answer')->label('Answer')->limit(60),
                Tables\Columns\ToggleColumn::make('is_active')->label('Active'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('d M Y')->sortable(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index'  => Pages\ListOfferFaqs::route('/'),
            'create' => Pages\CreateOfferFaq::route('/create'),
            'edit'   => Pages\EditOfferFaq::route('/{record}/edit'),
        ];
    }
}
