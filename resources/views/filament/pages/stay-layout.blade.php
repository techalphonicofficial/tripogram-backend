<x-filament::page>
    <div class="space-y-6">
        {{-- Package and Date Selection --}}
        <div class="bg-white rounded-xl shadow p-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-medium">📅 Select Package & Departure Date</h2>
                
                @if($date && $package_id)
                    <button 
                        wire:click="toggleFullLayout"
                        class="px-4 py-2 {{ $showFullLayout ? 'bg-gray-500' : 'bg-primary-500' }} text-white rounded-lg hover:opacity-90 transition-colors flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($showFullLayout)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6"></path>
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                            @endif
                        </svg>
                        {{ $showFullLayout ? 'Hide Full Layout' : 'View All Layout' }}
                    </button>
                @endif
            </div>
            {{ $this->form }}
        </div>

        @if($date && $package_id)
            {{-- Quick Stats --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Total Bookings</p>
                            <p class="text-2xl font-bold">{{ $bookings->count() }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-green-100 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Assigned Guests</p>
                            <p class="text-2xl font-bold">{{ collect($this->roomAssignments)->flatten(1)->count() }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-yellow-100 rounded-lg">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Unassigned Guests</p>
                            <p class="text-2xl font-bold">{{ count($this->unassignedGuests) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-purple-100 rounded-lg">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Available Beds</p>
                            <p class="text-2xl font-bold">{{ $this->availableRooms->sum('no_of_beds') - collect($this->roomAssignments)->flatten(1)->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Full Layout Table View --}}
           @if($showFullLayout)
           
           <div class="flex justify-end mb-3">
    <button 
        wire:click="downloadLayout"
        class=" text-black px-4 py-2 rounded text-sm"
    >
        Download PDF
    </button>
</div>

    @php
$data = $this->viewdata();
@endphp

@foreach($data as $bookingId => $rooms)

    <div class="border rounded-lg mb-6">

        {{-- Booking Header --}}
        <div class="bg-gray-100 px-4 py-2 font-semibold">
            Booking #{{ $bookingId }}
        </div>

        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50">
                    <th class="p-2 text-left">Room</th>
                    <th class="p-2 text-left">Type</th>
                   <th class="p-2 text-left">Room Type</th>
                    <th class="p-2 text-left">Bed 1</th>
                    <th class="p-2 text-left">Bed 2</th>
                    <th class="p-2 text-left">Bed 3</th>
                    <th class="p-2 text-left">Bed 4</th>
                    <th class="p-2 text-left">Bed 5</th>
                    <th class="p-2 text-left">Bed 6</th>
                    <th class="p-2 text-left">Total</th>
<th class="p-2 text-left">Available</th>
                </tr>
            </thead>

    <tbody>

@php
    $sr = 1;
    $grandTotal = 0;
@endphp

@foreach($rooms as $roomId => $beds)

    @php
        $room = $beds->first();
        $bedMap = $beds->keyBy('bed_number');
        $totalBeds = $room->room->no_of_beds ?? 6;

        // Captain exclude karke count
        $occupiedBeds = $beds->where('is_captain', 0)->count();
        $availableBeds = $totalBeds - $occupiedBeds;

        $grandTotal += $occupiedBeds;
    @endphp

    <tr class="border-b">

        {{-- Room Serial Number --}}
        <td class="p-2 font-medium">
            {{ $sr++ }}
        </td>

        {{-- Room Type --}}
        <td class="p-2">
            {{ ucfirst($room->room->room_type ?? 'other') }}
        </td>

        {{-- Room Type (StayLayout's room_type field) --}}
        <td class="p-2">
            {{ ucfirst($room->room_type ?? 'other') }}
        </td>

        {{-- Beds Loop --}}
        @for($i = 1; $i <= 6; $i++)
            <td class="p-2">
                @if(isset($bedMap[$i]))
                    @php
                        $bed = $bedMap[$i];
                    @endphp
                    <div class="bg-blue-50 p-2 rounded">
                        <div class="flex items-center gap-1">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            <span class="text-sm">
                                {{ \Str::limit($bed->guest_name, 12) }}
                            </span>
                            @if($bed->is_captain)
                                <span>👑</span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ ucfirst($bed->guest_gender) }}
                        </div>
                    </div>
                @else
                    @if($i <= $totalBeds)
                        <button
                            wire:click="quickAssignBed({{ $roomId }}, {{ $i }})"
                            class="text-xs bg-blue-500 text-white px-2 py-1 rounded"
                        >
                            Assign
                        </button>
                    @endif
                @endif
            </td>
        @endfor

        {{-- Total Occupied Beds --}}
        <td class="p-2 font-semibold text-blue-600">
            {{ $occupiedBeds }}
        </td>

        {{-- Available Beds --}}
        <td class="p-2 font-semibold text-green-600">
            {{ $availableBeds }}
        </td>

    </tr>

@endforeach

{{-- Grand Total Row --}}
<tr class="bg-gray-100 font-bold">
    <td colspan="9" class="text-right p-2">Grand Total Occupied Beds (excluding captains):</td>
    <td class="p-2 text-blue-600">{{ $grandTotal }}</td>
    <td></td> {{-- Empty cell for available beds column --}}
</tr>

</tbody>
<tfoot>
<tr class="bg-gray-100 font-semibold">
    <td colspan="9" class="p-3 text-right">
        Grand Total Guests : {{ $grandTotal }}
    </td>
</tr>
</tfoot>
          
        </table>

    </div>

@endforeach
@endif
            {{-- Selected Booking Info --}}
            @if($selectedBookingId && $bookingDetails)
                <div class="bg-primary-50 border border-primary-200 rounded-xl p-4">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-primary-100 rounded-lg">
                                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-primary-600 font-medium">Selected Booking #{{ $bookingDetails->id }}</p>
                                <p class="text-sm">
                                    <span class="font-medium">Customer:</span> {{ $bookingDetails->customer_name ?? 'No name' }} | 
                                    <span class="text-green-600">{{ count($this->unassignedGuests) }} unassigned</span>
                                </p>
                            </div>
                        </div>
                        <button wire:click="clearSelectedBooking" class="px-3 py-1 text-sm bg-white border border-primary-300 rounded-lg hover:bg-primary-50">
                            Clear
                        </button>
                    </div>

                    @if(!empty($this->unassignedGuests))
                        <div class="mt-3 pt-3 border-t border-primary-200">
                            <p class="text-sm font-medium mb-2">👥 Unassigned Guests:</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($this->unassignedGuests as $guest)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm
                                        @if($guest['gender'] == 'female') bg-pink-100 text-pink-700
                                        @elseif($guest['gender'] == 'other') bg-purple-100 text-purple-700
                                        @else bg-blue-100 text-blue-700
                                        @endif
                                    ">
                                        {{ $guest['name'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Bookings List --}}
            <div class="bg-white rounded-xl shadow p-4">
                <h2 class="text-lg font-medium mb-4">📋 Bookings for {{ $date }}</h2>
                
                @if($bookings->isEmpty())
                    <p class="text-gray-500 text-center py-4">No bookings found for this date</p>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($bookings as $booking)
                            @php
                                $bookingGuests = $this->extractFullPassengerDetails($booking->data_get, $booking->id)['passengers'];
                                $assignedCount = collect($this->roomAssignments)->flatten(1)->where('booking_id', $booking->id)->count();
                                $totalGuests = count($bookingGuests);
                                $unassignedInBooking = $totalGuests - $assignedCount;
                            @endphp
                            
                            <div class="border rounded-lg p-4 cursor-pointer transition-all {{ $selectedBookingId == $booking->id ? 'ring-2 ring-primary-500 bg-primary-50' : 'hover:shadow-md' }}"
                                 wire:click="selectBooking({{ $booking->id }})">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-medium">Booking #{{ $booking->id }}</h3>
                                        <p class="text-sm text-gray-600">{{ $booking->customer_name ?? 'No name' }}</p>
                                    </div>
                                    <span class="text-xs px-2 py-1 rounded-full {{ $unassignedInBooking > 0 ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700' }}">
                                        {{ $assignedCount }}/{{ $totalGuests }}
                                    </span>
                                </div>
                                
                                <div class="mt-2 w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="bg-primary-600 h-1.5 rounded-full" style="width: {{ $totalGuests > 0 ? ($assignedCount/$totalGuests)*100 : 0 }}%"></div>
                                </div>

                                @if($unassignedInBooking > 0)
                                    <p class="text-xs text-yellow-600 mt-2">{{ $unassignedInBooking }} guests need assignment</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Room Selection Dropdown --}}
            @if($selectedBookingId && !empty($this->unassignedGuests))
                <div class="bg-white rounded-xl shadow p-4">
                    <h2 class="text-lg font-medium mb-4">🏨 Select Room</h2>
                    
                    <div class="max-w-md">
                        <select wire:model="selectedRoomId" wire:change="selectRoom($event.target.value)" class="block w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                            <option value="">-- Choose a room --</option>
                            @foreach($this->roomOptions as $roomId => $roomLabel)
                                <option value="{{ $roomId }}">{{ $roomLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            {{-- 🔥 NEW: Gender Selection Prompt --}}
            @if($showGenderPrompt && $selectedRoom && !$selectedRoomGender)
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-medium">Who is this room for?</h2>
                            <p class="text-sm text-gray-600">Room: {{ $selectedRoom->room_no ?? 'N/A' }} ({{ ucfirst($selectedRoom->room_type) }})</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <button wire:click="setRoomGender('male')"
                                class="p-4 border-2 border-blue-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all">
                            <div class="text-3xl mb-2">♂️</div>
                            <div class="font-medium">Male</div>
                            <div class="text-xs text-gray-500 mt-1">{{ collect($unassignedGuests)->where('gender', 'male')->count() }} guests</div>
                        </button>
                        
                        <button wire:click="setRoomGender('female')"
                                class="p-4 border-2 border-pink-200 rounded-lg hover:border-pink-500 hover:bg-pink-50 transition-all">
                            <div class="text-3xl mb-2">♀️</div>
                            <div class="font-medium">Female</div>
                            <div class="text-xs text-gray-500 mt-1">{{ collect($unassignedGuests)->where('gender', 'female')->count() }} guests</div>
                        </button>
                        
                        <button wire:click="setRoomGender('other')"
                                class="p-4 border-2 border-purple-200 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition-all">
                            <div class="text-3xl mb-2">⚧</div>
                            <div class="font-medium">Other</div>
                            <div class="text-xs text-gray-500 mt-1">{{ collect($unassignedGuests)->where('gender', 'other')->count() }} guests</div>
                        </button>
                        
                        <button wire:click="setRoomGender('mixed')"
                                class="p-4 border-2 border-gray-200 rounded-lg hover:border-gray-500 hover:bg-gray-50 transition-all">
                            <div class="text-3xl mb-2">👥</div>
                            <div class="font-medium">Mixed</div>
                            <div class="text-xs text-gray-500 mt-1">{{ count($unassignedGuests) }} total guests</div>
                        </button>
                    </div>
                </div>
            @endif

            {{-- Room Layout --}}
            @if($showRoomLayout && $selectedRoom && $selectedRoomGender)
                <div class="bg-white rounded-xl shadow p-4">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h2 class="text-lg font-medium capitalize">{{ $selectedRoom->room_type }} Room Layout</h2>
                            <div class="flex items-center gap-4 mt-1">
                                <p class="text-sm text-gray-600">
                                    <span class="text-green-600 font-medium">{{ $this->getAvailableBedsCount($selectedRoom->id) }} available</span> | 
                                    {{ $selectedRoom->no_of_beds - $this->getAvailableBedsCount($selectedRoom->id) }} occupied
                                </p>
                                
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-600">Room No:</span>
                                    <input type="text" 
                                           wire:model="roomNumbers.{{ $selectedRoom->id }}" 
                                           placeholder="Enter room no"
                                           value="{{ $selectedRoom->room_no }}"
                                           class="w-24 px-2 py-1 text-sm border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                                    <button wire:click="updateRoomNumber({{ $selectedRoom->id }})"
                                            class="px-2 py-1 text-xs bg-primary-500 text-white rounded-lg hover:bg-primary-600">
                                        Save
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button wire:click="$set('selectedRoomGender', null); $set('showGenderPrompt', true)" class="text-sm text-gray-500 hover:text-gray-700">
                                Change Gender
                            </button>
                            <button wire:click="$set('showRoomLayout', false); $set('selectedRoomId', null)" class="text-sm text-gray-500 hover:text-gray-700">
                                Change Room
                            </button>
                        </div>
                    </div>

                    {{-- Gender Info --}}
                    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center gap-2">
                            @php
                                $genderIcon = match($selectedRoomGender) {
                                    'male' => '♂️',
                                    'female' => '♀️',
                                    'other' => '⚧',
                                    'mixed' => '👥',
                                    default => ''
                                };
                            @endphp
                            <span class="text-lg">{{ $genderIcon }}</span>
                            <span class="font-medium">This room is for: {{ ucfirst($selectedRoomGender) }}</span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @for($i = 1; $i <= $selectedRoom->no_of_beds; $i++)
                            @php
                                $guestName = $this->getGuestForBed($selectedRoom->id, $i);
                                $bookingId = $this->getBookingIdForBed($selectedRoom->id, $i);
                                $assignmentId = $this->getAssignmentIdForBed($selectedRoom->id, $i);
                            @endphp
                            
                            <div class="relative border-2 rounded-lg p-4 transition-all cursor-pointer
                                        @if($guestName)
                                            bg-blue-50 border-blue-400 hover:border-blue-600
                                        @else
                                            bg-green-50 border-green-400 hover:border-green-600 hover:shadow-md
                                        @endif"
                                 wire:click="selectBed({{ $selectedRoom->id }}, {{ $i }})">
                                
                                <div class="absolute -top-3 -left-3 w-8 h-8 rounded-full bg-gray-800 text-white flex items-center justify-center font-bold shadow-lg">
                                    {{ $i }}
                                </div>
                                
                                <div class="mt-2 text-center">
                                    @if($guestName)
                                        <div class="text-sm font-medium text-blue-700 truncate" title="{{ $guestName }}">
                                            {{ \Str::limit($guestName, 12) }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">#{{ $bookingId }}</div>
                                        <button wire:click="removeGuestFromBed({{ $selectedRoom->id }}, {{ $i }})"
                                                class="mt-2 text-xs text-red-500 hover:text-red-700"
                                                onclick="event.stopPropagation(); return confirm('Remove guest from this bed?')">
                                            Remove
                                        </button>
                                    @else
                                        <div class="text-sm text-gray-600 font-medium">Available</div>
                                        <div class="mt-2 text-xs text-green-600">Click to assign</div>
                                    @endif
                                </div>
                                
                                <div class="mt-2 text-center {{ $guestName ? 'text-blue-400' : 'text-green-400' }}">
                                    <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4M20 12v6M4 12v6M4 12V6a2 2 0 012-2h12a2 2 0 012 2v6"></path>
                                    </svg>
                                </div>
                            </div>
                        @endfor
                    </div>

                    @if($this->getAvailableBedsCount($selectedRoom->id) > 0 && !empty($this->unassignedGuests))
                        <div class="mt-4 text-center">
                            <button wire:click="openBulkAssign({{ $selectedRoom->id }})"
                                    class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors">
                                Bulk Assign Multiple Guests
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        @endif
    </div>

    {{-- Select Guest Modal --}}
    <x-filament::modal id="select-guest-modal" width="lg">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <span>Select Guest for Bed #{{ $selectedBedNumber }}</span>
                @if($selectedRoomGender)
                    <span class="text-sm font-normal px-2 py-1 rounded-full 
                        @if($selectedRoomGender == 'male') bg-blue-100 text-blue-700
                        @elseif($selectedRoomGender == 'female') bg-pink-100 text-pink-700
                        @elseif($selectedRoomGender == 'other') bg-purple-100 text-purple-700
                        @else bg-gray-100 text-gray-700
                        @endif
                    ">
                        @php
                            $genderIcon = match($selectedRoomGender) {
                                'male' => '♂️',
                                'female' => '♀️',
                                'other' => '⚧',
                                'mixed' => '👥',
                                default => ''
                            };
                        @endphp
                        {{ $genderIcon }} {{ ucfirst($selectedRoomGender) }} Room
                    </span>
                @endif
            </div>
        </x-slot>
        
        <div class="space-y-4">
            
              <div class="text-center py-8">
                    <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    
                    <div class="space-y-4">
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            
                             <input type="checkbox" 
       value="{{ $package_id }}"
       wire:click="toggleGuestSelection22({{ $package_id }}, {{ $selectedBookingId }}, '{{ $date }}', {{ $selectedRoomId }}, {{ $selectedBedNumber }})"
       class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
       {{ in_array($package_id, $selectedGuests) ? 'checked' : '' }}>

                            <h4 class="text-sm font-medium text-gray-700 mb-3">📋 Assignment Details</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500">Package ID</p>
                                    <p class="font-medium text-gray-800">#{{ $package_id }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Booking ID</p>
                                    <p class="font-medium text-gray-800">#{{ $selectedBookingId }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Start Date</p>
                                    <p class="font-medium text-gray-800">{{ $date }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Room</p>
                                    <p class="font-medium text-gray-800">#{{ $selectedRoomId }} (Bed #{{ $selectedBedNumber }})</p>
                                </div>
                            </div>
                        </div>

                        <p class="text-gray-500 mt-2">No unassigned guests available</p>
                    </div>
                </div>
            @if(empty($unassignedGuests))
              
            @else
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @foreach($unassignedGuests as $guest)
                        <div class="border rounded-lg p-4 hover:bg-gray-50 cursor-pointer transition-all"
                             wire:click="assignGuestToBed('{{ $guest['id'] }}')">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h4 class="font-medium text-lg">{{ $guest['name'] }}</h4>
                                    <div class="flex items-center gap-3 mt-2">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm
                                            @if($guest['gender'] == 'female') bg-pink-100 text-pink-700
                                            @elseif($guest['gender'] == 'other') bg-purple-100 text-purple-700
                                            @else bg-blue-100 text-blue-700
                                            @endif
                                        ">
                                            {{ ucfirst($guest['gender']) }}
                                        </span>
                                        <span class="text-sm text-gray-600">
                                            <span class="font-medium">Sharing:</span> {{ $guest['sharing_type'] }}
                                        </span>
                                    </div>
                                    @if($guest['contact'])
                                        <p class="text-sm text-gray-500 mt-2">
                                            📞 {{ $guest['contact'] }}
                                        </p>
                                    @endif
                                </div>
                                <svg class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        
        <x-slot name="footer">
            <x-filament::button color="gray" wire:click="$dispatch('close-modal', {id: 'select-guest-modal'})">
                Cancel
            </x-filament::button>
        </x-slot>
    </x-filament::modal>

    {{-- Bulk Assign Modal --}}
    <x-filament::modal id="bulk-assign-modal" width="2xl">
        <x-slot name="heading">
            Assign Multiple Guests to {{ $selectedRoom ? ucfirst($selectedRoom->room_type) : '' }} Room
        </x-slot>
        
        <div class="space-y-4">
            @if(empty($unassignedGuests))
                <div class="text-center py-8">
                    <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-gray-500 mt-2">No unassigned guests available</p>
                </div>
            @else
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <p class="text-sm text-blue-700">
                        <span class="font-medium">Available beds in this room:</span> {{ $selectedRoom ? $this->getAvailableBedsCount($selectedRoom->id) : 0 }}
                    </p>
                    @if($selectedRoomGender && $selectedRoomGender != 'mixed')
                        <p class="text-xs text-blue-600 mt-1">
                            ⚠️ This room is for <strong>{{ ucfirst($selectedRoomGender) }}</strong> guests only.
                        </p>
                    @endif
                </div>
                
                <div class="grid grid-cols-1 gap-3 max-h-96 overflow-y-auto">
                    @foreach($unassignedGuests as $guest)
                        <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors">
                            <label class="flex items-start gap-4 cursor-pointer">
                                <input type="checkbox" 
                                       value="{{ $guest['id'] }}"
                                       wire:click="toggleGuestSelection('{{ $guest['id'] }}')"
                                       class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                       {{ in_array($guest['id'], $selectedGuests) ? 'checked' : '' }}>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-medium text-lg">{{ $guest['name'] }}</h4>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs
                                            @if($guest['gender'] == 'female') bg-pink-100 text-pink-700
                                            @elseif($guest['gender'] == 'other') bg-purple-100 text-purple-700
                                            @else bg-blue-100 text-blue-700
                                            @endif
                                        ">
                                            {{ ucfirst($guest['gender']) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-4 mt-2">
                                        <span class="text-sm text-gray-600">
                                            <span class="font-medium">Sharing:</span> {{ $guest['sharing_type'] }}
                                        </span>
                                        @if($guest['contact'])
                                            <span class="text-sm text-gray-600">
                                                📞 {{ $guest['contact'] }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>
                
                <div class="flex justify-between items-center pt-4 border-t">
                    <div>
                        <span class="text-sm font-medium text-gray-700">{{ count($selectedGuests) }} guests selected</span>
                        @if(count($selectedGuests) > ($selectedRoom ? $this->getAvailableBedsCount($selectedRoom->id) : 0))
                            <p class="text-xs text-red-500 mt-1">Selected more guests than available beds!</p>
                        @endif  
                    </div>
                    <div class="flex gap-2">
                        <x-filament::button color="gray" wire:click="$set('selectedGuests', [])">
                            Clear All
                        </x-filament::button>
                        <x-filament::button 
                            wire:click="assignMultipleGuests"
                            :disabled="empty($selectedGuests) || count($selectedGuests) > ($selectedRoom ? $this->getAvailableBedsCount($selectedRoom->id) : 0)">
                            Assign Selected
                        </x-filament::button>
                    </div>
                </div>
            @endif
        </div>
        
        <x-slot name="footer">
            <x-filament::button color="gray" wire:click="$dispatch('close-modal', {id: 'bulk-assign-modal'})">
                Close
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</x-filament::page>