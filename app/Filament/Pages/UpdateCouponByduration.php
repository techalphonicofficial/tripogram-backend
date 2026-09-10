<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use App\Models\Packages;
use Illuminate\Support\Facades\DB;

class UpdateCouponByDuration extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static string $view = 'filament.pages.update-coupon-by-duration';
    protected static ?string $navigationLabel = 'Update Coupon by Duration';
    protected static ?string $title = 'Update Coupon by Duration';

    public $duration = null;
    public $coupon_amount = null;
    public $packageCount = null;  // ✅ This is accessible in blade

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('duration')
                    ->label('Select Duration')
                    ->options(
                        Packages::select('duration')
                            ->distinct()
                            ->pluck('duration', 'duration')
                            ->toArray()
                    )
                    ->reactive()
                    ->afterStateUpdated(function ($state) {
                        $this->duration = $state;
                        $this->updatePackageCount();
                    })
                    ->required(),

                Forms\Components\TextInput::make('coupon_amount')
                    ->label('Coupon Amount')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->reactive()
                    ->afterStateUpdated(function ($state) {
                        $this->coupon_amount = $state;
                        // No need to update count for coupon amount change
                    }),
            ]);
    }

    public function updatePackageCount()
    {
        if ($this->duration) {
            $this->packageCount = Packages::where('duration', $this->duration)->count();
        } else {
            $this->packageCount = null;
        }
    }

    public function submit()
    {
        $data = $this->form->getState();

        // Double check the count before updating
        $count = Packages::where('duration', $data['duration'])->count();

        if ($count === 0) {
            Notification::make()
                ->title('No packages found for this duration!')
                ->danger()
                ->send();
            return;
        }

        DB::beginTransaction();
        
        try {
            Packages::where('duration', $data['duration'])
                ->update([
                    'coupon_amount' => $data['coupon_amount'],
                ]);

            DB::commit();

            Notification::make()
                ->title("✅ {$count} Packages Updated Successfully!")
                ->body("Duration: {$data['duration']}\nCoupon Amount: {$data['coupon_amount']}")
                ->success()
                ->send();

            // Reset form after successful update
            $this->form->fill();
            $this->duration = null;
            $this->coupon_amount = null;
            $this->packageCount = null;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Error updating packages!')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}