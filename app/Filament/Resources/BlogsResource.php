<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogsResource\Pages;
use App\Models\Blogs;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Set;

class BlogsResource extends Resource
{
    protected static ?string $model = Blogs::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Content Management';

    public static function getNavigationBadge(): ?string
    {
        return (string) Blogs::count();
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized');
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

        if (!in_array('blogs', $permissionResources)) {
            return false;
        }

        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Blog Information')
                    ->schema([
                        TextInput::make('heading')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Set $set, ?string $state) => $set('slug', Str::slug($state)))
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->columnSpanFull(),

                        FileUpload::make('image')
                            ->label('Blog Image (1200*630)')
                            ->helperText('Upload blog thumbnail image')
                            ->image()
                            ->directory('blog')
                            ->maxSize(2048)
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('banner_alt')
                            ->label('Banner Alt Text')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        RichEditor::make('content')
                            ->label('Main Content')
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('excerpt')
                            ->label('Short Excerpt')
                            ->rows(3)
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('tags')
                            ->label('Tags')
                            ->helperText('Comma separated tags')
                            ->columnSpanFull(),

                        TextInput::make('status')
                            ->label('Status')
                            ->numeric()
                            ->default(1)
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('date')
                            ->label('Publish Date')
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),

                // FAQ SECTION - Changed from KeyValue to Repeater
                Forms\Components\Section::make('FAQ Section')
                    ->schema([
                        Repeater::make('faq')
                            ->label('Frequently Asked Questions')
                            ->schema([
                                TextInput::make('question')
                                    ->label('Question')
                                    ->required()
                                    ->placeholder('Enter your question here...'),
                                RichEditor::make('answer')
                                    ->label('Answer')
                                    ->required()
                                    ->placeholder('Enter the answer here...'),
                            ])
                            ->addActionLabel('Add FAQ')
                            ->collapsible()
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),

                // NEW SECTION: Blog Details (Replaces the RelationManager)
                Section::make('Blog Details')
                    ->description('Manage multiple detail blocks for this blog post')
                    ->schema([
                        Repeater::make('blogDetails')
                            ->relationship('blogDetails') // Make sure this relationship exists in Blogs model
                            ->schema([
                                FileUpload::make('image')
                                    ->label('Detail Image')
                                    ->image()
                                    ->directory('blog-details')
                                    ->maxSize(2048)
                                    ->columnSpanFull(),
                                
                                TextInput::make('alt')
                                    ->label('Image Alt Text')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                
                                RichEditor::make('content')
                                    ->label('Detail Content')
                                    ->required()
                                    ->columnSpanFull()
                                    ->helperText('Add the detailed content for this blog section'),
                            ])
                            ->columns(1)
                            ->addActionLabel('Add New Detail Block')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['alt'] ?? 'New Detail Block')
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('SEO Information')
                    ->schema([
                        TextInput::make('meta_title')
                            ->maxLength(255)
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('meta_description')
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('meta_keywords')
                            ->maxLength(255)
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('schema')
                            ->label('Schema JSON (optional)')
                            ->rules(['json'])
                            ->rows(5)
                            ->autosize()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->searchable()->sortable(),
                TextColumn::make('heading')->searchable()->sortable()
                    ->limit(70)
                    ->tooltip(fn ($record) => $record->heading),
                TextColumn::make('slug')->searchable()->limit(30),
                TextColumn::make('faq')
                    ->label('FAQs Count')
                    ->formatStateUsing(fn ($record) => $record->faq ? count($record->faq) . ' FAQs' : 'No FAQs')
                    ->badge()
                    ->color(fn ($record) => $record->faq ? 'success' : 'gray'),
                TextColumn::make('blogDetails')
                    ->label('Details Count')
                    ->formatStateUsing(fn ($record) => $record->blogDetails->count() . ' Details')
                    ->badge()
                    ->color('info'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state == 1 ? 'Active' : 'Inactive')
                    ->color(fn ($state) => $state == 1 ? 'success' : 'danger'),
                TextColumn::make('date')->date('d M Y')->sortable(),
                TextColumn::make('created_at')->dateTime('d M Y')->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // REMOVED getRelations() method - No more RelationManager

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogs::route('/'),
            'create' => Pages\CreateBlogs::route('/create'),
            'edit' => Pages\EditBlogs::route('/{record}/edit'),
        ];
    }
}