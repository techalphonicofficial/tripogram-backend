<?php

namespace App\Filament\Resources\MainPagesResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Filament\Forms\Components\Select;
use Filament\Forms\Set;
use App\Models\Trips;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Support\Facades\Storage;

class PageSectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';
    protected static ?string $title = 'Page Sections';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            TextInput::make('section_key')
                ->label('Section Key')
                ->required()
                ->columnSpanFull()
                ->rule('regex:/^[a-z_]+$/')
                ->helperText('Only small letters and underscores allowed. Example: about_hero_section')
                ->live(onBlur: true)
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: function (\Illuminate\Validation\Rules\Unique $rule, callable $get) {
                        return $rule->where('page_id', $this->getOwnerRecord()->id);
                    }
                )
                ->disabledOn('edit'),

            Builder::make('section')
                ->label('Page Sections')
                ->disableItemMovement()
                  //  ->disableItemCreation()
                    ->disableItemDeletion()
                ->blocks([

                    Block::make('Text')
                        ->label('Text')
                        ->schema([
                            TextInput::make('Text')
                                ->label('Text')
                                ->required(),
                        ]),

                    Block::make('status')
                        ->label('Status')
                        ->schema([
                            ToggleButtons::make('status')
                                ->boolean()
                                ->grouped(),
                        ]),

                    Block::make('content')
                        ->label('Content')
                        ->schema([
                            Textarea::make('content')
                                ->label('Content Text')
                                ->rows(4),
                        ]),

                    Block::make('rich_text')
                        ->label('Rich Text')
                        ->schema([
                            RichEditor::make('rich_text')
                                ->label('Rich Text Editor'),
                        ]),

                    Block::make('media')
                        ->label('Banner Media Settings')
                        ->schema([
                            ToggleButtons::make('show_type')
                                ->label('Which media do you want to show?')
                                ->options([
                                    'image' => 'Show Image',
                                    'video' => 'Show Video',
                                    'none' => 'none',
                                ])
                                ->inline()
                                ->required()
                                ->default('image')
                                ->columnSpanFull(),

                            FileUpload::make('image')
                                ->label('Upload Image')
                                ->image()
                                ->directory('page-sections')
                                ->helperText('Upload banner image')
                                ->deleteUploadedFileUsing(function ($file, $record, $state, $get) {
                                    if ($record && $record->image && $record->image !== $state) {
                                        Storage::disk('public')->delete($record->image);
                                    }
                                    return true;
                                }),

                            FileUpload::make('video')
                                ->label('Upload Video')
                                ->directory('page-sections/videos')
                                ->acceptedFileTypes(['video/mp4', 'video/avi', 'video/mov', 'video/mkv'])
                                ->maxSize(51200)
                                ->helperText('Upload banner video')
                                ->columnSpanFull()
                                ->deleteUploadedFileUsing(function ($file, $record, $state, $get) {
                                    if ($record && $record->video && $record->video !== $state) {
                                        Storage::disk('public')->delete($record->video);
                                    }
                                    return true;
                                }),
                        ]),

                    Block::make('popup')
                        ->label('Popup')
                        ->schema([
                            ToggleButtons::make('status')
                                ->label('Status')
                                ->boolean()
                                ->grouped()
                                ->default(false)
                                ->required(),

                            FileUpload::make('image')
                                ->label('Popup Image')
                                ->image()
                                ->directory('page-sections/popups')
                                ->required()
                                ->deleteUploadedFileUsing(function ($file, $record, $state, $get) {
                                    if ($record && $record->image && $record->image !== $state) {
                                        Storage::disk('public')->delete($record->image);
                                    }

                                    return true;
                                }),
                        ])
                        ->columns(2),

                    Block::make('image')
                        ->label('Image')
                        ->schema([
                            FileUpload::make('image')
                                ->label('Upload Image')
                                ->image()
                                ->directory('page-sections')
                                ->deleteUploadedFileUsing(function ($file, $record, $state, $get) {
                                    if ($record && $record->image && $record->image !== $state) {
                                        Storage::disk('public')->delete($record->image);
                                    }
                                    return true;
                                }),
                        ]),

                    Block::make('video')
                        ->label('Video')
                        ->schema([
                            FileUpload::make('video')
                                ->label('Upload Video')
                                ->directory('page-sections/videos')
                                ->acceptedFileTypes(['video/mp4', 'video/avi', 'video/mov', 'video/mkv'])
                                ->maxSize(1000000)
                                ->columnSpanFull()
                                ->deleteUploadedFileUsing(function ($file, $record, $state, $get) {
                                    if ($record && $record->video && $record->video !== $state) {
                                        Storage::disk('public')->delete($record->video);
                                    }
                                    return true;
                                }),
                        ]),

                    Block::make('url')
                        ->label('Url')
                        ->schema([
                            TextInput::make('url')
                                ->label('Url')
                                ->url(),
                        ]),

                    Block::make('button')
                        ->label('Button')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('button_label')
                                    ->label('Button Label'),

                                TextInput::make('button_link')
                                    ->label('Button Link')
                                    ->rule('regex:/^(https?:\/\/|mailto:|tel:).+/i')
                                    ->placeholder('https://example.com or mailto:info@example.com or tel:+911234567890')
                                    ->helperText('Accepted: http(s), mailto, tel'),
                            ]),
                        ]),

                    Block::make('gallery')
                        ->label('Image Gallery')
                        ->schema([
                            FileUpload::make('gallery')
                                ->multiple()
                                ->image()
                                ->directory('page-sections/gallery')
                                ->panelLayout('grid')
                                ->reorderable()
                                ->columnSpanFull()
                                ->deleteUploadedFileUsing(function ($files, $record, $state, $get) {
                                    if ($record && $record->gallery) {
                                        $oldFiles = is_array($record->gallery) ? $record->gallery : json_decode($record->gallery, true);
                                        $newFiles = is_array($state) ? $state : json_decode($state, true);
                                        
                                        if ($oldFiles && $newFiles) {
                                            $deletedFiles = array_diff($oldFiles, $newFiles);
                                            foreach ($deletedFiles as $file) {
                                                Storage::disk('public')->delete($file);
                                            }
                                        }
                                    }
                                    return true;
                                }),
                        ]),

                    Block::make('testimonials')
                        ->label('Testimonials')
                        ->schema([
                            Repeater::make('testimonials_items')
                                ->label('Testimonials')
                                ->schema([
                                    FileUpload::make('image')
                                        ->label('Profile Image')
                                        ->image()
                                        ->required()
                                        ->directory('page-sections')
                                        ->deleteUploadedFileUsing(function ($file, $record, $state, $get, $set, $component) {
                                            $itemIndex = $component->getContainer()->getParentComponent()->getItemKey();
                                            
                                            if ($record && $record->testimonials_items && isset($record->testimonials_items[$itemIndex]['image'])) {
                                                $oldImage = $record->testimonials_items[$itemIndex]['image'];
                                                if ($oldImage && $oldImage !== $state) {
                                                    Storage::disk('public')->delete($oldImage);
                                                }
                                            }
                                            return true;
                                        }),

                                    TextInput::make('name')
                                        ->label('Name')
                                        ->required(),

                                    TextInput::make('type')
                                        ->label('Type')
                                        ->required(),

                                    TextInput::make('rating')
                                        ->label('Rating')
                                        ->numeric()
                                        ->minValue(1)
                                        ->maxValue(5)
                                        ->step(1)
                                        ->default(5)
                                        ->required(),

                                    Textarea::make('content')
                                        ->label('Review Content')
                                        ->required()
                                        ->rows(4),
                                ])
                                ->collapsible()
                                ->reorderable()
                                ->minItems(1)
                                ->cloneable()
                                ->columnSpanFull(),
                        ]),

                    Block::make('menu')
                        ->label('Menus')
                        ->schema([
                            Repeater::make('trip_items')
                                ->label('Trips')
                                ->schema([
                                    Select::make('slug')
                                        ->label('Select Trip')
                                        ->options(
                                            Trips::query()
                                                ->pluck('heading', 'slug')
                                                ->toArray()
                                        )
                                        ->searchable()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            if ($state) {
                                                $trip = Trips::where('slug', $state)->first();
                                                $set('heading', $trip?->heading);
                                            } else {
                                                $set('heading', null);
                                            }
                                        })
                                        ->required(),

                                    TextInput::make('heading')
                                        ->label('Trip Heading')
                                        ->disabled()
                                        ->dehydrated()
                                        ->required(),
                                ])
                                ->reorderable()
                                ->cloneable()
                                ->columnSpanFull(),
                        ]),

                    Block::make('repeater_section')
                        ->label('Repeater Section')
                        ->schema([
                            Builder::make('section')
                                ->disableItemMovement()
                                ->blocks([

                                    Block::make('Text')
                                        ->label('Text')
                                        ->schema([
                                            TextInput::make('Text')
                                                ->label('Text')
                                                ->required(),
                                        ]),

                                    Block::make('content')
                                        ->label('Content')
                                        ->schema([
                                            Textarea::make('content')
                                                ->label('Content Text')
                                                ->rows(4),
                                        ]),

                                    Block::make('rich_text')
                                        ->label('Rich Text')
                                        ->schema([
                                            RichEditor::make('rich_text')
                                                ->label('Rich Text Editor'),
                                        ]),

                                    Block::make('image')
                                        ->label('Image')
                                        ->schema([
                                            FileUpload::make('image')
                                                ->label('Upload Image')
                                                ->image()
                                                ->directory('page-sections')
                                                ->deleteUploadedFileUsing(function ($file, $record, $state, $get) {
                                                    if ($record && $record->image && $record->image !== $state) {
                                                        Storage::disk('public')->delete($record->image);
                                                    }
                                                    return true;
                                                }),
                                        ]),

                                    Block::make('video')
                                        ->label('Video')
                                        ->schema([
                                            FileUpload::make('video')
                                                ->label('Upload Video')
                                                ->directory('page-sections/videos')
                                                ->acceptedFileTypes(['video/mp4', 'video/avi', 'video/mov', 'video/mkv'])
                                                ->maxSize(5120)
                                                ->columnSpanFull()
                                                ->deleteUploadedFileUsing(function ($file, $record, $state, $get) {
                                                    if ($record && $record->video && $record->video !== $state) {
                                                        Storage::disk('public')->delete($record->video);
                                                    }
                                                    return true;
                                                }),
                                        ]),

                                    Block::make('button')
                                        ->label('Button')
                                        ->schema([
                                            Grid::make(2)->schema([
                                                TextInput::make('button_label')
                                                    ->label('Button Label'),

                                                TextInput::make('button_link')
                                                    ->label('Button Link')
                                                    ->url(),
                                            ]),
                                        ]),

                                    Block::make('gallery')
                                        ->label('Image Gallery')
                                        ->schema([
                                            FileUpload::make('gallery')
                                                ->multiple()
                                                ->image()
                                                ->directory('page-sections/gallery')
                                                ->panelLayout('grid')
                                                ->reorderable()
                                                ->columnSpanFull()
                                                ->deleteUploadedFileUsing(function ($files, $record, $state, $get) {
                                                    if ($record && $record->gallery) {
                                                        $oldFiles = is_array($record->gallery) ? $record->gallery : json_decode($record->gallery, true);
                                                        $newFiles = is_array($state) ? $state : json_decode($state, true);
                                                        
                                                        if ($oldFiles && $newFiles) {
                                                            $deletedFiles = array_diff($oldFiles, $newFiles);
                                                            foreach ($deletedFiles as $file) {
                                                                Storage::disk('public')->delete($file);
                                                            }
                                                        }
                                                    }
                                                    return true;
                                                }),
                                        ]),

                                    Block::make('repeater_section')
                                        ->label('Repeater Section')
                                        ->schema([
                                            \Filament\Forms\Components\Repeater::make('items')
                                                ->label('Repeater Items')
                                                ->schema([])
                                                ->collapsible()
                                                ->reorderable()
                                                ->minItems(1)
                                                ->columnSpanFull(),
                                        ])
                                ])
                        ])
                ])
                ->columnSpanFull(),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('section_key')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d M Y h:i A'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(function ($record) {
                        // Delete all files associated with this section when section is deleted
                        $this->deleteSectionFiles($record);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->after(function ($records) {
                        foreach ($records as $record) {
                            $this->deleteSectionFiles($record);
                        }
                    }),
            ])
            ->searchPlaceholder('Search sections...')
            ->paginated([15, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(15);
    }

    /**
     * Delete all files associated with a section
     */
    private function deleteSectionFiles($record): void
    {
        if (!$record || !$record->section) {
            return;
        }

        $sectionData = $record->section;
        
        // Decode if it's JSON string
        if (is_string($sectionData)) {
            $sectionData = json_decode($sectionData, true);
        }

        if (!is_array($sectionData)) {
            return;
        }

        $this->recursiveDeleteFiles($sectionData);
    }

    /**
     * Recursively delete files from section data
     */
    private function recursiveDeleteFiles($data): void
    {
        if (!is_array($data)) {
            return;
        }

        foreach ($data as $key => $value) {
            // Handle single image
            if ($key === 'image' && $value && is_string($value)) {
                Storage::disk('public')->delete($value);
            }
            
            // Handle single video
            if ($key === 'video' && $value && is_string($value)) {
                Storage::disk('public')->delete($value);
            }
            
            // Handle gallery (array of images)
            if ($key === 'gallery' && is_array($value)) {
                foreach ($value as $image) {
                    if (is_string($image)) {
                        Storage::disk('public')->delete($image);
                    }
                }
            }
            
            // Handle testimonials items
            if ($key === 'testimonials_items' && is_array($value)) {
                foreach ($value as $testimonial) {
                    if (isset($testimonial['image']) && $testimonial['image']) {
                        Storage::disk('public')->delete($testimonial['image']);
                    }
                }
            }
            
            // Handle nested data (for repeater_section or other nested structures)
            if (is_array($value)) {
                $this->recursiveDeleteFiles($value);
            }
        }
    }
}