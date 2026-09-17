<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackagesResource\Pages;
use App\Filament\Resources\PackagesResource\RelationManagers\ActiveCostsRelationManager;
use App\Filament\Resources\PackagesResource\RelationManagers\PackageDatesRelationManager;
use App\Models\Packages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Set;
use Illuminate\Support\Str;
use Filament\Forms\Components\Repeater;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;
use Filament\Forms\Components\{FileUpload, RichEditor, Select, TextInput, Toggle, Tabs, Textarea};
use Filament\Infolists\Components\{Grid, ImageEntry, RepeatableEntry, TextEntry, IconEntry, Section};
use Filament\Tables\Actions\ViewAction;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage; // ✅ SIRF YEH ADD KIYA

class PackagesResource extends Resource
{
    protected static ?string $model = Packages::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Travel Management';
    protected static ?int $navigationSort = 3;
    
    public static function getNavigationBadge(): ?string
    {
        return (string) Packages::count();
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        // YAHAN KUCH MAT BADALNA - SAME RAHEGA
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

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

        if (!in_array('packages', $permissionResources)) {
            return false;
        }

        return true;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('PackageTabs')
                ->tabs([
                    Tab::make('Package Details')
                        ->schema([
                            Forms\Components\Section::make('Basic Information')
                                ->schema([
                                    TextInput::make('sort_order')
                                        ->label('Sort Order')
                                        ->numeric()
                                        ->default(0)
                                        ->helperText('Lower number = Higher priority in display')
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, $record, callable $set, callable $get) {
                                            if (!$record) {
                                                return;
                                            }
                                            
                                            $oldOrder = $record->sort_order;
                                            $newOrder = (int)$state;
                                            
                                            if ($oldOrder == $newOrder) {
                                                return;
                                            }
                                            
                                            if ($newOrder > $oldOrder) {
                                                Packages::where('sort_order', '>', $oldOrder)
                                                    ->where('sort_order', '<=', $newOrder)
                                                    ->where('id', '!=', $record->id)
                                                    ->decrement('sort_order');
                                            }
                                            else if ($newOrder < $oldOrder) {
                                                Packages::where('sort_order', '<', $oldOrder)
                                                    ->where('sort_order', '>=', $newOrder)
                                                    ->where('id', '!=', $record->id)
                                                    ->increment('sort_order');
                                            }
                                            
                                            $record->update(['sort_order' => $newOrder]);
                                            
                                            Notification::make()
                                                ->title('Sort order updated and reordered successfully')
                                                ->success()
                                                ->send();
                                        })
                                        ->required(),
                                    
                                    FileUpload::make('thumbnail')
                                        ->directory('package-thumbnail')
                                        ->image()
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                        ->label('Thumbnail (969*795)')
                                        ->imageEditor()
                                        ->required(),

                                    FileUpload::make('banner')
                                        ->directory('package-banner')
                                        ->image()
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                        ->label('Banner (1920*400)')
                                        ->imageEditor()
                                        ->nullable(),
                                     
                                    FileUpload::make('map_image')
                                        ->directory('map_image')
                                        ->image()
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                        ->label('Map Image')
                                        ->imageEditor()
                                        ->nullable(),
                                  
                                    // MULTIPLE TRIPS/CATEGORIES SELECTION
                                    Select::make('trips')
                                        ->label('Select Trips/Categories (Multiple)')
                                        ->options(\App\Models\Trips::pluck('heading', 'id'))
                                        ->multiple()
                                        ->searchable()
                                        ->preload()
                                        ->relationship('trips', 'heading')
                                        ->required()
                                        ->helperText('You can select multiple trips/categories for this package')
                                        ->createOptionForm([
                                            TextInput::make('heading')
                                                ->required()
                                                ->label('Trip Heading'),
                                            TextInput::make('slug')
                                                ->required()
                                                ->label('Trip Slug'),
                                            RichEditor::make('description')
                                                ->label('Description'),
                                            Toggle::make('is_active')
                                                ->label('Active')
                                                ->default(true),
                                        ])
                                        ->createOptionUsing(function (array $data) {
                                            $trip = \App\Models\Trips::create([
                                                'heading' => $data['heading'],
                                                'slug' => $data['slug'],
                                                'description' => $data['description'] ?? null,
                                                'is_active' => $data['is_active'] ?? true,
                                            ]);
                                            return $trip->id;
                                        }),

                                    Select::make('destination_id')
                                        ->label('Select Destination')
                                        ->options(\App\Models\Destinations::pluck('name', 'id'))
                                        ->searchable()
                                        ->required(),
TextInput::make('package_code')
    ->label('Package Code')
    ->maxLength(50)
    ->required()
    ->unique(column: 'package_code', ignoreRecord: true)
    ->helperText('Unique code for this package (e.g., DL001, UK002, HP003)')
    ->placeholder('Enter package code'),
                                    TextInput::make('title')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn(Set $set, ?string $state) => $set('slug', Str::slug($state)))
                                        ->required()
                                        ->label('Package Title'),

                                    TextInput::make('slug')
                                        
                                        ->required()
                                        ->label('Package Slug'),

                                    TextInput::make('starting_price')
                                        ->numeric()
                                        ->required()
                                        ->prefix('₹'),

                                    TextInput::make('duration')
                                        ->rules(['regex:/^\d+N-\d+D$/'])
                                        ->label('Duration (Format: 2N-3D)')
                                        ->required(),
			TextInput::make('day')
    ->label('Number of Days')
    ->numeric()
    ->minValue(1)
    ->maxValue(30)
    ->required()
    ->default(0)
    ->helperText('Reminder: Day balance payment before departure'),					
                                    TextInput::make('pickup')->required()->label('Pickup Location'),
                                    TextInput::make('drop')->required()->label('Drop Location'),
                                    TextInput::make('age_group')->label('Age Group')->nullable(),
                                  
                                 
                                    
                                    Toggle::make('is_active')->label('Active')->default(true),
                                    Toggle::make('is_trending')->label('Most Popular')->default(false),
                                    Select::make('season')
                                        ->label('Popular Tour Season')
                                        ->options([
                                            'spring' => 'Spring (Mar - May)',
                                            'summer' => 'Summer (Jun - Aug)',
                                            'autumn' => 'Autumn (Sep - Nov)',
                                            'winter' => 'Winter (Dec - Feb)',
                                        ])
                                        ->placeholder('Auto-detect from package dates')
                                        ->helperText('This controls which season shows this package in the Popular Tours API.')
                                        ->searchable(),
                                    
                                    Select::make('slot')
                                        ->label('Slot')
                                        ->options([
                                            'morning' => 'Morning',
                                            'afternoon' => 'Afternoon',
                                            'evening' => 'Evening',
                                        ])
                                        ->required(),

                                    TextInput::make('booking_amount')
                                        ->label('Booking Amount')
                                        ->numeric()
                                        ->prefix('₹')
                                        ->required(),
                                        
                                    Repeater::make('gallery')
                                        ->schema([
                                            FileUpload::make('image')
                                                ->image()
                                                ->directory('package-gallery')
                                                ->maxSize(2048)
                                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                                ->required()
                                                ->columnSpan(1),
                                            
                                            TextInput::make('alt_text')
                                                ->label('Alt Text')
                                                
                                                ->columnSpan(1),
                                        ])
                                        ->columns(2)
                                        ->columnSpanFull()
                                        ->label('Gallery Images')
                                        ->defaultItems(0)
                                        ->reorderable(true)
                                        ->reorderableWithButtons()
                                        ->cloneable()
                                        ->itemLabel(fn (array $state): ?string => $state['alt_text'] ?? 'Gallery Image'),
                                ]),
Toggle::make('show_book_no_button')
                                        ->label('Show book now button')
                                        ->default(true),
                            Forms\Components\Section::make('Package Content')
                                ->schema([
                                    RichEditor::make('description')
                                        ->columnSpanFull()
                                        ->nullable(),

                                    Forms\Components\Repeater::make('itinerary')
                                        ->label('Itinerary')
                                        ->schema([
                                            TextInput::make('heading')
                                                ->label('Heading')
                                                ->columnSpanFull(),
                                            RichEditor::make('content')
                                                ->label('Content')
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(1)
                                        ->createItemButtonLabel('Add Itinerary Item')
                                        ->columnSpanFull()
                                        ->nullable(),

                                    FileUpload::make('itinerary_pdf')
                                        ->directory('itinerary-pdf')
                                        ->acceptedFileTypes(['application/pdf'])
                                        ->maxSize(5120)
                                        ->openable()
                                        ->downloadable()
                                        ->label('Itinerary PDF (Max 5MB)')
                                        ->helperText('Only PDF allowed. Max size 5 MB.')
                                        ->columnSpanFull()
                                        ->nullable(),

                                    RichEditor::make('inclusion')
                                        ->columnSpanFull()
                                        ->nullable(),
                                        
                                    RichEditor::make('exclusion')
                                        ->columnSpanFull()
                                        ->nullable(),
                                        
                                    RichEditor::make('note')
                                        ->columnSpanFull()
                                        ->nullable(),
                                        
                                    RichEditor::make('things_to_pack')
                                        ->columnSpanFull()
                                        ->nullable(),
                                ])->columns(2),
                        ]),

                    Tab::make('testimonials')
                        ->label('Testimonials')
                        ->schema([
                            Repeater::make('testimonials')
                                ->label('Testimonials')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Name')
                                        ->maxLength(255)
                                        ->nullable(),

                                    FileUpload::make('image')
                                        ->label('Image (256*256)')
                                        ->image()
                                        ->directory('package-testimonials')
                                        ->nullable(),

                                    Toggle::make('social')
                                        ->label('Social')
                                        ->default(false),

                                    Select::make('star')
                                        ->label('Rating')
                                        ->options([
                                            1 => '⭐',
                                            2 => '⭐⭐',
                                            3 => '⭐⭐⭐',
                                            4 => '⭐⭐⭐⭐',
                                            5 => '⭐⭐⭐⭐⭐',
                                        ])
                                        ->nullable(),

                                    Textarea::make('text')
                                        ->label('Testimonial Text')
                                        ->rows(4)
                                        ->nullable(),

                                    Toggle::make('status')
                                        ->label('Active')
                                        ->default(true),
                                  
                                  
                                    
                                ])
                                ->collapsible()
                                ->grid(2)
                                ->columnSpanFull()
                                ->default([])
                                ->reorderable()
                                ->addActionLabel('Add Testimonial')
                                ->nullable(),
                        ]),
                        
                    Tab::make('faqs')
                        ->schema([
                            Forms\Components\Section::make('faqs')->schema([
                                Forms\Components\Repeater::make('faqs')
                                    ->label('FAQs')
                                    ->schema([
                                        TextInput::make('question')->required(),
                                        Textarea::make('answer')->rows(2)->required(),
                                    ])
                                    ->createItemButtonLabel('Add FAQ')
                                    ->columnSpanFull()
                                    ->defaultItems(0)
                                    ->nullable(),
                            ]),
                        ]),

                    Tab::make('SEO')
                        ->schema([
                            Forms\Components\Section::make('SEO & Meta')->schema([
                                TextInput::make('meta_title')
                                    ->label('Meta Title')
                                    ->nullable(),
                                Textarea::make('meta_description')
                                    ->label('Meta Description')
                                    ->rows(2)
                                    ->nullable(),
                                TextInput::make('meta_keywords')
                                    ->label('Meta Keywords')
                                    ->nullable(),
                            ]),
                        ]),
                        
                    Tab::make('Instagram Videos')
                        ->schema([
                            Forms\Components\Section::make('Instagram Videos')
                                ->schema([
                                    Forms\Components\Repeater::make('related_insta_video')
                                        ->label('Instagram Videos')
                                        ->schema([
                                            FileUpload::make('thumbnail')
                                                ->directory('instagram-videos')
                                                ->image()
                                                ->imageEditor()
                                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                                ->label('Thumbnail (640*850px)')
                                                ->nullable(),

                                            TextInput::make('video_url')
                                                ->label('Video URL')
                                                ->placeholder('https://www.instagram.com/reel/DGN1r9eyxXH/embed')
                                                ->url()
                                                ->nullable(),
                                        ])
                                        ->createItemButtonLabel('Add Instagram Video')
                                        ->columns(1)
                                        ->columnSpanFull()
                                        ->nullable(),
                                ]),
                        ]),

                    Tab::make('YouTube Videos')
                        ->schema([
                            Forms\Components\Section::make('YouTube Videos')
                                ->schema([
                                    Forms\Components\Repeater::make('related_youtube_video')
                                        ->label('YouTube Videos')
                                        ->schema([
                                            FileUpload::make('thumbnail')
                                                ->directory('youtube-videos')
                                                ->image()
                                                ->imageEditor()
                                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                                ->label('Thumbnail(640*850px)')
                                                ->nullable(),

                                            TextInput::make('video_url')
                                                ->label('Video URL')
                                                ->placeholder('https://www.youtube.com/embed/oY7ZTv8RvM8')
                                                ->url()
                                                ->nullable(),
                                        ])
                                        ->createItemButtonLabel('Add YouTube Video')
                                        ->columns(1)
                                        ->columnSpanFull()
                                        ->nullable(),
                                ]),
                        ]),
                ])
                ->columnSpanFull()
        ]);
    }
   public static function table(Table $table): Table
{
    return $table
        ->columns([
            // ... TUMHARE SARE COLUMNS JAISE HAIN WAISE RAHENGE
            Tables\Columns\TextColumn::make('sort_order')
                ->label('Sort Order')
                ->sortable()
                ->searchable()
                ->default(0),
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('slug')->searchable()->sortable(),
            
            Tables\Columns\TextColumn::make('trips.heading')
                ->label('Trips/Categories')
                ->badge()
                ->color('info')
                ->separator(',')
                ->listWithLineBreaks()
                ->limitList(3)
                ->expandableLimitedList()
                ->searchable(),
            
            Tables\Columns\TextColumn::make('duration'),
            Tables\Columns\TextColumn::make('starting_price')->money('INR'),
            Tables\Columns\ToggleColumn::make('is_trending')
                ->label('Most Popular'),

            Tables\Columns\TextColumn::make('season')
                ->label('Season')
                ->badge()
                ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : 'Auto'),
          	
            Tables\Columns\ToggleColumn::make('is_active'),
             Tables\Columns\ToggleColumn::make('show_book_no_button')
          		->label('Show Book Now Button'),
            Tables\Columns\TextColumn::make('slot')
                ->badge()
                ->color(fn ($state) => match ($state) {
                    'morning' => 'success',
                    'afternoon' => 'warning',
                    'evening' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('booking_amount')
                ->money('INR')
                ->sortable(),
            Tables\Columns\TextColumn::make('created_at')->dateTime('d M Y'),
        ])
        ->defaultSort('sort_order', 'asc')
        ->reorderable('sort_order')
        ->filters([
            // ... TUMHARE SARE FILTERS JAISE HAIN
            Tables\Filters\SelectFilter::make('trips')
                ->label('Filter by Trips/Categories')
                ->relationship('trips', 'heading')
                ->multiple()
                ->searchable()
                ->preload(),

            Tables\Filters\SelectFilter::make('destination_id')
                ->label('Destination')
                ->relationship('destination', 'name')
                ->searchable(),

            Tables\Filters\SelectFilter::make('season')
                ->label('Season')
                ->options([
                    'spring' => 'Spring',
                    'summer' => 'Summer',
                    'autumn' => 'Autumn',
                    'winter' => 'Winter',
                ]),
          
            Tables\Filters\SelectFilter::make('slug')
                ->label('Slug')
                ->options(
                    Packages::pluck('slug', 'slug')->toArray()
                )
                ->searchable(),
            
            Tables\Filters\Filter::make('slug_search')
                ->label('Search by Slug')
                ->form([
                    TextInput::make('slug')
                        ->label('Slug')
                        ->placeholder('Enter slug to search...'),
                ])
                ->query(function ($query, array $data) {
                    return $query->when(
                        $data['slug'],
                        fn($query, $slug) => $query->where('slug', 'like', "%{$slug}%"),
                    );
                }),
        ])
        ->actions([
            // ✅ SIRF YAHAN DUPLICATE BUTTON ADD KARO - PEEHLE WALE SAB BUTTONS RAHENGE
            Action::make('duplicate')
                ->label('Duplicate')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Duplicate Package')
                ->modalDescription('Are you sure you want to duplicate this package?')
                ->modalSubmitActionLabel('Yes, Duplicate')
                ->action(function ($record) {
                    self::duplicatePackage($record);
                }),
            
            // YAHAN SE NEEECHE SARE BUTTONS WAISE HI RAHENGE - KUCH MAT HATANA
            Action::make('moveUp')
                ->label('')
                ->icon('heroicon-o-arrow-up')
                ->color('success')
                ->action(function ($record) {
                    $currentOrder = $record->sort_order;
                    
                    $previousRecord = Packages::where('sort_order', '<', $currentOrder)
                        ->orderBy('sort_order', 'desc')
                        ->first();
                    
                    if ($previousRecord) {
                        $tempOrder = $previousRecord->sort_order;
                        $previousRecord->update(['sort_order' => $currentOrder]);
                        $record->update(['sort_order' => $tempOrder]);
                        
                        Notification::make()
                            ->title('Moved up successfully')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Already at the top')
                            ->warning()
                            ->send();
                    }
                })
                ->visible(fn ($record) => $record->sort_order > 0),
            
            Action::make('moveDown')
                ->label('')
                ->icon('heroicon-o-arrow-down')
                ->color('warning')
                ->action(function ($record) {
                    $currentOrder = $record->sort_order;
                    
                    $nextRecord = Packages::where('sort_order', '>', $currentOrder)
                        ->orderBy('sort_order', 'asc')
                        ->first();
                    
                    if ($nextRecord) {
                        $tempOrder = $nextRecord->sort_order;
                        $nextRecord->update(['sort_order' => $currentOrder]);
                        $record->update(['sort_order' => $tempOrder]);
                        
                        Notification::make()
                            ->title('Moved down successfully')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Already at the bottom')
                            ->warning()
                            ->send();
                    }
                }),
                
            ViewAction::make()->infolist([
                // ... TUMHARA PURANA INFOLIST
                Section::make('Package Info')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('sort_order')->label('Sort Order'),
                                TextEntry::make('title')->label('Package Title'),
                                TextEntry::make('slug')->label('Package Slug'),
                                
                                TextEntry::make('trips')
                                    ->label('Associated Trips/Categories')
                                    ->formatStateUsing(function ($record) {
                                        $trips = $record->trips;
                                        if ($trips->isEmpty()) {
                                            return 'No trips assigned';
                                        }
                                        return $trips->pluck('heading')->implode(', ');
                                    })
                                    ->badge()
                                    ->color('info'),
                                
                                TextEntry::make('duration')->label('Duration'),
                                TextEntry::make('starting_price')->label('Starting Price')->money('INR'),
                                TextEntry::make('pickup')->label('Pickup'),
                                TextEntry::make('drop')->label('Drop'),
                                TextEntry::make('age_group_min')->label('Min Age'),
                                TextEntry::make('age_group_max')->label('Max Age'),
                                IconEntry::make('is_active')->label('Active')->boolean(),
                                IconEntry::make('is_trending')->label('Trending')->boolean(),
                                ImageEntry::make('thumbnail')->label('Thumbnail'),
                                ImageEntry::make('banner')->label('Banner'),
                            ]),
                    ]),

                Section::make('Associated Trips Details')
                    ->schema([
                        RepeatableEntry::make('trips')
                            ->label('Trips/Categories')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('heading')->label('Trip Name'),
                                        TextEntry::make('slug')->label('Trip Slug'),
                                        TextEntry::make('pivot.status')->label('Mapping Status'),
                                        TextEntry::make('pivot.mapping_date')->label('Mapping Date')->date(),
                                        IconEntry::make('is_active')->label('Active')->boolean(),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->trips->isNotEmpty()),

                Section::make('Description')
                    ->schema([
                        TextEntry::make('description')->label('Description')->html()->columnSpanFull()
                    ]),
                Section::make('Itinerary')
                    ->schema([
                        RepeatableEntry::make('itinerary')
                            ->label('Itinerary')
                            ->schema([
                                TextEntry::make('heading')->label('Heading'),
                                TextEntry::make('content')->label('Content')->html(),
                            ])
                    ]),
                Section::make('Inclusion')
                    ->schema([
                        TextEntry::make('inclusion')->label('Inclusion')->html()->columnSpanFull()
                    ]),
                Section::make('Exclusion')
                    ->schema([
                        TextEntry::make('exclusion')->label('Exclusion')->html()->columnSpanFull()
                    ]),
                Section::make('Note')
                    ->schema([
                        TextEntry::make('note')->label('Note')->html()->columnSpanFull()
                    ]),
                Section::make('Things to Pack')
                    ->schema([
                        TextEntry::make('things_to_pack')->label('Things to Pack')->html()->columnSpanFull()
                    ]),
                Section::make('Active Costs')
                    ->schema([
                        RepeatableEntry::make('activeCosts')
                            ->label('Active Costs')
                            ->schema([
                                Grid::make(6)
                                    ->schema([
                                        TextEntry::make('activity')->label('Activity')->columnSpan(1),
                                        TextEntry::make('cost')->label('Cost')->money('INR', true)->columnSpan(1),
                                        TextEntry::make('discount_percent')->label('Discount %')->columnSpan(1),
                                        TextEntry::make('gst_percent')->label('GST %')->columnSpan(1),
                                        TextEntry::make('final_cost_excl')
                                            ->label('Cost (Excl. GST)')
                                            ->state(fn($record) => round(
                                                ($record->cost - ($record->cost * $record->discount_percent / 100))
                                            ))
                                            ->money('INR', true)
                                            ->columnSpan(1),
                                        TextEntry::make('final_cost_incl')
                                            ->label('Cost (Incl. GST)')
                                            ->state(fn($record) => round(
                                                ($record->cost - ($record->cost * $record->discount_percent / 100)) *
                                                    (1 + ($record->gst_percent / 100))
                                            ))
                                            ->money('INR', true)
                                            ->columnSpan(1),
                                    ]),
                            ]),
                    ]),

                Section::make('Package Dates')
                    ->schema([
                        RepeatableEntry::make('packageDates')
                            ->label('Package Dates')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('start_date')->label('Start')->date('d M Y')->columnSpan(1),
                                        TextEntry::make('end_date')->label('End')->date('d M Y')->columnSpan(1),
                                        TextEntry::make('status')->label('Status')->columnSpan(1),
                                        TextEntry::make('starting_price')->label('Starting Price')->money('INR', true)->columnSpan(1),
                                    ]),
                            ]),
                    ]),
            ]),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            // ... TUMHARE SARE BULK ACTIONS JAISE HAIN
            Tables\Actions\DeleteBulkAction::make(),
            Tables\Actions\BulkAction::make('reorder')
                ->label('Reorder by ID')
                ->action(function ($records) {
                    $sortOrder = 0;
                    foreach ($records as $record) {
                        $record->update(['sort_order' => $sortOrder]);
                        $sortOrder++;
                    }
                    Notification::make()
                        ->title('Reordered successfully')
                        ->success()
                        ->send();
                }),
        ])
        ->headerActions([
            // ... TUMHARE SARE HEADER ACTIONS JAISE HAIN
            Action::make('resetSortOrder')
                ->label('Reset Sort Order')
                ->color('danger')
                ->action(function () {
                    $packages = Packages::orderBy('id')->get();
                    $sortOrder = 0;
                    foreach ($packages as $package) {
                        $package->update(['sort_order' => $sortOrder]);
                        $sortOrder++;
                    }
                    Notification::make()
                        ->title('Sort order reset successfully')
                        ->success()
                        ->send();
                }),
        ]);
}

  
  
  // ✅ PackagesResource class ke END mein ye function add karo (last curly brace se pehle)

private static function duplicatePackage($originalPackage): void
{
    try {
        DB::beginTransaction();
        
        // Duplicate main package
        $newPackage = $originalPackage->replicate();
        $newPackage->title = $originalPackage->title . ' (Copy)';
        $newPackage->slug = $originalPackage->slug . '-copy-' . time();
        $newPackage->package_code = $originalPackage->package_code . '-copy-' . time();
        $newPackage->sort_order = Packages::max('sort_order') + 1;
        $newPackage->created_at = now();
        $newPackage->updated_at = now();
        
        // Copy thumbnail
        if ($originalPackage->thumbnail) {
            $newPackage->thumbnail = self::copyImageFile($originalPackage->thumbnail, 'package-thumbnail');
        }
        
        // Copy banner
        if ($originalPackage->banner) {
            $newPackage->banner = self::copyImageFile($originalPackage->banner, 'package-banner');
        }
        
        // Copy map_image
        if ($originalPackage->map_image) {
            $newPackage->map_image = self::copyImageFile($originalPackage->map_image, 'map_image');
        }
        
        $newPackage->save();
        
        // Copy gallery
        if ($originalPackage->gallery && is_array($originalPackage->gallery)) {
            $newGallery = [];
            foreach ($originalPackage->gallery as $galleryItem) {
                $newImagePath = null;
                if (!empty($galleryItem['image'])) {
                    $newImagePath = self::copyImageFile($galleryItem['image'], 'package-gallery');
                }
                $newGallery[] = [
                    'image' => $newImagePath,
                    'alt_text' => ($galleryItem['alt_text'] ?? '') . ' (Copy)'
                ];
            }
            $newPackage->gallery = $newGallery;
            $newPackage->save();
        }
        
        // Copy testimonials
        if ($originalPackage->testimonials && is_array($originalPackage->testimonials)) {
            $newTestimonials = [];
            foreach ($originalPackage->testimonials as $testimonial) {
                $newImagePath = null;
                if (!empty($testimonial['image'])) {
                    $newImagePath = self::copyImageFile($testimonial['image'], 'package-testimonials');
                }
                $newTestimonials[] = [
                    'name' => ($testimonial['name'] ?? '') . ' (Copy)',
                    'image' => $newImagePath,
                    'social' => $testimonial['social'] ?? false,
                    'star' => $testimonial['star'] ?? 5,
                    'text' => $testimonial['text'] ?? '',
                    'status' => $testimonial['status'] ?? true
                ];
            }
            $newPackage->testimonials = $newTestimonials;
            $newPackage->save();
        }
        
        // Copy FAQs
        if ($originalPackage->faqs && is_array($originalPackage->faqs)) {
            $newFaqs = [];
            foreach ($originalPackage->faqs as $faq) {
                $newFaqs[] = [
                    'question' => $faq['question'] ?? '',
                    'answer' => $faq['answer'] ?? ''
                ];
            }
            $newPackage->faqs = $newFaqs;
            $newPackage->save();
        }
        
        // Copy Itinerary
        if ($originalPackage->itinerary && is_array($originalPackage->itinerary)) {
            $newItinerary = [];
            foreach ($originalPackage->itinerary as $item) {
                $newItinerary[] = [
                    'heading' => $item['heading'] ?? '',
                    'content' => $item['content'] ?? ''
                ];
            }
            $newPackage->itinerary = $newItinerary;
            $newPackage->save();
        }
        
        // Copy relationships (trips)
        if ($originalPackage->trips) {
            $newPackage->trips()->attach($originalPackage->trips->pluck('id')->toArray());
        }
        
        // Copy active costs
        if ($originalPackage->activeCosts) {
            foreach ($originalPackage->activeCosts as $cost) {
                $newPackage->activeCosts()->create([
                    'activity' => $cost->activity,
                    'cost' => $cost->cost,
                    'discount_percent' => $cost->discount_percent,
                    'gst_percent' => $cost->gst_percent,
                ]);
            }
        }
        
        // Copy package dates
        if ($originalPackage->packageDates) {
            foreach ($originalPackage->packageDates as $date) {
                $newPackage->packageDates()->create([
                    'start_date' => $date->start_date,
                    'end_date' => $date->end_date,
                    'status' => 'inactive',
                    'starting_price' => $date->starting_price,
                ]);
            }
        }
        
        DB::commit();
        
        Notification::make()
            ->success()
            ->title('Package Duplicated Successfully!')
            ->body("New package: {$newPackage->title}")
            ->send();
        
        // Redirect to edit page
        redirect()->route('filament.admin.resources.packages.edit', $newPackage);
        
    } catch (\Exception $e) {
        DB::rollBack();
        
        Notification::make()
            ->danger()
            ->title('Duplication Failed!')
            ->body($e->getMessage())
            ->send();
            
        \Log::error('Package duplication failed', [
            'original_id' => $originalPackage->id,
            'error' => $e->getMessage()
        ]);
    }
}

// ✅ Helper function to copy image files
private static function copyImageFile($oldPath, $directory)
{
    if (!$oldPath || !Storage::disk('public')->exists($oldPath)) {
        return null;
    }
    
    $extension = pathinfo($oldPath, PATHINFO_EXTENSION);
    $newFilename = 'copy_' . time() . '_' . uniqid() . '.' . $extension;
    $newPath = $directory . '/' . $newFilename;
    
    Storage::disk('public')->copy($oldPath, $newPath);
    
    return $newPath;
}
    public static function getRelations(): array
    {
        return [
            ActiveCostsRelationManager::class,
            PackageDatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPackages::route('/'),
            'create' => Pages\CreatePackages::route('/create'),
            'edit'   => Pages\EditPackages::route('/{record}/edit'),
        ];
    }

    public static function canCreateAnother(): bool
    {
        return false;
    }
}