<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Bookings;
use App\Models\Packages;
use App\Models\PackageDates;
use App\Models\Room;
use App\Models\StayLayout as StayLayoutModel;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\DB;
use App\Models\Role;

use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
class StayLayout extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static string $view = 'filament.pages.stay-layout';
    protected static ?string $navigationGroup = 'Stay Management';
    protected static ?int $navigationSort = 1;
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




        
      if (!in_array('stay-layout', $permissionResources)) {
         return false;
        }

       

        return true;
    
        // return false;
        // dd('sd');
        return auth()->user()?->role === 'admin';

    }

    public $package_id;
    public $date;
    public $room_id;
    public $room_no;
    public $room_type;
    public $booking_id = NULL;
    public $roomNumbers = []; 
    public $bookings = [];
    public $selectedBookingId = null;
    public $selectedRoomId = null;
    public $selectedBedNumber = null;
    public $guestsList = [];
    public $roomAssignments = [];
    public $bookingDetails = null;
    public $selectedRoom = null;
    public $availableRooms = [];
    public $unassignedGuests = [];
    public $selectedGuests = [];
    public $showRoomLayout = false;
    public $showFullLayout = false;
    public $fullLayoutData = [];
    public $summaryByType = [];
    public $totalBeds = 0;
    public $viewMode = 'all';
    
    // 🔥 NEW: Gender prompt properties
    public $showGenderPrompt = false;
    public $selectedRoomGender = null;

    public function mount(): void
    {
        $this->bookings = collect();
        $this->roomAssignments = [];
        $this->availableRooms = Room::where('status', 'active')->get();
        $this->selectedGuests = [];
        $this->showRoomLayout = false;
        
        // 🔥 NEW: Initialize gender prompt
        $this->showGenderPrompt = false;
        $this->selectedRoomGender = null;
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
                ->label('Select Departure Date')
                ->options(function ($get) {
                    if (!$get('package_id')) return [];

                    return PackageDates::where('package_id', $get('package_id'))
                        ->pluck('start_date', 'start_date')
                        ->toArray();
                })
                ->reactive()
                ->afterStateUpdated(function ($state) {
                    if ($state) {
                        $this->loadBookingsForDate();
                        $this->loadExistingAssignments();
                        $this->showRoomLayout = false;
                        $this->selectedRoomId = null;
                        
                        // 🔥 NEW: Reset gender prompt
                        $this->showGenderPrompt = false;
                        $this->selectedRoomGender = null;
                    }
                }),
        ];
    }

    /**
     * Extract passenger details from data_get JSON column
     */
     
  public function updateRoomNumber($roomId)
{
    try {
        DB::beginTransaction();
        
        $roomNumber = $this->roomNumbers[$roomId] ?? null;
        
        if (!$roomNumber) {
            Notification::make()
                ->title('Error')
                ->body('Please enter a room number')
                ->danger()
                ->send();
            return;
        }
        
        // Update all assignments in this room for current booking/date
        StayLayoutModel::where('room_id', $roomId)
            ->where('package_id', $this->package_id)
            ->where('booking_id', $this->selectedBookingId)
            ->whereDate('start_date', $this->date)
            ->update(['room_no' => $roomNumber]);
        
        DB::commit();
        
        $this->refreshFullLayout();
        
        Notification::make()
            ->title('Success')
            ->body("Room number updated to {$roomNumber}")
            ->success()
            ->send();
            
    } catch (\Exception $e) {
        DB::rollBack();
        Notification::make()
            ->title('Error')
            ->body('Failed to update room number')
            ->danger()
            ->send();
    }
}


    // private function extractFullPassengerDetails($dataGet, $bookingId = null)
    // {
    //     $passengers = [];
        
    //     try {
    //         if (empty($dataGet)) {
    //             return ['passengers' => []];
    //         }
            
    //         // Agar string hai to JSON decode karo
    //         if (is_string($dataGet)) {
    //             $decoded = json_decode($dataGet, true);
    //             if (json_last_error() === JSON_ERROR_NONE) {
    //                 $dataGet = $decoded;
    //             } else {
    //                 return ['passengers' => []];
    //             }
    //         }
            
    //         if (!is_array($dataGet)) {
    //             return ['passengers' => []];
    //         }
            
    //         // Har sharing type ke liye
    //         foreach ($dataGet as $sharingType => $sharingData) {
    //             if (!is_array($sharingData)) {
    //                 continue;
    //             }
                
    //             // Check if members exist
    //             if (!isset($sharingData['members']) || !is_array($sharingData['members'])) {
    //                 continue;
    //             }
                
    //             // Har member ke liye
    //             foreach ($sharingData['members'] as $index => $member) {
    //                 if (!is_array($member)) {
    //                     continue;
    //                 }
                    
    //                 // Gender normalize karo
    //                 $gender = isset($member['gender']) ? strtolower(trim($member['gender'])) : 'male';
                    
    //                 if (in_array($gender, ['female', 'woman', 'women', 'f'])) {
    //                     $gender = 'female';
    //                 } elseif (in_array($gender, ['other', 'others', 'transgender'])) {
    //                     $gender = 'other';
    //                 } else {
    //                     $gender = 'male';
    //                 }
                    
    //                 // Unique ID banao
    //                 $uniqueId = 'guest_' . ($bookingId ?? '') . '_' . $index . '_' . uniqid();
                    
    //                 $passengers[] = [
    //                     'id' => $uniqueId,
    //                     'member_number' => $member['member_number'] ?? ($index + 1),
    //                     'name' => $member['name'] ?? 'Unknown',
    //                     'gender' => $gender,
    //                     'contact' => $member['contact'] ?? '',
    //                     'email' => $member['email'] ?? '',
    //                     'sharing_type' => $this->formatSharingType($sharingType),
    //                     'original_sharing' => $sharingType,
    //                 ];
    //             }
    //         }
            
    //         return ['passengers' => $passengers];
            
    //     } catch (\Exception $e) {
    //         \Log::error('Error extracting passenger details: ' . $e->getMessage());
    //         return ['passengers' => []];
    //     }
    // }


/**
 * Extract passenger details from info_get table
 */
private function extractFullPassengerDetails($dataGet, $bookingId = null)
{
    $passengers = [];
    
    try {
        if (!$bookingId) {
            return ['passengers' => []];
        }
        
        // Fetch passengers from info_get table
        $passengerRecords = DB::table('info_get')
            ->where('booking_id', $bookingId)
            ->where('package_id', $this->package_id)
            ->get();
            
        if ($passengerRecords->isEmpty()) {
            return ['passengers' => []];
        }
        
        foreach ($passengerRecords as $record) {
            // Gender normalize karo
            $gender = isset($record->gender) ? strtolower(trim($record->gender)) : 'male';
            
            if (in_array($gender, ['female', 'woman', 'women', 'f'])) {
                $gender = 'female';
            } elseif (in_array($gender, ['other', 'others', 'transgender'])) {
                $gender = 'other';
            } else {
                $gender = 'male';
            }
            
            // Unique ID using the record's id from info_get
            $uniqueId = 'guest_' . $bookingId . '_' . $record->id;
            
            $passengers[] = [
                'id' => $uniqueId,
                'person_id' => $record->id, // This is the info_get record ID
                'member_number' => $record->member_number ?? 1,
                'name' => $record->name ?? 'Unknown',
                'gender' => $gender,
                'contact' => $record->contact ?? '',
                'email' => $record->email ?? '',
                'sharing_type' => $this->formatSharingType($record->sharing_type ?? ''),
                'original_sharing' => $record->sharing_type ?? '',
                'dob' => $record->dob ?? null,
                'id_proof_type' => $record->id_proof_type ?? null,
                'id_proof_number' => $record->id_proof_number ?? null,
                'emergency_name' => $record->emergency_name ?? null,
                'emergency_contact' => $record->emergency_contact ?? null,
                'emergency_relation' => $record->emergency_relation ?? null,
            ];
        }
        
        return ['passengers' => $passengers];
        
    } catch (\Exception $e) {
        \Log::error('Error extracting passenger details from info_get: ' . $e->getMessage());
        return ['passengers' => []];
    }
}


    /**
     * Format sharing type name
     */
    private function formatSharingType($type)
    {
        $type = str_replace('_', ' ', $type);
        $type = ucwords($type);
        return $type;
    }

    /**
     * Load bookings for selected date
     */
    public function loadBookingsForDate()
    {
        if (!$this->package_id || !$this->date) {
            return;
        }

        $this->bookings = Bookings::where('package_id', $this->package_id)
            ->where('start_date', $this->date)
            ->whereIn('status', ['confirmed', 'completed'])
            ->get();
    }

    /**
     * Load existing assignments - ROOM_NO ADDED HERE
     */
   /**
 * Load existing assignments - with person_id
 */
public function loadExistingAssignments()
{
    $this->roomAssignments = [];
    
    $assignments = StayLayoutModel::with(['room', 'booking'])
        ->where('package_id', $this->package_id)
        ->where('booking_id', $this->booking_id)
        ->where('room_id', $this->room_id)
        ->whereDate('start_date', $this->date)
        ->get();
        
    foreach ($assignments as $assignment) {
        $this->roomAssignments[$assignment->room_id][$assignment->bed_number] = [
            'guest_name' => $assignment->guest_name,
            'booking_id' => $assignment->booking_id,
            'assignment_id' => $assignment->id,
            'guest_gender' => $assignment->guest_gender,
            'is_captain' => $assignment->is_captain,
            'room_no' => $assignment->room_no,
            'person_id' => $assignment->person_id // Added person_id
        ];
    }
}

    /**
     * Load all unassigned guests from selected booking
     */
    public function loadUnassignedGuests()
    {
        $this->unassignedGuests = [];
        
        if (!$this->selectedBookingId) {
            return;
        }
        
        $booking = Bookings::find($this->selectedBookingId);
        if (!$booking) {
            return;
        }
        
        $details = $this->extractFullPassengerDetails($booking->data_get, $booking->id);
        
        // Sab bookings ke assignments lo (check karne ke liye)
        $allAssignments = StayLayoutModel::where('package_id', $this->package_id)
                                            ->where('booking_id', $this->booking_id)
            ->whereDate('start_date', $this->date)
            ->get();
        
        foreach ($details['passengers'] as $passenger) {
            // Check if already assigned
            $isAssigned = $allAssignments->contains(function($assignment) use ($passenger, $booking) {
                return $assignment->guest_name == $passenger['name'] 
                    && $assignment->booking_id == $booking->id;
            });
            
            if (!$isAssigned) {
                $passenger['booking_id'] = $booking->id;
                $this->unassignedGuests[] = $passenger;
            }
        }
    }

    // 🔥 NEW: Set gender for room
    public function setRoomGender($gender)
    {
        $this->room_type = $gender;
        $this->selectedRoomGender = $gender;
        $this->showGenderPrompt = false;
        $this->showRoomLayout = true;
        
        $genderText = $this->getGenderText($gender);
        Notification::make()
            ->title('Gender Selected')
            ->body("This room is for {$genderText}.")
            ->success()
            ->send();
    }

    // 🔥 NEW: Get gender text
    private function getGenderText($gender)
    {
        return match($gender) {
            'male' => '♂️ Male',
            'female' => '♀️ Female',
            'other' => '⚧ Other',
            'mixed' => '👥 Mixed',
            default => $gender
        };
    }

    // 🔥 NEW: Validate guest gender for room
    private function validateGenderForRoom($guestGender, $roomGender)
    {
        // Mixed room mein sab allow
        if ($roomGender === 'mixed') {
            return true;
        }
        
        // Exact match chahiye
        return strtolower($guestGender) === $roomGender;
    }

    /**
     * Toggle full layout view
     */
    public function toggleFullLayout()
    {
        $this->showFullLayout = !$this->showFullLayout;
        if ($this->showFullLayout) {
            if ($this->selectedBookingId) {
                $this->loadBookingLayout($this->selectedBookingId);
            } else {
                $this->loadFullLayout();
            }
        }
    }

    /**
     * Load full layout for all bookings - ROOM_NO ADDED HERE
     */
   /**
 * Load full layout for all bookings - with person_id
 */
public function loadFullLayout()
{
    $this->viewMode = 'all'; 
    $this->fullLayoutData = [];
    $this->summaryByType = [];
    $this->selectedBookingId = null;
    $this->bookingDetails = null;
    
    // Get all active rooms
    $rooms = Room::where('status', 'active')->orderBy('id')->get();
    $this->totalBeds = $rooms->sum('no_of_beds');
    
    // Get all assignments for this package and date
    $assignments = StayLayoutModel::with('room')
        ->where('package_id', $this->package_id)
        ->whereDate('start_date', $this->date)
        ->get()
        ->groupBy('room_id');

    // Initialize summary counters
    $summaryData = [];
    
    foreach ($rooms as $room) {
        $roomAssignments = $assignments[$room->id] ?? collect();
        
        $beds = [];
        foreach ($roomAssignments as $assignment) {
            $beds[$assignment->bed_number] = [
                'name' => $assignment->guest_name,
                'booking_id' => $assignment->booking_id,
                'gender' => $assignment->guest_gender,
                'assignment_id' => $assignment->id,
                'is_captain' => $assignment->is_captain,
                'room_no' => $assignment->room_no,
                'person_id' => $assignment->person_id // Added person_id
            ];
        }
        
        // Add to fullLayoutData
        $this->fullLayoutData[] = [
            'room_no' => $room->room_no,
            'room_type' => $room->room_type,
            'total_beds' => $room->no_of_beds,
            'beds' => $beds,
            'room_id' => $room->id
        ];
        
        // Build summary by room type
        $type = $room->room_type;
        if (!isset($summaryData[$type])) {
            $summaryData[$type] = [
                'total_rooms' => 0,
                'total_beds' => 0,
                'occupied' => 0,
                'available' => 0
            ];
        }
        
        $summaryData[$type]['total_rooms']++;
        $summaryData[$type]['total_beds'] += $room->no_of_beds;
        $summaryData[$type]['occupied'] += count($beds);
        $summaryData[$type]['available'] = 
            $summaryData[$type]['total_beds'] - $summaryData[$type]['occupied'];
    }
    
    $this->summaryByType = $summaryData;
    
    // Sort rooms by room number
    usort($this->fullLayoutData, function($a, $b) {
        return $a['room_no'] <=> $b['room_no'];
    });
}

    /**
     * Load layout for specific booking - ROOM_NO ADDED HERE
     */
    public function loadBookingLayout($bookingId = null)
    {
        $this->viewMode = 'booking';
        $bookingId = $bookingId ?? $this->selectedBookingId;
        
        if (!$bookingId) {
            Notification::make()
                ->title('Error')
                ->body('Please select a booking first')
                ->warning()
                ->send();
            return;
        }
        
        $this->fullLayoutData = [];
        $this->summaryByType = [];
        $this->selectedBookingId = $bookingId;
        
        // Get booking details
        $this->bookingDetails = Bookings::find($bookingId);
        
        // Get all active rooms
        $rooms = Room::where('status', 'active')->orderBy('id')->get();
        $this->totalBeds = $rooms->sum('no_of_beds');
        
        // Get assignments for this booking only
        $assignments = StayLayoutModel::with('room')
            ->where('package_id', $this->package_id)
            ->where('booking_id', $bookingId)
            ->whereDate('start_date', $this->date)
            ->get()
            ->groupBy('room_id');
        
        // Initialize summary counters
        $summaryData = [];
        
        foreach ($rooms as $room) {
            $roomAssignments = $assignments[$room->id] ?? collect();
            
            $beds = [];
            foreach ($roomAssignments as $assignment) {
                $beds[$assignment->bed_number] = [
                    'name' => $assignment->guest_name,
                    'booking_id' => $assignment->booking_id,
                    'gender' => $assignment->guest_gender,
                    'assignment_id' => $assignment->id,
                    'is_captain' => $assignment->is_captain,
                    'room_no' => $assignment->room_no // YEH LINE ADD KI HAI
                ];
            }
            
            // Sirf wohi rooms dikhao jisme is booking ke guests hain
            if (count($beds) > 0) {
                $this->fullLayoutData[] = [
                    'room_no' => $room->room_no,
                    'room_type' => $room->room_type,
                    'total_beds' => $room->no_of_beds,
                    'beds' => $beds,
                    'room_id' => $room->id,
                    'occupied_by_booking' => count($beds)
                ];
                
                // Build summary by room type
                $type = $room->room_type;
                if (!isset($summaryData[$type])) {
                    $summaryData[$type] = [
                        'total_rooms' => 0,
                        'total_beds' => 0,
                        'occupied_by_booking' => 0,
                        'occupied' => 0,
                        'available' => 0
                    ];
                }
                
                $summaryData[$type]['total_rooms']++;
                $summaryData[$type]['total_beds'] += $room->no_of_beds;
                $summaryData[$type]['occupied_by_booking'] += count($beds);
                $summaryData[$type]['occupied'] = $summaryData[$type]['occupied_by_booking'];
                $summaryData[$type]['available'] = $summaryData[$type]['total_beds'] - $summaryData[$type]['occupied'];
            }
        }
        
        $this->summaryByType = $summaryData;
        
        // Sort rooms by room number
        usort($this->fullLayoutData, function($a, $b) {
            return $a['room_no'] <=> $b['room_no'];
        });
        
        $this->showFullLayout = true;
        
        $bookingNumber = $this->bookingDetails->id ?? 'Unknown';
        
        Notification::make()
            ->title('Booking Layout Loaded')
            ->body("Showing assignments for Booking #{$bookingNumber}")
            ->success()
            ->send();
    }

    /**
     * Refresh full layout
     */
    public function refreshFullLayout()
    {
        if ($this->selectedBookingId) {
            $this->loadBookingLayout($this->selectedBookingId);
        } else {
            $this->loadFullLayout();
        }
        
        Notification::make()
            ->title('Layout Refreshed')
            ->body('Room layout has been updated')
            ->success()
            ->send();
    }

    /**
     * Get booking-wise data for tabs
     */
    public function getBookingWiseData()
    {
        $data = [];
        
        foreach ($this->bookings as $booking) {
            $bookingGuests = $this->extractFullPassengerDetails($booking->data_get, $booking->id)['passengers'];
            $assignedCount = StayLayoutModel::where('package_id', $this->package_id)
                ->where('booking_id', $booking->id)
                ->whereDate('start_date', $this->date)
                ->count();
            $totalGuests = count($bookingGuests);
            
            $data[$booking->id] = [
                'id' => $booking->id,
                'customer_name' => $booking->customer_name ?? 'Unknown',
                'assigned' => $assignedCount,
                'total' => $totalGuests,
                'pending' => $totalGuests - $assignedCount,
                'color' => $this->getBookingColor($booking->id)
            ];
        }
        
        return $data;
    }

    /**
     * Get booking-wise summary
     */
    public function getBookingWiseSummary()
    {
        $summary = [];
        
        foreach ($this->bookings as $booking) {
            $assignedCount = StayLayoutModel::where('package_id', $this->package_id)
                ->where('booking_id', $booking->id)
                ->whereDate('start_date', $this->date)
                ->count();
                
            $guests = $this->extractFullPassengerDetails($booking->data_get, $booking->id)['passengers'];
            $totalGuests = count($guests);
            
            $summary[$booking->id] = [
                'assigned' => $assignedCount,
                'total' => $totalGuests,
                'pending' => $totalGuests - $assignedCount,
                'customer' => $booking->customer_name ?? 'Unknown'
            ];
        }
        
        return $summary;
    }

    /**
     * Get color for booking ID
     */
    private function getBookingColor($bookingId)
    {
        $colors = [
            41 => 'blue',
            42 => 'green',
            43 => 'purple',
            44 => 'orange',
            45 => 'pink',
            46 => 'indigo',
            47 => 'red',
            48 => 'yellow',
            49 => 'teal',
            50 => 'cyan',
        ];
        
        return $colors[$bookingId] ?? 'gray';
    }

    /**
     * Select a booking
     */
    public function selectBooking($bookingId)
    {
        $this->selectedBookingId = $bookingId;
        $this->bookingDetails = Bookings::find($bookingId);
        
        if (!$this->bookingDetails) {
            Notification::make()
                ->title('Error')
                ->body('Booking not found')
                ->danger()
                ->send();
            return;
        }
        
        // Load existing assignments first
        $this->booking_id = $bookingId;
        $this->loadExistingAssignments();
        
        // Then load unassigned guests
        $this->loadUnassignedGuests();
        
        $this->selectedGuests = [];
        $this->showRoomLayout = false;
        $this->selectedRoomId = null;
        
        // 🔥 NEW: Reset gender prompt
        $this->showGenderPrompt = false;
        $this->selectedRoomGender = null;
        
        // Agar full layout already showing hai to is booking ka layout dikhao
        if ($this->showFullLayout) {
            $this->loadBookingLayout($bookingId);
        }
        
        Notification::make()
            ->title('Booking Selected')
            ->body('Found ' . count($this->unassignedGuests) . ' unassigned guests. Please select a room from dropdown.')
            ->success()
            ->send();
    }

    /**
     * Select room from dropdown
     */
 public function viewdata()
{
    DB::statement("SET SQL_MODE=''");

    $staylayout = StayLayoutModel::with('room')->where('package_id', $this->package_id)
        ->whereDate('start_date', $this->date)
        ->get()
        ->groupBy([
            'booking_id',
            'room_id'
        ]);

    return $staylayout;
}


public function downloadLayout()
{
    $data = $this->viewdata();

    $pdf = Pdf::loadView('filament.pages.pdf-layout', [
        'data' => $data
    ])->setPaper('a4','landscape');

    return response()->streamDownload(
        fn () => print($pdf->output()),
        'stay-layout.pdf'
    );
}
    public function selectRoom($roomId)
    {
        $this->selectedRoomId = $roomId;
        $this->room_id  = $roomId;
        $this->selectedRoom = Room::find($roomId);
        
        // Auto-fill room number in roomNumbers array
        if ($this->selectedRoom && $this->selectedRoom->room_no) {
            $this->roomNumbers[$roomId] = $this->selectedRoom->room_no;
        }
        
        // 🔥 NEW: Show gender prompt instead of room layout
        $this->showGenderPrompt = true;
        $this->showRoomLayout = false;
        $this->selectedRoomGender = null;
        $this->selectedBedNumber = null;
        
        Notification::make()
            ->title('Room Selected')
            ->body('Please select who this room is for: Male, Female, Other, or Mixed')
            ->info()
            ->send();
    }
    
    public function quickAssignBed($roomId, $bedNumber)
    {   
        
        // dd($roomss);
        $roomdata = StayLayoutModel::where('id',$roomId)->first();
        // dd($roomdata);
        if (!$this->selectedRoomGender) {
            Notification::make()
                ->title('Select Gender First')
                ->body('Please select who this room is for before assigning.')
                ->warning()
                ->send();
            return;
        }
        
        $this->selectedRoomId = $roomId;
        $this->selectedBedNumber = $bedNumber;
        $this->selectedRoom = Room::find($roomId);
        
        // Load unassigned guests
        $this->loadUnassignedGuests();
        
        // Open guest selection modal
        $this->dispatch('open-modal', id: 'select-guest-modal');
        
        Notification::make()
            ->title('Select Guest')
            ->body("Choose a guest for Bed #{$bedNumber}")
            ->info()
            ->send();
    }

    /**
     * Select a bed to assign guest
     */
    public function selectBed($roomId, $bedNumber)
    {
        // 🔥 Check if gender is selected
        if (!$this->selectedRoomGender) {
            Notification::make()
                ->title('Select Gender First')
                ->body('Please select who this room is for before assigning.')
                ->warning()
                ->send();
            return;
        }
        
        // Check if bed is occupied
        if (isset($this->roomAssignments[$roomId][$bedNumber])) {
            Notification::make()
                ->title('Bed Already Occupied')
                ->body('This bed is assigned to ' . $this->roomAssignments[$roomId][$bedNumber]['guest_name'])
                ->warning()
                ->send();
            return;
        }
        
        $this->selectedRoomId = $roomId;
        $this->selectedBedNumber = $bedNumber;
        $this->selectedRoom = Room::find($roomId);
        
        // Load unassigned guests for the selected booking
        $this->loadUnassignedGuests();
        
        // Open guest selection modal
        $this->dispatch('open-modal', id: 'select-guest-modal');
    }

    /**
     * Assign selected guest to bed
     */
    // public function downloadLayout($format)
    // {
    //     $data = [
    //         'package_id' => $this->package_id,
    //         'date' => $this->date,
    //         'fullLayoutData' => $this->fullLayoutData,
    //         'summaryByType' => $this->summaryByType,
    //         'totalBeds' => $this->totalBeds,
    //         'selectedBookingId' => $this->selectedBookingId,
    //         'bookingDetails' => $this->bookingDetails
    //     ];
        
    //     $filename = 'room-layout-' . $this->date . '-' . time();
        
    //     switch($format) {
    //         case 'pdf':
    //             return $this->downloadAsPdf($data, $filename);
    //         case 'excel':
    //             return $this->downloadAsExcel($data, $filename);
    //         case 'csv':
    //             return $this->downloadAsCsv($data, $filename);
    //         default:
    //             Notification::make()
    //                 ->title('Error')
    //                 ->body('Invalid download format')
    //                 ->danger()
    //                 ->send();
    //             return;
    //     }
    // }

    /**
     * Download as CSV
     */
    private function downloadAsCsv($data, $filename)
    {
        $csv = [];
        
        // Header with Room No
        $csv[] = ['Room No', 'Room Type', 'Bed Number', 'Guest Name', 'Booking ID', 'Gender', 'Assigned Room No'];
        
        // Data
        foreach($data['fullLayoutData'] as $room) {
            foreach($room['beds'] as $bedNum => $bed) {
                $csv[] = [
                    $room['room_no'],
                    $room['room_type'],
                    $bedNum,
                    $bed['name'],
                    $bed['booking_id'],
                    $bed['gender'] ?? 'N/A',
                    $bed['room_no'] ?? $room['room_no']
                ];
            }
        }
        
        // Empty rooms
        foreach($data['fullLayoutData'] as $room) {
            if(count($room['beds']) == 0) {
                $csv[] = [
                    $room['room_no'],
                    $room['room_type'],
                    'All',
                    'Empty',
                    'N/A',
                    'N/A',
                    $room['room_no']
                ];
            }
        }
        
        // Create CSV
        $handle = fopen('php://temp', 'r+');
        foreach($csv as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        
        return response()->streamDownload(function() use ($content) {
            echo $content;
        }, $filename . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Download as Excel
     */
    private function downloadAsExcel($data, $filename)
    {
        $html = '<table border="1">';
        $html .= '<tr><th>Room No</th><th>Room Type</th><th>Bed Number</th><th>Guest Name</th><th>Booking ID</th><th>Gender</th><th>Assigned Room No</th></tr>';
        
        foreach($data['fullLayoutData'] as $room) {
            foreach($room['beds'] as $bedNum => $bed) {
                $html .= '<tr>';
                $html .= '<td>' . $room['room_no'] . '</td>';
                $html .= '<td>' . $room['room_type'] . '</td>';
                $html .= '<td>' . $bedNum . '</td>';
                $html .= '<td>' . $bed['name'] . '</td>';
                $html .= '<td>' . $bed['booking_id'] . '</td>';
                $html .= '<td>' . ($bed['gender'] ?? 'N/A') . '</td>';
                $html .= '<td>' . ($bed['room_no'] ?? $room['room_no']) . '</td>';
                $html .= '</tr>';
            }
        }
        
        // Empty rooms
        foreach($data['fullLayoutData'] as $room) {
            if(count($room['beds']) == 0) {
                $html .= '<tr>';
                $html .= '<td>' . $room['room_no'] . '</td>';
                $html .= '<td>' . $room['room_type'] . '</td>';
                $html .= '<td>All</td>';
                $html .= '<td>Empty</td>';
                $html .= '<td>N/A</td>';
                $html .= '<td>N/A</td>';
                $html .= '<td>' . $room['room_no'] . '</td>';
                $html .= '</tr>';
            }
        }
        
        $html .= '</table>';
        
        return response()->streamDownload(function() use ($html) {
            echo $html;
        }, $filename . '.xls', [
            'Content-Type' => 'application/vnd.ms-excel',
        ]);
    }

    /**
     * Download as PDF
     */
    private function downloadAsPdf($data, $filename)
    {
        try {
            Log::info('PDF Generation Started', [
                'package_id' => $data['package_id'],
                'date' => $data['date'],
                'selectedBookingId' => $data['selectedBookingId'] ?? null
            ]);

            if (!view()->exists('filament.pages.pdf-layout')) {
                $error = 'PDF view not found at: resources/views/filament/pages/pdf-layout.blade.php';
                Log::error($error);
                
                Notification::make()
                    ->title('PDF Error')
                    ->body('View file missing')
                    ->danger()
                    ->send();
                return;
            }

            Log::info('View exists, generating PDF...');

            $pdf = Pdf::loadView('filament.pages.pdf-layout', $data);
            $pdf->setPaper('A4', 'landscape');
            $pdf->setOptions([
                'defaultFont' => 'sans-serif',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
                'logOutputFile' => storage_path('logs/dompdf.html')
            ]);

            Log::info('PDF generated successfully, size: ' . strlen($pdf->output()) . ' bytes');

            return response()->streamDownload(function() use ($pdf) {
                echo $pdf->output();
            }, $filename . '.pdf', [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.pdf"',
                'Cache-Control' => 'no-cache, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);

        } catch (\Exception $e) {
            Log::error('PDF Generation Exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->title('PDF Generation Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();

            return;
        }
    }

   public function assignGuestToBed($guestId)
{
    if (!$this->selectedRoomId || !$this->selectedBedNumber) {
        Notification::make()
            ->title('Error')
            ->body('Please select a bed first')
            ->danger()
            ->send();
        return;
    }

    // Check if gender is selected
    if (!$this->selectedRoomGender) {
        Notification::make()
            ->title('Select Gender First')
            ->body('Please select who this room is for before assigning.')
            ->warning()
            ->send();
        return;
    }

    // Find guest
    $guest = collect($this->unassignedGuests)->firstWhere('id', $guestId);
    
    if (!$guest) {
        Notification::make()
            ->title('Error')
            ->body('Guest not found')
            ->danger()
            ->send();
        return;
    }

    // GENDER VALIDATION
    if (!$this->validateGenderForRoom($guest['gender'], $this->selectedRoomGender)) {
        $genderText = $this->getGenderText($this->selectedRoomGender);
        Notification::make()
            ->title('Gender Mismatch')
            ->body("This room is for {$genderText}. You cannot assign a {$guest['gender']} guest here.")
            ->danger()
            ->send();
        return;
    }

    try {
        DB::beginTransaction();
        
        // Check if this specific bed is already assigned
        $existingAssignment = StayLayoutModel::where('room_id', $this->selectedRoomId)
            ->where('bed_number', $this->selectedBedNumber)
            ->where('package_id', $this->package_id)
            ->where('booking_id', $this->booking_id)
            ->whereDate('start_date', $this->date)
            ->lockForUpdate()
            ->first();

        if ($existingAssignment) {
            DB::rollBack();
            Notification::make()
                ->title('Bed Already Occupied')
                ->body('This bed is already assigned to ' . $existingAssignment->guest_name)
                ->warning()
                ->send();
            
            $this->loadExistingAssignments();
            return;
        }

        // Check if this person is already assigned anywhere using person_id
        $guestAlreadyAssigned = StayLayoutModel::where('booking_id', $this->selectedBookingId)
            ->where('person_id', $guest['person_id']) // Using person_id from info_get
            ->where('package_id', $this->package_id)
            ->whereDate('start_date', $this->date)
            ->lockForUpdate()
            ->first();

        if ($guestAlreadyAssigned) {
            DB::rollBack();
            Notification::make()
                ->title('Guest Already Assigned')
                ->body($guest['name'] . ' is already assigned to Bed #' . $guestAlreadyAssigned->bed_number)
                ->warning()
                ->send();
            
            $this->loadUnassignedGuests();
            return;
        }
     
        // Create the assignment with person_id
        StayLayoutModel::create([
            'booking_id' => $this->selectedBookingId,
            'room_id' => $this->selectedRoomId,
            'room_type' => $this->room_type,
            'package_id' => $this->package_id,
            'bed_number' => $this->selectedBedNumber,
            'guest_name' => $guest['name'],
            'person_id' => $guest['person_id'], // Save the info_get record ID
            'guest_email' => $guest['email'] ?? null,
            'guest_contact' => $guest['contact'] ?? null,
            'guest_gender' => $guest['gender'] ?? 'male',
            'start_date' => $this->date,
            'end_date' => $this->date,
            'status' => 'active',
            'is_captain' => false
        ]);
  
        DB::commit();

        // Refresh data
        $this->loadExistingAssignments();
        $this->loadUnassignedGuests();
        
        if ($this->showFullLayout) {
            if ($this->selectedBookingId) {
                $this->loadBookingLayout($this->selectedBookingId);
            } else {
                $this->loadFullLayout();
            }
        }
        
        Notification::make()
            ->title('Success')
            ->body("{$guest['name']} assigned to Bed #{$this->selectedBedNumber}")
            ->success()
            ->send();

        $this->selectedBedNumber = null;
        $this->dispatch('close-modal', id: 'select-guest-modal');
        
    } catch (\Exception $e) {
        DB::rollBack();
        
        Notification::make()
            ->title('Error')
            ->body('Failed to assign guest: ' . $e->getMessage())
            ->danger()
            ->send();
    }
}
    /**
     * Toggle guest selection for bulk assign
     */
    public function toggleGuestSelection($guestId)
    {
        if (in_array($guestId, $this->selectedGuests)) {
            $this->selectedGuests = array_diff($this->selectedGuests, [$guestId]);
        } else {
            $this->selectedGuests[] = $guestId;
        }
    }


 public function toggleGuestSelection22($package_id, $selectedBookingId, $date, $selectedRoomId, $selectedBedNumber)
{
    try {
        DB::beginTransaction();
        
        // Find the guest (assuming you have unassigned guests loaded)
        
        
        // Check if bed is already occupied
        $existingAssignment = StayLayoutModel::where('room_id', $selectedRoomId)
            ->where('bed_number', $selectedBedNumber)
            ->where('package_id', $package_id)
            ->where('booking_id', $selectedBookingId)
            ->whereDate('start_date', $date)
            ->first();
            
        if ($existingAssignment) {
            Notification::make()
                ->title('Error')
                ->body('This bed is already occupied')
                ->danger()
                ->send();
            return;
        }
        
        // Create new assignment
        $assignment = StayLayoutModel::create([
            'package_id' => $package_id,
            'booking_id' => $selectedBookingId,
            'room_id' => $selectedRoomId,
            'bed_number' => $selectedBedNumber,
            'guest_name' => $guest['name'] ?? '',
            'room_type' => $this->room_type,
            'guest_email' => $guest['email'] ?? null,
            'guest_contact' => $guest['contact'] ?? null,
            'guest_gender' => $guest['gender'] ?? $this->room_type,
            'start_date' => $date,
            'end_date' => $date,
            'status' => 'active',
            'is_captain' => true
        ]);
        
        DB::commit();
        
        // Refresh data
        $this->refreshFullLayout();
        $this->loadUnassignedGuests();
        
        Notification::make()
            ->title('Success')
            ->body("Guest assigned to Bed #{$selectedBedNumber}")
            ->success()
            ->send();
        
        // Close modal
        $this->dispatch('close-modal', id: 'select-guest-modal');
        
    } catch (\Exception $e) {
        DB::rollBack();
        
        Notification::make()
            ->title('Error')
            ->body('Failed to assign guest: ' . $e->getMessage())
            ->danger()
            ->send();
    }
}


    /**
     * Assign multiple selected guests to current room
     */
    public function assignMultipleGuests()
    {
        if (!$this->selectedRoomId || empty($this->selectedGuests)) {
            Notification::make()
                ->title('Error')
                ->body('Please select a room and at least one guest')
                ->danger()
                ->send();
            return;
        }

        // 🔥 Check if gender is selected
        if (!$this->selectedRoomGender) {
            Notification::make()
                ->title('Select Gender First')
                ->body('Please select who this room is for before assigning.')
                ->warning()
                ->send();
            return;
        }

        $room = Room::find($this->selectedRoomId);
        $availableBeds = $room->no_of_beds - (isset($this->roomAssignments[$this->selectedRoomId]) ? count($this->roomAssignments[$this->selectedRoomId]) : 0);
        
        if (count($this->selectedGuests) > $availableBeds) {
            Notification::make()
                ->title('Not Enough Beds')
                ->body("This room only has {$availableBeds} available beds")
                ->danger()
                ->send();
            return;
        }

        // 🔥 Gender validation
        $invalidGuests = [];
        foreach ($this->selectedGuests as $guestId) {
            $guest = collect($this->unassignedGuests)->firstWhere('id', $guestId);
            if ($guest && !$this->validateGenderForRoom($guest['gender'], $this->selectedRoomGender)) {
                $invalidGuests[] = $guest['name'];
            }
        }
        
        if (!empty($invalidGuests)) {
            $genderText = $this->getGenderText($this->selectedRoomGender);
            Notification::make()
                ->title('Gender Mismatch')
                ->body("Following guests cannot be assigned to this {$genderText} room: " . implode(', ', $invalidGuests))
                ->danger()
                ->send();
            return;
        }

        // Find next available bed numbers
        $occupiedBeds = array_keys($this->roomAssignments[$this->selectedRoomId] ?? []);
        $availableBedNumbers = [];
        for ($i = 1; $i <= $room->no_of_beds; $i++) {
            if (!in_array($i, $occupiedBeds)) {
                $availableBedNumbers[] = $i;
            }
        }

        try {
            DB::beginTransaction();
            
            $assignedCount = 0;
            foreach ($this->selectedGuests as $index => $guestId) {
                $guest = collect($this->unassignedGuests)->firstWhere('id', $guestId);
                if (!$guest) continue;
                
                StayLayoutModel::create([
                    'booking_id' => $this->selectedBookingId,
                    'room_id' => $this->selectedRoomId,
                    'bed_number' => $availableBedNumbers[$index],
                    'guest_name' => $guest['name'],
                    'guest_email' => $guest['email'] ?? null,
                    'guest_contact' => $guest['contact'] ?? null,
                    'guest_gender' => $guest['gender'] ?? null,
                    'start_date' => $this->date,
                    'end_date' => $this->date,
                    'status' => 'active'
                ]);
                $assignedCount++;
            }

            DB::commit();

            // Refresh data
            $this->loadExistingAssignments();
            $this->loadUnassignedGuests();
            
            // Agar full layout showing hai to refresh karo
            if ($this->showFullLayout) {
                if ($this->selectedBookingId) {
                    $this->loadBookingLayout($this->selectedBookingId);
                } else {
                    $this->loadFullLayout();
                }
            }
            
            $this->selectedGuests = [];
            
            Notification::make()
                ->title('Success')
                ->body("{$assignedCount} guests assigned to " . ucfirst($room->room_type) . " Room")
                ->success()
                ->send();

            $this->dispatch('close-modal', id: 'bulk-assign-modal');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Error')
                ->body('Failed to assign guests: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Assign captain to a specific bed
     */
    public function assignCaptain($assignmentId, $bedNumber)
    {
        if (!$assignmentId) {
            Notification::make()
                ->title('Error')
                ->body('Invalid assignment')
                ->danger()
                ->send();
            return;
        }
        
        try {
            DB::beginTransaction();
            
            $assignment = StayLayoutModel::find($assignmentId);
            
            if (!$assignment) {
                Notification::make()
                    ->title('Error')
                    ->body('Assignment not found')
                    ->danger()
                    ->send();
                return;
            }
            
            // Remove captain from all other beds in this room
            StayLayoutModel::where('room_id', $assignment->room_id)
                ->where('package_id', $assignment->package_id)
                ->where('booking_id', $assignment->booking_id)
                ->whereDate('start_date', $assignment->start_date)
                ->update(['is_captain' => false]);
            
            // Set this bed as captain
            $assignment->is_captain = true;
            $assignment->save();
            
            DB::commit();
            
            // Refresh layout
            $this->refreshFullLayout();
            
            Notification::make()
                ->title('Success')
                ->body("Bed #{$bedNumber} is now Captain")
                ->success()
                ->send();
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Error')
                ->body('Failed to assign captain: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Remove captain from a bed
     */
    public function removeCaptain($assignmentId)
    {
        try {
            DB::beginTransaction();
            
            $assignment = StayLayoutModel::find($assignmentId);
            
            if ($assignment) {
                $assignment->is_captain = false;
                $assignment->save();
            }
            
            DB::commit();
            
            // Refresh layout
            $this->refreshFullLayout();
            
            Notification::make()
                ->title('Success')
                ->body('Captain removed')
                ->success()
                ->send();
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Error')
                ->body('Failed to remove captain')
                ->danger()
                ->send();
        }
    }
    
    /**
     * Remove guest from bed
     */
    public function removeGuestFromBed($roomId, $bedNumber)
    {
        try {
            $assignment = StayLayoutModel::where('room_id', $roomId)
                ->where('package_id', $this->package_id)
                ->where('bed_number', $bedNumber)
                ->whereDate('start_date', $this->date)
                ->first();
            
            if ($assignment) {
                $guestName = $assignment->guest_name;
                $assignment->delete();
                
                $this->loadExistingAssignments();
                $this->loadUnassignedGuests();
                
                // Agar full layout showing hai to refresh karo
                if ($this->showFullLayout) {
                    if ($this->selectedBookingId) {
                        $this->loadBookingLayout($this->selectedBookingId);
                    } else {
                        $this->loadFullLayout();
                    }
                }
                
                Notification::make()
                    ->title('Success')
                    ->body("{$guestName} removed from Bed #{$bedNumber}")
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Failed to remove guest')
                ->danger()
                ->send();
        }
    }

    /**
     * Check if bed is available
     */
    public function isBedAvailable($roomId, $bedNumber)
    {
        return !isset($this->roomAssignments[$roomId][$bedNumber]);
    }

    /**
     * Get guest name for bed
     */
    public function getGuestForBed($roomId, $bedNumber)
    {
        return $this->roomAssignments[$roomId][$bedNumber]['guest_name'] ?? null;
    }

    /**
     * Get booking ID for bed
     */
    public function getBookingIdForBed($roomId, $bedNumber)
    {
        return $this->roomAssignments[$roomId][$bedNumber]['booking_id'] ?? null;
    }

    /**
     * Get assignment ID for bed
     */
    public function getAssignmentIdForBed($roomId, $bedNumber)
    {
        return $this->roomAssignments[$roomId][$bedNumber]['assignment_id'] ?? null;
    }

    /**
     * Get all rooms with their beds
     */
    public function getRoomsProperty()
    {
        return Room::where('status', 'active')->get();
    }

    /**
     * Get available beds count for a room
     */
    public function getAvailableBedsCount($roomId)
    {
        $room = Room::find($roomId);
            
        if (!$room) return 0;
        
        $occupied = isset($this->roomAssignments[$roomId]) ? count($this->roomAssignments[$roomId]) : 0;
        return $room->no_of_beds - $occupied;
    }

    /**
     * Get room options for dropdown
     */
    public function getRoomOptionsProperty()
    {
        $options = [];
        foreach ($this->availableRooms as $room) {
            $availableBeds = $this->getAvailableBedsCount($room->id);
            $options[$room->id] = ucfirst($room->room_type) . ' (' . $room->no_of_beds . ' beds - ' . $availableBeds . ' available)';
        }
        return $options;
    }

    /**
     * Get gender color class
     */
    public function getGenderColor($gender)
    {
        return match($gender) {
            'female' => 'pink',
            'other' => 'purple',
            default => 'blue'
        };
    }

    /**
     * Clear selected booking
     */
    public function clearSelectedBooking()
    {
        $this->selectedBookingId = null;
        $this->bookingDetails = null;
        $this->unassignedGuests = [];
        $this->selectedGuests = [];
        $this->showRoomLayout = false;
        $this->selectedRoomId = null;
        
        // 🔥 NEW: Reset gender prompt
        $this->showGenderPrompt = false;
        $this->selectedRoomGender = null;
        
        // Agar full layout showing hai to sab bookings ka layout dikhao
        if ($this->showFullLayout) {
            $this->loadFullLayout();
        }
        
        Notification::make()
            ->title('Booking Cleared')
            ->body('Now showing all bookings')
            ->info()
            ->send();
    }

    /**
     * Open bulk assign modal for room
     */
    public function openBulkAssign($roomId)
    {
        $this->selectedRoomId = $roomId;
        $this->selectedRoom = Room::find($roomId);
        $this->loadUnassignedGuests();
        $this->dispatch('open-modal', id: 'bulk-assign-modal');
    }
}