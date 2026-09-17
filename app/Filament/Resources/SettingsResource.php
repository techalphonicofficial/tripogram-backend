<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingsResource\Pages;
use App\Models\Settings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;

class SettingsResource extends Resource
{
    protected static ?string $model = Settings::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationLabel = 'App Settings';
    protected static ?string $pluralLabel = 'Settings';
    protected static ?string $label = 'Settings';

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        // Admin & Manager bypass
        if ($user->role == 'admin') {
            return true;
        }

        $role = Role::where('name', $user->role)->first();
        $role_id = $role->id;
        if (!$role_id) {
            return false;
        }
        
        if (!$role) {
            abort(403, 'Role not found');
        }
        
        $rolePermissionIds = DB::table('role_has_permissions')
            ->where('role_id', $role_id)
            ->pluck('permission_id');

        if ($rolePermissionIds->isEmpty()) {
            return false;
        }
        
        $permissionResources = DB::table('permissions')
            ->whereIn('id', $rolePermissionIds)
            ->pluck('resource')
            ->toArray();

        if (!in_array('settings', $permissionResources)) {
            return false;
        }

        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Settings')
                    ->schema([
                        Forms\Components\TextInput::make('razorpay_key_id')
                            ->label('Razorpay Key ID')
                            ->password()
                            ->revealable()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('razorpay_key_secret')
                            ->label('Razorpay Key Secret')
                            ->password()
                            ->revealable()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('package_amount_percent')
                            ->label('Package Amount Percent')
                            ->type('number')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Webhook Settings')
                    ->schema([
                        Forms\Components\TextInput::make('privyr_webhook_url')
                            ->label('Privyr Webhook URL')
                            ->required()
                            ->url()
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('SEO & Tracking Codes')
                    ->schema([
                        Forms\Components\Textarea::make('gtm_header')
                            ->label('GTM Header Code')
                            ->rows(6)
                            ->helperText('Paste your GTM/Google Analytics header code here. This will be placed in <head> section.')
                            ->columnSpanFull(),
                        
                        Forms\Components\Textarea::make('gtm_footer')
                            ->label('GTM Footer Code')
                            ->rows(6)
                            ->helperText('Paste your GTM/Google Analytics footer code here. This will be placed before closing </body> tag.')
                            ->columnSpanFull(),
                        
                        Forms\Components\Textarea::make('robots_txt')
                            ->label('robots.txt')
                            ->rows(6)
                            ->helperText('Paste your robots.txt rules here.')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Most Popular Tour Section')
                    ->schema([
                        Forms\Components\TextInput::make('popular_title')
                            ->label('Title')
                            ->default('Most Popular Tour')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('popular_description')
                            ->label('Description')
                            ->default('Discover the world\'s most popular tours with Enlivetrips - where every journey is crafted for unforgettable experiences.')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),

                Tables\Columns\TextColumn::make('privyr_webhook_url')
                    ->label('Privyr Webhook')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('razorpay_key_id')
                    ->label('Razorpay Key ID')
                    ->limit(20)
                    ->searchable(),

                Tables\Columns\TextColumn::make('package_amount_percent')
                    ->label('Package %')
                    ->numeric(),

                Tables\Columns\TextColumn::make('gtm_header')
                    ->label('GTM Header')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('gtm_footer')
                    ->label('GTM Footer')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('robots_txt')
                    ->label('robots.txt')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSettings::route('/create'),
            'edit' => Pages\EditSettings::route('/{record}/edit'),
        ];
    }
}