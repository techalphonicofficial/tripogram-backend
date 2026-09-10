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
                            ->columnSpanFull(),

                        Textarea::make('excerpt')
                            ->label('Short Excerpt')
                            ->rows(3)
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('tags')
                            ->label('Tags')
                            ->helperText('Comma separated tags')
                            ->columnSpanFull(),
  Forms\Components\Section::make('Blog Details')
                    ->schema([
                        Repeater::make('blogDetails')
                            ->relationship('blogDetails') // Ye relationship Blogs model mein define hai
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
                                    ->columnSpanFull(),
                            ])
                            ->addActionLabel('Add Blog Detail')
                            ->collapsible()
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),
                        Forms\Components\Select::make('status')
                            ->options([
                                '1' => 'Active',
                                '0' => 'Inactive',
                            ])
                            ->default('Active')
                            ->required(),

                        Forms\Components\DatePicker::make('date')
                            ->label('Publish Date')
                            ->required(),
                    ])
                    ->columns(2),

                // FAQ SECTION
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

                // BLOG DETAILS SECTION (LatestBlogDetail table mein data store hoga)
              

                // SEO Information
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
                        'active' => 'Active',
                        'inactive' => 'Inactive',
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
protected function getTableContentFooter(): ?\Illuminate\Contracts\View\View
    {
        return view('filament.components.table-footer', [
            'total' => $this->getTableRecords()->total(),
        ]);
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogs::route('/'),
            'create' => Pages\CreateBlogs::route('/create'),
            'edit' => Pages\EditBlogs::route('/{record}/edit'),
        ];
    }
}