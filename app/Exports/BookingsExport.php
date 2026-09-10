<?php

namespace App\Exports;

use App\Models\Bookings;
use App\Models\PaymentLink;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Carbon\Carbon;

class BookingsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected $filters;
    protected $selectedIds;

    public function __construct($filters = [], $selectedIds = [])
    {
        $this->filters = $filters;
        $this->selectedIds = $selectedIds;
    }

    public function collection()
    {
        $query = Bookings::query();

        // Filter by selected IDs
        if (!empty($this->selectedIds)) {
            $query->whereIn('id', $this->selectedIds);
        }

        // Apply filters
        if (!empty($this->filters['package_id'])) {
            $query->where('package_id', $this->filters['package_id']);
        }

        if (!empty($this->filters['assign_to'])) {
            $query->where('assign_to', $this->filters['assign_to']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['payment_mode'])) {
            $query->where('payment_mode', $this->filters['payment_mode']);
        }

        if (!empty($this->filters['start_date_from'])) {
            $query->whereDate('start_date', '>=', $this->filters['start_date_from']);
        }

        if (!empty($this->filters['start_date_to'])) {
            $query->whereDate('start_date', '<=', $this->filters['start_date_to']);
        }

        if (!empty($this->filters['created_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['created_from']);
        }

        if (!empty($this->filters['created_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['created_to']);
        }

        if (!empty($this->filters['min_amount'])) {
            $query->where('final_amount', '>=', $this->filters['min_amount']);
        }

        if (!empty($this->filters['max_amount'])) {
            $query->where('final_amount', '<=', $this->filters['max_amount']);
        }

        if (isset($this->filters['due_amount'])) {
            if ($this->filters['due_amount'] === 'true') {
                $query->where('due_amount', '>', 0);
            } elseif ($this->filters['due_amount'] === 'false') {
                $query->where('due_amount', 0);
            }
        }

        return $query->orderBy('id', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Booking ID',
            'Full Name',
            'Email',
            'Phone',
            'Package',
            'Duration',
            'Pickup',
            'Drop',
            'M/F/O Count',
            'Booking Link',
            'Activities',
            'Amount Before Coupon',
            'Final Amount',
            'Coupon Discount',
            'Applied Coupons',
            'Payment Link URL',
            'Payment Link Amount',
            'Payment Link Status',
            'Paid Amount',
            'Due Amount',
            'Payment Mode',
            'Payment Type',
            'Status',
            'Assigned To',
            'Start Date',
            'End Date',
            'Created At',
            'Updated At',
        ];
    }

    public function map($booking): array
    {
        // Get gender count
        $genderCount = $this->getGenderCount($booking);

        // Get payment link info
        $paymentLink = PaymentLink::where('booking_id', $booking->booking_id)->first();

        // Get activities
        $activities = collect($booking->active_cost)->pluck('activity')->implode(', ');

        // Get applied coupons
        $coupons = $booking->applied_coupons;
        if (is_string($coupons)) {
            $coupons = json_decode($coupons, true);
        }
        $couponString = is_array($coupons) ? implode(', ', $coupons) : '';

        // Get assigned user name
        $assignedTo = $booking->assignedTo ? $booking->assignedTo->name : 'Unassigned';

        // Calculate amount before coupon
        $amountBeforeCoupon = ($booking->final_amount ?? 0) + ($booking->total_coupon_discount ?? 0);

        return [
            $booking->id,
            $booking->booking_id,
            $booking->full_name,
            $booking->email,
            $booking->phone,
            $booking->package_title,
            $booking->duration,
            $booking->pickup,
            $booking->drop,
            $genderCount,
            'https://tripogramclub.com/booking-detail?id=' . $booking->booking_token,
            $activities ?: '-',
            $amountBeforeCoupon,
            $booking->final_amount,
            $booking->total_coupon_discount,
            $couponString ?: '-',
            $paymentLink?->payment_link,
            $paymentLink?->amount,
            $paymentLink?->status ?? 'No Link',
            $booking->paid_amount,
            $booking->due_amount,
            $booking->payment_mode,
            $booking->payment_type,
            $booking->status,
            $assignedTo,
            $booking->start_date ? Carbon::parse($booking->start_date)->format('d-m-Y') : '',
            $booking->end_date ? Carbon::parse($booking->end_date)->format('d-m-Y') : '',
            $booking->created_at ? Carbon::parse($booking->created_at)->format('d-m-Y H:i:s') : '',
            $booking->updated_at ? Carbon::parse($booking->updated_at)->format('d-m-Y H:i:s') : '',
        ];
    }

    private function getGenderCount($booking)
    {
        $data = $booking->data_get;
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        if (!is_array($data)) {
            return '0/0/0';
        }

        $male = 0;
        $female = 0;
        $other = 0;
        foreach ($data as $sharingType) {
            if (!empty($sharingType['members'])) {
                foreach ($sharingType['members'] as $member) {
                    $gender = strtolower($member['gender'] ?? '');
                    if ($gender === 'male') {
                        $male++;
                    } elseif ($gender === 'female') {
                        $female++;
                    } elseif ($gender === 'other') {
                        $other++;
                    }
                }
            }
        }
        return "M:{$male} F:{$female} O:{$other}";
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF2E6B8E']]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Auto-size columns
                foreach (range('A', 'Z') as $col) {
                    $event->sheet->getDelegate()->getColumnDimension($col)->setAutoSize(true);
                }

                // Freeze the first row
                $event->sheet->getDelegate()->freezePane('A2');

                // Add filters
                $event->sheet->getDelegate()->setAutoFilter('A1:AC1');
            },
        ];
    }
}
