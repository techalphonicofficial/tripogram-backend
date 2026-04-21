<?php

namespace App\Filament\Resources\BookingsResource\Pages;

use App\Filament\Resources\BookingsResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Http;
use App\Models\Packages;
use Carbon\Carbon;

class EditBookings extends EditRecord
{
    protected static string $resource = BookingsResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // ✅ Handle payment_transactions (NO json_encode)
        if (isset($data['payment_transactions'])) {
            if (is_array($data['payment_transactions']) && count($data['payment_transactions']) > 0) {
                $data['payment_transactions'] = $data['payment_transactions'];
            } else {
                $data['payment_transactions'] = null;
            }
        } else {
            $data['payment_transactions'] = null;
        }

        // ✅ Handle active_cost (NO json_encode)
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

        // only send if confirmed
        if ($booking->status !== 'confirmed') {
            return;
        }

        try {
            \Log::info('WhatsApp API START (Edit Booking)', [
                'booking_id' => $booking->booking_id,
                'phone' => $booking->phone
            ]);

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
                    
                    // Calculate GST amount
                    $gstAmount = $totalWithGST - $totalWithDiscount;
                    $gstTotal += $gstAmount;
                    $subtotalWithoutGST += $totalWithDiscount;
                    
                    // Format without GST percentage in between
                    $items[] = "{$activityName}: ₹" . number_format($cost, 2) . " × {$quantity} = ₹" . number_format($totalWithDiscount, 2);
                }
                $activeCostData = implode(' | ', $items);
            } else {
                $activeCostData = 'No activities selected';
            }

            // Format start date - FIX for 1969/1970 issue
            $startDate = 'Date not set';
            if ($booking->start_date) {
                $dateStr = (string)$booking->start_date;
                // Check if it's a valid date (not 1969 or 1970)
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

            $response = Http::post('https://api.sendinai.com/sender', [
                "token" => "Hn8OQb2zZwDGvdhjwgHrChbit3QqQFyrjLPKkkto475bac3e",
                "phone" => $booking->phone,
                "template_name" => "booking_confirmation_enlivetrips",
                "template_language" => "EN_US",
                "text1" => $booking->full_name,
                "text2" => "https://www.enlivetrips.com/booking-detail?id=" . $booking->booking_token,
                "text3" => $booking->booking_id,
                "text4" => $package->title,
                "text5" => $startDate,
                "text6" => $activeCostData,
                "text7" => "Subtotal: ₹" . number_format($subtotalWithoutGST, 2) . " | Total GST: ₹" . number_format($gstTotal, 2) . " (5%) | Grand Total: ₹" . number_format($booking->final_amount, 2),
                "text8" => "Paid: ₹" . number_format($booking->paid_amount, 2),
                "text9" => "Due: ₹" . number_format($booking->due_amount, 2),
                "text10" => "https://www.enlivetrips.com/terms"
            ]);

            \Log::info('WhatsApp API RESPONSE (Edit)', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                \Log::info('WhatsApp sent successfully for booking: ' . $booking->booking_id);
            } else {
                \Log::error('WhatsApp failed for booking: ' . $booking->booking_id . ' - ' . $response->body());
            }

        } catch (\Throwable $e) {
            \Log::error('WhatsApp API FAILED (Edit)', [
                'error' => $e->getMessage(),
                'booking_id' => $booking->booking_id
            ]);
        }

        \Filament\Notifications\Notification::make()
            ->success()
            ->title('Booking updated successfully')
            ->send();
    }
}