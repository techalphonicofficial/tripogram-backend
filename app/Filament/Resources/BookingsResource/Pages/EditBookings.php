<?php

namespace App\Filament\Resources\BookingsResource\Pages;

use App\Filament\Resources\BookingsResource;
use Filament\Resources\Pages\EditRecord;
use App\Models\Packages;
use Carbon\Carbon;

class EditBookings extends EditRecord
{
    protected static string $resource = BookingsResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
      
        // ✅ Handle payment_transactions
        if (isset($data['payment_transactions'])) {
            if (is_array($data['payment_transactions']) && count($data['payment_transactions']) > 0) {
                $data['payment_transactions'] = $data['payment_transactions'];
            } else {
                $data['payment_transactions'] = null;
            }
        } else {
            $data['payment_transactions'] = null;
        }

        // ✅ Handle active_cost
        if (isset($data['active_cost'])) {
            if (is_array($data['active_cost'])) {
                $data['active_cost'] = $data['active_cost'];
            } else {
                $data['active_cost'] = [];
            }
        } else {
            $data['active_cost'] = [];
        }

        // ✅ Handle payment_type
        if ($data['payment_mode'] === 'online') {
            if (isset($data['paid_amount']) && isset($data['final_amount'])) {
                $paidAmount = (float)$data['paid_amount'];
                $finalAmount = (float)$data['final_amount'];

                if ($paidAmount >= $finalAmount) {
                    $data['payment_type'] = 'full';
                } elseif ($paidAmount >= ($finalAmount / 2)) {
                    $data['payment_type'] = 'half';
                } else {
                    $data['payment_type'] = 'partial';
                }
            }
        }

      return $data;
    }

    protected function afterSave(): void
    {
        $booking = $this->record;

        // only calculate if confirmed
        if ($booking->status !== 'confirmed') {
            return;
        }

        // Package and calculations - WhatsApp call ke liye data prepare hai
        // Lekin WhatsApp API call NAHI karenge
        
        $package = Packages::where('id', $booking->package_id)->first();

        // Prepare active cost data 
        $activeCostArray = $booking->active_cost;
        if (is_string($activeCostArray)) {
            $activeCostArray = json_decode($activeCostArray, true);
        }

        // Calculate totals
        $gstTotal = 0;
        $subtotalWithoutGST = 0;
        $activeCostData = '';

        if (!empty($activeCostArray) && is_array($activeCostArray)) {
            $items = [];
            foreach ($activeCostArray as $item) {
                $activityName = $item['activity'] ?? 'Unknown';
                $cost = (float)($item['cost'] ?? 0);
                $quantity = (float)($item['quantity'] ?? 1);
                $totalWithDiscount = (float)($item['total_with_discount'] ?? 0);
                $totalWithGST = (float)($item['total_with_discount_and_gst'] ?? 0);
                
                $gstAmount = $totalWithGST - $totalWithDiscount;
                $gstTotal += $gstAmount;
                $subtotalWithoutGST += $totalWithDiscount;
                
                $items[] = "{$activityName}: ₹" . number_format($cost, 2) . " × {$quantity} = ₹" . number_format($totalWithDiscount, 2);
            }
            $activeCostData = implode(' | ', $items);
        } else {
            $activeCostData = 'No activities selected';
        }

        // Format start date
        $startDate = 'Date not set';
        if ($booking->start_date) {
            $dateStr = (string)$booking->start_date;
            if (!str_contains($dateStr, '1969') && !str_contains($dateStr, '1970-01-01')) {
                try {
                    $startDate = Carbon::parse($booking->start_date)
                        ->timezone('Asia/Kolkata')
                        ->format('d M Y');
                } catch (\Exception $e) {
                    $startDate = 'Date not set';
                }
            }
        }

        // ❌ WhatsApp API call HATAYA - comment out kiya ya delete
        // Sirf notification dikhao
        \Filament\Notifications\Notification::make()
            ->success()
            ->title('Booking updated successfully')
            ->send();
    }
}