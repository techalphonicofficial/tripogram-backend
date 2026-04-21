<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Bookings;
use App\Models\Packages;
use App\Models\PackageDates;
use App\Models\PackageVehicle;
use App\Models\Seats;
use App\Models\Vehicle;
use Filament\Forms;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\DB;
use App\Models\Role;
// use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmation;
use Illuminate\Support\Facades\Mail;

class SeatAllotment extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.seat-allotment';
    protected static ?string $navigationGroup = 'Vehicles Management';
    protected static ?int $navigationSort = 2;
    
    
       public static function shouldRegisterNavigation(): bool
    {


        $user = Auth::user();
// dd($user);
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
        
        
        // return $rolePermissionIds;

              
        
        $permissionResources = DB::table('permissions')
            ->whereIn('id', $rolePermissionIds)
            ->pluck('resource')
            ->toArray();




        
      if (!in_array('seat-allotment', $permissionResources)) {
         return false;
        }

       

        return true;
    
        // return false;
        // dd('sd');
        return auth()->user()?->role === 'admin';

    }

public $captainSeats = [];

    public $seatGenders = [];
    public $package_id;
    public $date;
    public $selectedVehicleId;
    public $package;
    public $bookings;
    public $bookingsWithSeats;
    public $selectedSeats = [];
    public $activeBookings = [];
    
    // For gender modal
    public $showGenderModal = false;
    public $selectedSeatForGender;
    public $selectedGender = 'male';
    
    // For passenger selection modal
    public $showPassengerModal = false;
    public $selectedBookingForPassengers = null;
    public $bookingPassengers = [];
    public $passengerToSeatMap = [];
    public $availableSeatsForBooking = [];
    
    // For super header pending bookings
    public $showPendingBookingsModal = false;
    public $allPendingBookings = [];
    public $totalPendingCount = 0;
    
    // NEW: For search and filtering
    public $searchTerm = '';
    public $filteredPendingBookings = [];
    public $filterStatus = ''; // Add this line

    public function mount(): void
    {
        $this->bookings = collect();
        $this->bookingsWithSeats = collect();
        $this->loadAllPendingBookings(); // Load all pending bookings on mount
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('package_id')
                ->label('Select Package')
                ->options(Packages::pluck('title', 'id'))
                ->reactive()
                ->afterStateUpdated(fn() => $this->date = null),

            Forms\Components\Select::make('date')
                ->label('Select Date')
                ->options(function ($get) {
                    if (!$get('package_id')) return [];

                    return PackageDates::where('package_id', $get('package_id'))
                        ->pluck('start_date', 'start_date')
                        ->toArray();
                })
                ->reactive()
                ->afterStateUpdated(fn() => $this->loadBookings()),

            Forms\Components\Select::make('selectedVehicleId')
                ->label('Select Vehicle')
                ->options(
                    \App\Models\Vehicle::all()->mapWithKeys(function ($vehicle) {
                        return [
                            $vehicle->id => "Type: {$vehicle->vehicle_type} / Seats: {$vehicle->vehicle_seats} / Row Type: {$vehicle->vehicle_row}"
                        ];
                    })->toArray()
                )
                ->searchable()
                ->required(),
        ];
    }

    // NEW: Send booking email

public function toggleDirectCaptain($seatId, $seatNo)
{
    $seat = Seats::find($seatId);
    // dd($seat);
    if (!$seat) return;
    
    // Toggle captain status
    $newStatus = 1;
    if($seat->is_captain == 1){
$newStatus = 0; 
    }
    
    
    $data = $seat->update([
        'is_captain' => $newStatus
    ]);

//    dd($data); 
    $status = $newStatus ? 'assigned as' : 'removed from';
    
    Notification::make()
        ->title("Seat #{$seatNo} {$status} Captain")
        ->success()
        ->send();
    
    $this->loadBookings();
}
   public function sendBookingEmail($bookingId)
{
    $booking = Bookings::find($bookingId);
    
    if (!$booking) {
        Notification::make()
            ->title('Booking not found')
            ->danger()
            ->send();
        return;
    }
    
    // Generate token if not exists
    if (empty($booking->booking_token)) {
        $booking->booking_token = $this->generateBookingToken();
        $booking->save();
    }
    
    // Check if email exists
    if (empty($booking->email)) {
        Notification::make()
            ->title('⚠️ No email address found for this booking')
            ->warning()
            ->send();
        return;
    }
    
    try {
        // Send email
        Mail::to($booking->email)->send(new BookingConfirmation($booking));
        
        // Update last email sent time (if you have this column)
        // $booking->last_email_sent = now();
        // $booking->save();
        
        // Success notification with details
        Notification::make()
            ->title('📧 Email Sent Successfully')
            ->body('To: ' . $booking->email . "\nToken: " . $booking->booking_token)
            ->success()
            ->send();
            
        // Optional: Log email sent
        \Log::info('Booking confirmation email sent', [
            'booking_id' => $booking->id,
            'email' => $booking->email,
            'token' => $booking->booking_token
        ]);
            
    } catch (\Exception $e) {
        // Log error
        \Log::error('Failed to send booking email', [
            'booking_id' => $booking->id,
            'error' => $e->getMessage()
        ]);
        
        // Error notification
        Notification::make()
            ->title('❌ Email Sending Failed')
            ->body('Error: ' . $e->getMessage())
            ->danger()
            ->send();
    }
}

    // NEW: Generate booking token
    private function generateBookingToken()
    {
        return 'BK-' . strtoupper(uniqid()) . '-' . bin2hex(random_bytes(4));
    }

    // NEW: Filter bookings based on search term and status
    public function updatedSearchTerm()
    {
        $this->filterBookings();
    }

    public function updatedFilterStatus()
    {
        $this->filterBookings();
    }

    // NEW: Filter bookings method
    // public function filterBookings()
    // {
    //     $bookings = collect($this->allPendingBookings);
        
    //     // Apply search filter
    //     if (!empty($this->searchTerm)) {
    //         $term = strtolower($this->searchTerm);
    //         $bookings = $bookings->filter(function($booking) use ($term) {
    //             return str_contains(strtolower($booking['full_name'] ?? ''), $term) ||
    //                   str_contains(strtolower($booking['email'] ?? ''), $term) ||
    //                   str_contains($booking['phone'] ?? '', $term) ||
    //                   str_contains(strtolower($booking['package_title'] ?? ''), $term) ||
    //                   str_contains((string)($booking['id'] ?? ''), $term);
    //         });
    //     }
        
    //     // Apply status filter
    //     if ($this->filterStatus == 'filled') {
    //         $bookings = $bookings->filter(function($booking) {
    //             $filledCount = $booking['filled_count'] ?? 0;
    //             $totalPassengers = $booking['passenger_count'];
    //             return ($filledCount == $totalPassengers && $totalPassengers > 0);
    //         });
    //     } elseif ($this->filterStatus == 'unfilled') {
    //         $bookings = $bookings->filter(function($booking) {
    //             $filledCount = $booking['filled_count'] ?? 0;
    //             $totalPassengers = $booking['passenger_count'];
    //             return ($filledCount < $totalPassengers);
    //         });
    //     }
        
    //     $this->filteredPendingBookings = $bookings->values()->toArray();
    // }
public function filterBookings()
    {
        $bookings = collect($this->allPendingBookings);
        
        // Apply search filter
        if (!empty($this->searchTerm)) {
            $term = strtolower($this->searchTerm);
            $bookings = $bookings->filter(function($booking) use ($term) {
                return str_contains(strtolower($booking['full_name'] ?? ''), $term) ||
                       str_contains(strtolower($booking['email'] ?? ''), $term) ||
                       str_contains($booking['phone'] ?? '', $term) ||
                       str_contains(strtolower($booking['package_title'] ?? ''), $term) ||
                       str_contains((string)($booking['id'] ?? ''), $term) ||
                       str_contains(strtolower($booking['booking_token'] ?? ''), $term); // 👈 Token add kiya
            });
        }
        
        // Apply status filter
        if ($this->filterStatus == 'filled') {
            $bookings = $bookings->filter(function($booking) {
                $filledCount = $booking['filled_count'] ?? 0;
                $totalPassengers = $booking['passenger_count'];
                return ($filledCount == $totalPassengers && $totalPassengers > 0);
            });
        } elseif ($this->filterStatus == 'unfilled') {
            $bookings = $bookings->filter(function($booking) {
                $filledCount = $booking['filled_count'] ?? 0;
                $totalPassengers = $booking['passenger_count'];
                return ($filledCount < $totalPassengers);
            });
        }
        
        $this->filteredPendingBookings = $bookings->values()->toArray();
    }
    // Load all pending bookings from ALL packages
 public function loadAllPendingBookings()
    {
        // Get all bookings that don't have seats assigned
        $allBookings = Bookings::with(['seats', 'package'])
            ->whereDoesntHave('seats') // Bookings without seats
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($booking) {
                $passengerData = $this->extractFullPassengerDetails($booking->data_get);
                
                $quantity = 0;
                if (!empty($booking->active_cost)) {
                    foreach ($booking->active_cost as $item) {
                        $quantity += $item['quantity'] ?? 0;
                    }
                }
                
                $passengerCount = !empty($passengerData['passengers']) ? count($passengerData['passengers']) : $quantity;
                
                // Calculate filled passengers count
                $filledCount = collect($passengerData['passengers'])->filter(function($p) {
                    return !empty($p['name']) && $p['name'] != 'Unknown';
                })->count();
                
                return [
                    'id' => $booking->id,
                    'booking_id' => $booking->id,
                    'full_name' => $booking->full_name,
                    'email' => $booking->email,
                    'phone' => $booking->phone,
                    'package_id' => $booking->package_id,
                    'package_title' => $booking->package->title ?? 'Unknown Package',
                    'start_date' => $booking->start_date ? date('d M Y', strtotime($booking->start_date)) : 'N/A',
                    'passenger_count' => $passengerCount,
                    'filled_count' => $filledCount,
                    'passengers' => $passengerData['passengers'],
                    'booking_token' => $booking->booking_token ?? null, // 👈 Token add kiya
                    'gender_breakdown' => [
                        'male' => collect($passengerData['passengers'])->where('gender', 'male')->count(),
                        'female' => collect($passengerData['passengers'])->where('gender', 'female')->count(),
                        'other' => collect($passengerData['passengers'])->where('gender', 'other')->count(),
                    ],
                    'created_at' => $booking->created_at->format('d M Y, h:i A')
                ];
            })
            ->values()
            ->toArray();

        $this->allPendingBookings = $allBookings;
        $this->totalPendingCount = count($allBookings);
        $this->filteredPendingBookings = $this->allPendingBookings; // Initialize filtered
    }

    // Open pending bookings modal
    public function openPendingBookingsModal()
    {
        $this->searchTerm = ''; // Reset search
        $this->filterStatus = ''; // Reset filter
        $this->loadAllPendingBookings(); // Refresh data
        $this->showPendingBookingsModal = true;
    }

    // Close pending bookings modal
    public function closePendingBookingsModal()
    {
        $this->showPendingBookingsModal = false;
        $this->searchTerm = '';
        $this->filterStatus = '';
    }

    // Function to extract full passenger details from data_get
    private function extractFullPassengerDetails($dataGet)
    {
        $passengers = [];
        $sharingDetails = [];
        
        // If it's a string, decode it
        if (is_string($dataGet)) {
            $dataGet = json_decode($dataGet, true);
        }
        
        // If it's not an array or is empty, return empty array
        if (!is_array($dataGet) || empty($dataGet)) {
            return ['passengers' => [], 'sharing_details' => []];
        }
        
        // Extract passenger details from all sharing types
        foreach ($dataGet as $sharingType => $sharingData) {
            $sharingDetails[$sharingType] = [
                'count' => $sharingData['count'] ?? 0,
                'members' => []
            ];
            
            if (isset($sharingData['members']) && is_array($sharingData['members'])) {
                foreach ($sharingData['members'] as $index => $member) {
                    if (!is_array($member)) continue;
                    
                    // Get gender and standardize it
                    $gender = isset($member['gender']) ? strtolower(trim($member['gender'])) : 'male';
                    
                    if (empty($gender) || $gender == ' ') {
                        $gender = 'male';
                    } elseif (in_array($gender, ['other', 'others', 'transgender', 'third'])) {
                        $gender = 'other';
                    } elseif (in_array($gender, ['female', 'woman', 'women', 'f'])) {
                        $gender = 'female';
                    } else {
                        $gender = 'male';
                    }
                    
                    $passengerData = [
                        'id' => uniqid(), // Temporary ID for selection
                        'member_number' => $member['member_number'] ?? ($index + 1),
                        'name' => $member['name'] ?? 'Unknown',
                        'gender' => $gender,
                        'contact' => $member['contact'] ?? '',
                        'email' => $member['email'] ?? '',
                        'dob' => $member['dob'] ?? '',
                        'sharing_type' => str_replace('_', ' ', ucwords($sharingType)),
                        'original_sharing' => $sharingType,
                        'id_proof_type' => $member['id_proof_type'] ?? '',
                        'id_proof_number' => $member['id_proof_number'] ?? '',
                        'emergency_name' => $member['emergency_name'] ?? '',
                        'emergency_contact' => $member['emergency_contact'] ?? '',
                    ];
                    
                    $passengers[] = $passengerData;
                    $sharingDetails[$sharingType]['members'][] = $passengerData;
                }
            }
        }
        
        return [
            'passengers' => $passengers,
            'sharing_details' => $sharingDetails
        ];
    }

    public function loadBookings()
    {
        if (!$this->package_id || !$this->date) {
            $this->selectedSeats = [];
            $this->bookings = collect();
            $this->bookingsWithSeats = collect();
            return;
        }

        $packageDate = PackageDates::where('package_id', $this->package_id)
            ->where('start_date', $this->date)
            ->first();

        if (!$packageDate) {
            $this->selectedSeats = [];
            $this->bookings = collect();
            $this->bookingsWithSeats = collect();
            return;
        }

        $this->package = Packages::with(['packageVehicles' => function ($q) use ($packageDate) {
            $q->where('package_date_id', $packageDate->id)
                ->with(['vehicle', 'seats']);
        }])->find($this->package_id);

        // Get all bookings for this date
        $allBookings = Bookings::with(['seats'])
            ->where('package_id', $this->package_id)
            ->whereDate('start_date', $this->date)
            ->get();

        // Process bookings WITHOUT seats
        $this->bookings = $allBookings->filter(function ($booking) {
            return $booking->seats->isEmpty();
        })->map(function ($booking) {
            // Extract full passenger details
            $passengerData = $this->extractFullPassengerDetails($booking->data_get);
            
            // Calculate total quantity from active_cost
            $quantity = 0;
            if (!empty($booking->active_cost)) {
                foreach ($booking->active_cost as $item) {
                    $quantity += $item['quantity'] ?? 0;
                }
            }
            
            // If we have passengers from data_get, use that count, otherwise use quantity
            $passengerCount = !empty($passengerData['passengers']) ? count($passengerData['passengers']) : $quantity;
            
            return [
                'id' => $booking->id,
                'full_name' => $booking->full_name,
                'email' => $booking->email,
                'phone' => $booking->phone,
                'quantity' => $quantity,
                'passenger_count' => $passengerCount,
                'passengers' => $passengerData['passengers'],
                'sharing_details' => $passengerData['sharing_details'],
                'gender_breakdown' => [
                    'male' => collect($passengerData['passengers'])->where('gender', 'male')->count(),
                    'female' => collect($passengerData['passengers'])->where('gender', 'female')->count(),
                    'other' => collect($passengerData['passengers'])->where('gender', 'other')->count(),
                ]
            ];
        })->values();

        // Process bookings WITH seats
        // $this->bookingsWithSeats = $allBookings->filter(function ($booking) {
        //     return $booking->seats->isNotEmpty();
        // })->map(function ($booking) {
        //     // Extract passenger details from data_get
        //     $passengerData = $this->extractFullPassengerDetails($booking->data_get);
            
        //     // Get seats with their details from the database (including new columns)
        //     $seatsByBus = [];
        //     $seatsWithDetails = [];
            
        //     foreach ($booking->seats->groupBy('package_vehicle_id') as $vehicleId => $seats) {
        //         // Simple seat numbers array
        //         $seatsByBus[$vehicleId] = $seats->pluck('seat_no')->toArray();
                
        //         // Detailed seat info from database with all new columns
        //         $seatsWithDetails[$vehicleId] = $seats->map(function($seat) {
        //             return [
        //                 'seat_no' => $seat->seat_no,
        //                 'seat_id' => $seat->id,
        //                 'gender' => $seat->gender ?? 'unknown',
        //                 'passenger_name' => $seat->passenger_name ?? 'Unknown',
        //                 'passenger_contact' => $seat->passenger_contact ?? '',
        //                 'sharing_type' => $seat->sharing_type ?? 'N/A',
        //                 'booking_id' => $seat->booking_id
        //             ];
        //         })->keyBy('seat_no')->toArray();
        //     }
            
        //     // Calculate gender breakdown from actual seat data (more accurate)
        //     $maleCount = $booking->seats->where('gender', 'male')->count();
        //     $femaleCount = $booking->seats->where('gender', 'female')->count();
        //     $otherCount = $booking->seats->where('gender', 'other')->count();
            
        //     return [
        //         'id' => $booking->id,
        //         'full_name' => $booking->full_name,
        //         'active_cost' => $booking->active_cost,
        //         'passenger_count' => count($passengerData['passengers']),
        //         'seats_by_bus' => $seatsByBus,
        //         'seats_with_details' => $seatsWithDetails,
        //         'passengers' => $passengerData['passengers'],
        //         'sharing_details' => $passengerData['sharing_details'],
        //         'gender_breakdown' => [
        //             'male' => $maleCount,
        //             'female' => $femaleCount,
        //             'other' => $otherCount,
        //         ]
        //     ];
        // })->values();

        $this->bookingsWithSeats = $allBookings->filter(function ($booking) {
    return $booking->seats->isNotEmpty();
})->map(function ($booking) {
    // Extract passenger details from data_get
    $passengerData = $this->extractFullPassengerDetails($booking->data_get);
    
    // Get seats with their details from the database (including new columns)
    $seatsByBus = [];
    $seatsWithDetails = [];
    
    foreach ($booking->seats->groupBy('package_vehicle_id') as $vehicleId => $seats) {
        // Simple seat numbers array
        $seatsByBus[$vehicleId] = $seats->pluck('seat_no')->toArray();
        
        // Detailed seat info from database with all new columns
        $seatsWithDetails[$vehicleId] = $seats->map(function($seat) {
            return [
                'seat_no' => $seat->seat_no,
                'seat_id' => $seat->id,
                'gender' => $seat->gender ?? 'unknown',
                'passenger_name' => $seat->passenger_name ?? 'Unknown',
                'passenger_contact' => $seat->passenger_contact ?? '',
                'sharing_type' => $seat->sharing_type ?? 'N/A',
                'booking_id' => $seat->booking_id,
                'is_captain' => $seat->is_captain ?? false // Captain flag
            ];
        })->keyBy('seat_no')->toArray();
    }
    
    // Calculate gender breakdown from actual seat data (more accurate)
    $maleCount = $booking->seats->where('gender', 'male')->count();
    $femaleCount = $booking->seats->where('gender', 'female')->count();
    $otherCount = $booking->seats->where('gender', 'other')->count();
    $captainCount = $booking->seats->where('is_captain', true)->count();
    
    return [
        'id' => $booking->id,
        'full_name' => $booking->full_name,
        'active_cost' => $booking->active_cost,
        'passenger_count' => count($passengerData['passengers']),
        'seats_by_bus' => $seatsByBus,
        'seats_with_details' => $seatsWithDetails,
        'passengers' => $passengerData['passengers'],
        'sharing_details' => $passengerData['sharing_details'],
        'gender_breakdown' => [
            'male' => $maleCount,
            'female' => $femaleCount,
            'other' => $otherCount,
        ],
        'captain_count' => $captainCount
    ];
})->values();

        // Refresh pending bookings count
        $this->loadAllPendingBookings();
    }

    // Open passenger selection modal
    public function openPassengerModal($bookingId)
    {
        $this->selectedBookingForPassengers = $bookingId;
        
        // Find the booking
        $booking = $this->bookings->firstWhere('id', $bookingId);
        if (!$booking) return;
        
        $this->bookingPassengers = $booking['passengers'] ?? [];
        
        // Get available seats (selected seats)
        $this->availableSeatsForBooking = [];
        
        // Get seat details for selected seats
        if (!empty($this->selectedSeats)) {
            $this->availableSeatsForBooking = Seats::whereIn('id', $this->selectedSeats)
                ->with('packageVehicle')
                ->get()
                ->map(function($seat) {
                    return [
                        'id' => $seat->id,
                        'seat_no' => $seat->seat_no,
                        'bus_label' => $seat->packageVehicle->label ?? 'Unknown',
                        'gender' => $seat->gender
                    ];
                })->toArray();
        }
        
        // Initialize passenger to seat map
        $this->passengerToSeatMap = [];
        foreach ($this->bookingPassengers as $index => $passenger) {
            $this->passengerToSeatMap[$index] = null;
        }
        
        $this->showPassengerModal = true;
    }

    // Assign passenger to seat
    public function assignPassengerToSeat($passengerIndex, $seatId)
    {
        $this->passengerToSeatMap[$passengerIndex] = $seatId;
    }

    // Save passenger assignments
    // public function savePassengerAssignments()
    // {
    //     if (empty($this->selectedBookingForPassengers) || empty($this->passengerToSeatMap)) {
    //         return;
    //     }
        
    //     // Filter out unassigned passengers
    //     $assignments = array_filter($this->passengerToSeatMap, function($seatId) {
    //         return !is_null($seatId) && !empty($seatId);
    //     });
        
    //     if (empty($assignments)) {
    //         Notification::make()
    //             ->title('Please assign passengers to seats')
    //             ->warning()
    //             ->send();
    //         return;
    //     }
        
    //     // Get the booking to access passenger details
    //     $booking = $this->bookings->firstWhere('id', $this->selectedBookingForPassengers);
        
    //     DB::beginTransaction();
    //     try {
    //         foreach ($assignments as $passengerIndex => $seatId) {
    //             $passenger = $this->bookingPassengers[$passengerIndex] ?? null;
    //             if ($passenger && is_array($passenger)) {
    //                 // Update the seat with all passenger details
    //                 Seats::where('id', $seatId)->update([
    //                     'booking_id' => $this->selectedBookingForPassengers,
    //                     'gender' => $passenger['gender'] ?? 'male',
    //                     'passenger_name' => $passenger['name'] ?? 'Unknown',
    //                     'passenger_contact' => $passenger['contact'] ?? null,
    //                     'sharing_type' => $passenger['sharing_type'] ?? null,
    //                     'updated_at' => now()
    //                 ]);
    //             }
    //         }
            
    //         // Clear selected seats that were assigned
    //         $assignedSeatIds = array_values($assignments);
    //         $this->selectedSeats = array_diff($this->selectedSeats, $assignedSeatIds);
    //         $this->selectedSeats = array_values($this->selectedSeats);
            
    //         DB::commit();
            
    //         $this->closePassengerModal();
    //         $this->loadBookings();
            
    //         Notification::make()
    //             ->title('Passengers assigned successfully')
    //             ->success()
    //             ->send();
                
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Notification::make()
    //             ->title('Error assigning passengers: ' . $e->getMessage())
    //             ->danger()
    //             ->send();
    //     }
    // }
// Save passenger assignments
public function savePassengerAssignments()
{
    if (empty($this->selectedBookingForPassengers) || empty($this->passengerToSeatMap)) {
        return;
    }
    
    // Filter out unassigned passengers
    $assignments = array_filter($this->passengerToSeatMap, function($seatId) {
        return !is_null($seatId) && !empty($seatId);
    });
    
    if (empty($assignments)) {
        Notification::make()
            ->title('Please assign passengers to seats')
            ->warning()
            ->send();
        return;
    }
    
    // Get the booking to access passenger details
    $booking = $this->bookings->firstWhere('id', $this->selectedBookingForPassengers);
    
    DB::beginTransaction();
    try {
        foreach ($assignments as $passengerIndex => $seatId) {
            $passenger = $this->bookingPassengers[$passengerIndex] ?? null;
            if ($passenger && is_array($passenger)) {
                // Check if this seat is marked as captain seat
                $isCaptain = isset($this->captainSeats[$seatId]);
                
                // Update the seat with all passenger details
                Seats::where('id', $seatId)->update([
                    'booking_id' => $this->selectedBookingForPassengers,
                    'gender' => $passenger['gender'] ?? 'male',
                    'passenger_name' => $passenger['name'] ?? 'Unknown',
                    'passenger_contact' => $passenger['contact'] ?? null,
                    'sharing_type' => $passenger['sharing_type'] ?? null,
                    'is_captain' => $isCaptain, // Captain flag set karo
                    'updated_at' => now()
                ]);
            }
        }
        
        // Clear selected seats that were assigned
        $assignedSeatIds = array_values($assignments);
        $this->selectedSeats = array_diff($this->selectedSeats, $assignedSeatIds);
        $this->selectedSeats = array_values($this->selectedSeats);
        
        // Clear captain seats that were assigned
        foreach ($assignedSeatIds as $seatId) {
            if (isset($this->captainSeats[$seatId])) {
                unset($this->captainSeats[$seatId]);
            }
        }
        
        DB::commit();
        
        $this->closePassengerModal();
        $this->loadBookings();
        
        Notification::make()
            ->title('Passengers assigned successfully')
            ->success()
            ->send();
            
    } catch (\Exception $e) {
        DB::rollBack();
        Notification::make()
            ->title('Error assigning passengers: ' . $e->getMessage())
            ->danger()
            ->send();
    }
}
    // Close passenger modal
    public function closePassengerModal()
    {
        $this->showPassengerModal = false;
        $this->selectedBookingForPassengers = null;
        $this->bookingPassengers = [];
        $this->passengerToSeatMap = [];
        $this->availableSeatsForBooking = [];
    }

    public function updatedPackageId()
    {
        $this->selectedSeats = [];
        $this->date = null;
        $this->loadBookings();
    }

    public function updatedDate()
    {
        $this->selectedSeats = [];
        $this->loadBookings();
    }

    public function toggleSeat($seatId)
    {
        $seat = Seats::with(['packageVehicle.date'])->find($seatId);
        if (!$seat) return;

        if ($seat->packageVehicle->date->start_date !== $this->date) return;

        // Check if seat is booked
        if ($seat->booking_id != null) {
            return;
        }

        // Toggle seat selection
        if (in_array($seatId, $this->selectedSeats)) {
            $this->selectedSeats = array_values(array_diff($this->selectedSeats, [$seatId]));
        } else {
            $this->selectedSeats[] = $seatId;
            $this->updateActiveBookings();
        }
    }

    public function addVehicle()
    {
        if (!$this->package_id || !$this->date || !$this->selectedVehicleId) {
            Notification::make()
                ->title('Please select package, date and vehicle')
                ->warning()
                ->send();
            return;
        }

        $packageDate = PackageDates::where('package_id', $this->package_id)
            ->where('start_date', $this->date)
            ->first();

        if (!$packageDate) return;

        $vehicle = Vehicle::find($this->selectedVehicleId);
        if (!$vehicle) return;

        $count = PackageVehicle::where('package_date_id', $packageDate->id)->count();

        $newBus = PackageVehicle::create([
            'package_id'      => $this->package_id,
            'package_date_id' => $packageDate->id,
            'vehicle_id'      => $vehicle->id,
            'label'           => $vehicle->vehicle_type . " " . ($count + 1)
        ]);

        for ($i = 1; $i <= $vehicle->vehicle_seats; $i++) {
            Seats::create([
                'package_vehicle_id' => $newBus->id,
                'seat_no' => $i
            ]);
        }

        Packages::where('id', $this->package_id)->update([
            'vehicle_id' => $vehicle->id
        ]);

        $this->selectedSeats = [];
        $this->loadBookings();
        
        Notification::make()
            ->title('Vehicle added successfully')
            ->success()
            ->send();
    }

    // Select booking to open modal
    public function selectBooking($bookingId)
    {
        if (empty($this->selectedSeats)) {
            Notification::make()
                ->title('Please select seats first')
                ->warning()
                ->send();
            return;
        }
        
        // Open passenger selection modal
        $this->openPassengerModal($bookingId);
    }

    public function removeBus($busId)
    {
        $bus = PackageVehicle::find($busId);
        if (!$bus) return;

        $bus->seats()->delete();
        $bus->delete();

        $this->selectedSeats = [];
        $this->loadBookings();
        
        Notification::make()
            ->title('Bus removed successfully')
            ->success()
            ->send();
    }

    public function removeSeatAllotment($bookingId)
    {
        Seats::where('booking_id', $bookingId)->update([
            'booking_id' => null,
            'gender' => null,
            'passenger_name' => null,
            'passenger_contact' => null,
            'sharing_type' => null
        ]);

        $this->selectedSeats = [];
        $this->loadBookings();
        
        Notification::make()
            ->title('Seat allotment removed successfully')
            ->success()
            ->send();
    }

    public function updateActiveBookings()
    {
        $count = count($this->selectedSeats);
        $this->activeBookings = [];
        
        foreach ($this->bookings as $booking) {
            if ($booking['passenger_count'] == $count) {
                $this->activeBookings[] = $booking['id'];
            }
        }
    }
}