<x-filament-panels::page>
    
    {{-- Pending Header --}}
    <div class="super-pending-header">
        <div class="super-header-content">
            <div class="super-header-left">
                <div class="header-icon-wrapper">
                    <svg class="header-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </div>
                <div class="header-text">
                    <span class="header-title">Pending Seat Allotments</span>
                    <span class="header-subtitle">Bookings waiting for seat assignment</span>
                </div>
            </div>
            
            <div class="super-header-right">
                <div class="pending-badge-large">
                    <span class="pending-number">{{ $totalPendingCount }}</span>
                    <span class="pending-label">Pending</span>
                </div>
                
                @if($totalPendingCount > 0)
                    <button class="view-all-btn" wire:click="openPendingBookingsModal">
                        <span>View All</span>
                        <svg class="btn-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                @endif
            </div>
        </div>
        
        {{-- Quick Stats --}}
        @if($totalPendingCount > 0)
            <div class="quick-stats">
                <div class="stat-item">
                    <span class="stat-value">{{ $totalPendingCount }}</span>
                    <span class="stat-label">Total Bookings</span>
                </div>
                @php
                    $totalPassengers = collect($this->allPendingBookings)->sum('passenger_count');
                @endphp
                <div class="stat-item">
                    <span class="stat-value">{{ $totalPassengers }}</span>
                    <span class="stat-label">Total Passengers</span>
                </div>
                @php
                    $uniquePackages = collect($this->allPendingBookings)->pluck('package_title')->unique()->count();
                @endphp
                <div class="stat-item">
                    <span class="stat-value">{{ $uniquePackages }}</span>
                    <span class="stat-label">Packages</span>
                </div>
            </div>
        @endif
    </div>
    
    {{ $this->form }}

    <div class="main-grid">
        {{-- Left: Buses & Seats --}}
        <div class="buses-section">
            @if ($package && $date)
                @php
                    $packageDate = \App\Models\PackageDates::where('package_id', $package->id)
                        ->where('start_date', $date)
                        ->first();
                        
                    $currentDateVehicles = $package->packageVehicles->filter(function ($vehicle) use ($packageDate) {
                        return $vehicle->package_date_id == $packageDate->id;
                    });
                @endphp

                <div class="section-header">
                    <h2 class="section-title">Vehicles & Seats</h2>
                    <button class="btn-primary" wire:click="addVehicle">
                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Vehicle
                    </button>
                </div>

                <div class="buses-grid">
                    @foreach ($currentDateVehicles as $bus)
                        <div class="bus-card" id="bus-content-{{ $bus->id }}">
                            <div class="bus-header">
                                <div class="bus-info">
                                    <h3 class="bus-title">{{$package->title}} ({{date('d M, Y',strtotime($date))}})</h3>
                                    <span class="bus-type">{{ $bus->label }}</span>
                                </div>
                                <div class="bus-actions">
                                    <button class="btn-download" onclick="downloadBusImage(this)" data-bus-id="{{ $bus->id }}">
                                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                        Download
                                    </button>
                                    <button class="btn-danger btn-sm"
                                        onclick="if(confirm('Are you sure you want to remove this bus?')) { @this.call('removeBus', {{ $bus->id }}) }"
                                        title="Remove this bus">
                                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                        Remove
                                    </button>
                                </div>
                            </div>

                            <div class="bus-content">
                                <div class="seat-layout-container">
                                    <div class="seat-layout">
                                        @php
                                            $totalSeats = $bus->seats->count();
                                            $rowPattern = $bus->vehicle->vehicle_row;
                                            $rows = explode('-', $rowPattern);
                                            $leftSeatsPerRow = intval($rows[0]);
                                            $rightSeatsPerRow = intval($rows[1]);
                                            $lastRowSeats = $rowPattern == '2-2' ? 5 : 4;

                                            $seatCounter = 0;
                                            $seats = $bus->seats->sortBy('seat_no')->values();
                                        @endphp

                                        {{-- Main Rows --}}
                                        @while ($seatCounter < $totalSeats - $lastRowSeats)
                                            <div class="seat-row">
                                                {{-- Left Seats --}}
                                                @for ($i = 0; $i < $leftSeatsPerRow && $seatCounter < $totalSeats - $lastRowSeats; $i++, $seatCounter++)
                                                    @php $seat = $seats[$seatCounter]; @endphp
                                                    <label class="seat 
                                                        @if ($seat->is_captain == 1)
                                                            seat-captain
                                                        @elseif ($seat->booking_id != null)
                                                            @if ($seat->gender == 'female') seat-booked-female
                                                            @elseif ($seat->gender == 'male') seat-booked-male
                                                            @elseif ($seat->gender == 'other') seat-booked-other
                                                            @else seat-booked
                                                            @endif
                                                        @elseif ($seat->gender == 'female') seat-female
                                                        @elseif ($seat->gender == 'male') seat-male
                                                        @elseif ($seat->gender == 'other') seat-other
                                                        @endif
                                                        @if (in_array($seat->id, $selectedSeats) && !$seat->is_captain) seat-selected @endif">
                                                        
                                                        {{-- Captain seat ke liye checkbox DISABLE --}}
                                                        @if($seat->is_captain == 1)
                                                            <input type="checkbox" disabled>
                                                        @else
                                                            <input type="checkbox"
                                                                wire:click="toggleSeat({{ $seat->id }})"
                                                                value="{{ $seat->seat_no }}"
                                                                @if (in_array($seat->id, $selectedSeats)) checked @endif
                                                                @if ($seat->booking_id != null) disabled @endif>
                                                        @endif
                                                        
                                                        <span class="seat-number">
                                                            @if($seat->is_captain == 1)👑 @endif
                                                            {{ $seat->seat_no }}
                                                            @if($seat->gender && !$seat->is_captain)
                                                                <span class="gender-icon">
                                                                    @if($seat->gender == 'male')♂
                                                                    @elseif($seat->gender == 'female')♀
                                                                    @elseif($seat->gender == 'other')⚧
                                                                    @endif
                                                                </span>
                                                            @endif
                                                        </span>

                                                        @if ($seat->booking_id != null)
                                                            <div class="seat-tooltip">Booked: {{ ucfirst($seat->gender) ?? 'No gender' }}</div>
                                                        @elseif($seat->gender && !$seat->is_captain)
                                                            <div class="seat-tooltip">{{ ucfirst($seat->gender) }}</div>
                                                        @elseif($seat->is_captain == 1)
                                                            <div class="seat-tooltip">Captain Seat (Reserved)</div>
                                                        @endif
                                                    </label>
                                                @endfor

                                                <div class="passage"></div>

                                                {{-- Right Seats --}}
                                                <div class="double-seats">
                                                    @for ($i = 0; $i < $rightSeatsPerRow && $seatCounter < $totalSeats - $lastRowSeats; $i++, $seatCounter++)
                                                        @php $seat = $seats[$seatCounter]; @endphp
                                                        <label class="seat 
                                                            @if ($seat->is_captain == 1)
                                                                seat-captain
                                                            @elseif ($seat->booking_id != null)
                                                                @if ($seat->gender == 'female') seat-booked-female
                                                                @elseif ($seat->gender == 'male') seat-booked-male
                                                                @elseif ($seat->gender == 'other') seat-booked-other
                                                                @else seat-booked
                                                                @endif
                                                            @elseif ($seat->gender == 'female') seat-female
                                                            @elseif ($seat->gender == 'male') seat-male
                                                            @elseif ($seat->gender == 'other') seat-other
                                                            @endif
                                                            @if (in_array($seat->id, $selectedSeats) && !$seat->is_captain) seat-selected @endif">
                                                            
                                                            @if($seat->is_captain == 1)
                                                                <input type="checkbox" disabled>
                                                            @else
                                                                <input type="checkbox"
                                                                    wire:click="toggleSeat({{ $seat->id }})"
                                                                    value="{{ $seat->seat_no }}"
                                                                    @if (in_array($seat->id, $selectedSeats)) checked @endif
                                                                    @if ($seat->booking_id != null) disabled @endif>
                                                            @endif
                                                            
                                                            <span class="seat-number">
                                                                @if($seat->is_captain == 1)👑 @endif
                                                                {{ $seat->seat_no }}
                                                                @if($seat->gender && !$seat->is_captain)
                                                                    <span class="gender-icon">
                                                                        @if($seat->gender == 'male')♂
                                                                        @elseif($seat->gender == 'female')♀
                                                                        @elseif($seat->gender == 'other')⚧
                                                                        @endif
                                                                    </span>
                                                                @endif
                                                            </span>
                                                            
                                                            @if ($seat->booking_id != null)
                                                                <div class="seat-tooltip">Booked: {{ ucfirst($seat->gender) ?? 'No gender' }}</div>
                                                            @elseif($seat->gender && !$seat->is_captain)
                                                                <div class="seat-tooltip">{{ ucfirst($seat->gender) }}</div>
                                                            @elseif($seat->is_captain == 1)
                                                                <div class="seat-tooltip">Captain Seat (Reserved)</div>
                                                            @endif
                                                        </label>
                                                    @endfor
                                                </div>
                                            </div>
                                        @endwhile

                                        {{-- Last Row --}}
                                        <div class="back-row">
                                            @for ($i = $seatCounter; $i < $totalSeats; $i++)
                                                @php $seat = $seats[$i]; @endphp
                                                <label class="seat 
                                                    @if ($seat->is_captain == 1)
                                                        seat-captain
                                                    @elseif ($seat->booking_id != null)
                                                        @if ($seat->gender == 'female') seat-booked-female
                                                        @elseif ($seat->gender == 'male') seat-booked-male
                                                        @elseif ($seat->gender == 'other') seat-booked-other
                                                        @else seat-booked
                                                        @endif
                                                    @elseif ($seat->gender == 'female') seat-female
                                                    @elseif ($seat->gender == 'male') seat-male
                                                    @elseif ($seat->gender == 'other') seat-other
                                                    @endif
                                                    @if (in_array($seat->id, $selectedSeats) && !$seat->is_captain) seat-selected @endif">
                                                    
                                                    @if($seat->is_captain == 1)
                                                        <input type="checkbox" disabled>
                                                    @else
                                                        <input type="checkbox" wire:click="toggleSeat({{ $seat->id }})"
                                                            value="{{ $seat->seat_no }}"
                                                            @if (in_array($seat->id, $selectedSeats)) checked @endif
                                                            @if ($seat->booking_id != null) disabled @endif>
                                                    @endif
                                                    
                                                    <span class="seat-number">
                                                        @if($seat->is_captain == 1)👑 @endif
                                                        {{ $seat->seat_no }}
                                                        @if($seat->gender && !$seat->is_captain)
                                                            <span class="gender-icon">
                                                                @if($seat->gender == 'male')♂
                                                                @elseif($seat->gender == 'female')♀
                                                                @elseif($seat->gender == 'other')⚧
                                                                @endif
                                                            </span>
                                                        @endif
                                                    </span>

                                                    @if ($seat->booking_id != null)
                                                        <div class="seat-tooltip">Booked: {{ ucfirst($seat->gender) ?? 'No gender' }}</div>
                                                    @elseif($seat->gender && !$seat->is_captain)
                                                        <div class="seat-tooltip">{{ ucfirst($seat->gender) }}</div>
                                                    @elseif($seat->is_captain == 1)
                                                        <div class="seat-tooltip">Captain Seat (Reserved)</div>
                                                    @endif
                                                </label>
                                            @endfor
                                        </div>
                                    </div>
                                </div>

                                {{-- Assigned Bookings --}}
                                <div class="assigned-bookings">
                                    <h4 class="assigned-title">Assigned Seats</h4>
                                    <div class="bookings-list">
                                        @if ($bookingsWithSeats && $bookingsWithSeats->count() > 0)
                                            @foreach ($bookingsWithSeats as $row)
                                                @if (isset($row['seats_by_bus'][$bus->id]) && is_array($row['seats_by_bus'][$bus->id]))
                                                    <div class="assigned-booking">
                                                        <div class="booking-header">
                                                            <span class="guest-name">{{ $row['full_name'] }}</span>
                                                            <span class="guest-pax">{{ $row['passenger_count'] ?? array_sum(array_column($row['active_cost'] ?? [], 'quantity')) }} pax</span>
                                                        </div>
                                                        
                                                        @if(!empty($row['gender_breakdown']) && is_array($row['gender_breakdown']))
                                                            <div class="gender-breakdown">
                                                                @if(($row['gender_breakdown']['male'] ?? 0) > 0)
                                                                    <span class="gender-badge male">
                                                                        <span class="gender-dot male-dot"></span>
                                                                        {{ $row['gender_breakdown']['male'] }} Male
                                                                    </span>
                                                                @endif
                                                                @if(($row['gender_breakdown']['female'] ?? 0) > 0)
                                                                    <span class="gender-badge female">
                                                                        <span class="gender-dot female-dot"></span>
                                                                        {{ $row['gender_breakdown']['female'] }} Female
                                                                    </span>
                                                                @endif
                                                                @if(($row['gender_breakdown']['other'] ?? 0) > 0)
                                                                    <span class="gender-badge other">
                                                                        <span class="gender-dot other-dot"></span>
                                                                        {{ $row['gender_breakdown']['other'] }} Other
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        @endif
                                                        
                                                        <div class="seat-assignments">
                                                            @if(isset($row['seats_with_details'][$bus->id]) && is_array($row['seats_with_details'][$bus->id]))
                                                                @foreach($row['seats_with_details'][$bus->id] as $seatDetail)
                                                                    @if(!isset($seatDetail['is_captain']) || !$seatDetail['is_captain'])
                                                                        <div class="seat-assignment-card">
                                                                            <div class="seat-info">
                                                                                <span class="seat-number">Seat {{ $seatDetail['seat_no'] }}</span>
                                                                                <span class="gender-label 
                                                                                    @if($seatDetail['gender'] == 'male') male-text
                                                                                    @elseif($seatDetail['gender'] == 'female') female-text
                                                                                    @elseif($seatDetail['gender'] == 'other') other-text
                                                                                    @endif">
                                                                                    @if($seatDetail['gender'] == 'male') Male
                                                                                    @elseif($seatDetail['gender'] == 'female') Female
                                                                                    @elseif($seatDetail['gender'] == 'other') Other
                                                                                    @endif
                                                                                </span>
                                                                            </div>
                                                                            <div class="passenger-details">
                                                                                <span class="passenger-name">{{ $seatDetail['passenger_name'] }}</span>
                                                                                @if(!empty($seatDetail['sharing_type']) && $seatDetail['sharing_type'] != 'N/A' && $seatDetail['sharing_type'] != 'Unknown')
                                                                                    <span class="sharing-badge">{{ $seatDetail['sharing_type'] }}</span>
                                                                                @endif
                                                                                @if(!empty($seatDetail['passenger_contact']))
                                                                                    <span class="contact-info">{{ $seatDetail['passenger_contact'] }}</span>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                @endforeach
                                                            @else
                                                                @foreach($row['seats_by_bus'][$bus->id] as $index => $seat)
                                                                    @if(!isset($seat['is_captain']) || !$seat['is_captain'])
                                                                        @php
                                                                            $gender = isset($row['seats_by_bus'][$bus->id]['genders'][$index]) 
                                                                                ? $row['seats_by_bus'][$bus->id]['genders'][$index] 
                                                                                : 'unknown';
                                                                        @endphp
                                                                        <div class="seat-assignment-card simple">
                                                                            <div class="seat-info">
                                                                                <span class="seat-number">Seat {{ $seat }}</span>
                                                                                @if($gender != 'unknown')
                                                                                    <span class="gender-label 
                                                                                        @if($gender == 'male') male-text
                                                                                        @elseif($gender == 'female') female-text
                                                                                        @elseif($gender == 'other') other-text
                                                                                        @endif">
                                                                                        @if($gender == 'male') Male
                                                                                        @elseif($gender == 'female') Female
                                                                                        @elseif($gender == 'other') Other
                                                                                        @endif
                                                                                    </span>
                                                                                @endif
                                                                            </div>
                                                                            <div class="passenger-details">
                                                                                <span class="passenger-name text-muted">Passenger {{ $index + 1 }}</span>
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                @endforeach
                                                            @endif
                                                        </div>
                                                        
                                                        <button class="btn-remove-seats"
                                                            wire:click="removeSeatAllotment({{ $row['id'] }})"
                                                            wire:confirm="Are you sure you want to remove all seats from {{ $row['full_name'] }}?">
                                                            <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                                </path>
                                                            </svg>
                                                            Remove
                                                        </button>
                                                    </div>
                                                @endif
                                            @endforeach
                                        @else
                                            <div class="no-assignments">
                                                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                                    </path>
                                                </svg>
                                                <h4>No seats assigned yet</h4>
                                                <p>Select seats and assign them to bookings</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Captain Assignment Section --}}
                        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px; margin-top:20px;">
                            @foreach ($seats as $seat)
                                @continue($seat->booking_id != NULL)
                                <div style="display:flex;align-items:center;gap:10px;padding:10px;background:#f9fafb;border-radius:8px;border:1px solid #e5e7eb;">
                                    <div style="width:40px;height:40px;background:#e5e7eb;border-radius:6px;display:flex;align-items:center;justify-content:center;font-weight:bold;">
                                        {{ $seat->seat_no }}
                                    </div>
                                    <div style="flex:1;">
                                        <div style="font-size:0.8rem;color:#6b7280;">{{ $bus->label }}</div>
                                        <div style="font-weight:500;">Seat {{ $seat->seat_no }}</div>
                                    </div>
                                    <label style="display:flex;align-items:center;gap:5px;cursor:pointer;">
                                        <input type="checkbox"
                                            wire:click="toggleDirectCaptain({{ $seat->id }}, {{ $seat->seat_no }})"
                                            @if($seat->is_captain) checked @endif
                                            style="width:18px;height:18px;accent-color:#f59e0b;">
                                        <span style="color:#f59e0b;font-weight:bold;">👑</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                    
                    {{-- Gender Legend --}}
                    <div class="gender-legend">
                        <span class="legend-item"><span class="legend-color male"></span> Men</span>
                        <span class="legend-item"><span class="legend-color female"></span> Women</span>
                        <span class="legend-item"><span class="legend-color other"></span> Other</span>
                        <span class="legend-item"><span class="legend-color selected"></span> Selected</span>
                        <span class="legend-item"><span class="legend-color booked"></span> Booked</span>
                        <span class="legend-item"><span class="legend-color captain"></span> Captain</span>
                    </div>
                </div>
            @else
                <div class="empty-state">
                    <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path>
                    </svg>
                    <h3>Select Package & Date</h3>
                    <p>Choose a package and date to start managing seat allotments</p>
                </div>
            @endif
        </div>

        {{-- Right: Bookings --}}
        <div class="bookings-section">
            <div class="section-header">
                <h2 class="section-title">Available Bookings</h2>
                <div class="selection-info">
                    <span class="selected-count">{{ count($selectedSeats) }} seats selected</span>
                </div>
            </div>

            <div class="bookings-list">
                @if ($bookings && $bookings->count() > 0)
                    @foreach ($bookings as $booking)
                        @php
                            $isActive = in_array($booking['id'], $activeBookings);
                            $genderBreakdown = $booking['gender_breakdown'] ?? ['male' => 0, 'female' => 0, 'other' => 0];
                            $sharingBreakdown = $booking['sharing_breakdown'] ?? [];
                        @endphp

                        <div class="booking-card @if ($isActive) booking-active @else booking-inactive @endif"
                            @if($isActive) wire:click="selectBooking({{ $booking['id'] }})" @endif>
                            
                            <div class="booking-info">
                                <div>
                                    <span class="guest-name">{{ $booking['full_name'] }}</span>
                                    <span class="guest-pax">{{ $booking['passenger_count'] }} pax</span>
                                </div>
                                <div class="booking-actions">
                                    <span class="booking-id">#{{ $booking['id'] }}</span>
                                </div>
                            </div>
                            
                            @if(($genderBreakdown['male'] ?? 0) > 0 || ($genderBreakdown['female'] ?? 0) > 0 || ($genderBreakdown['other'] ?? 0) > 0)
                                <div class="gender-breakdown">
                                    @if($genderBreakdown['male'] > 0)
                                        <span class="gender-badge male">♂ {{ $genderBreakdown['male'] }}</span>
                                    @endif
                                    @if($genderBreakdown['female'] > 0)
                                        <span class="gender-badge female">♀ {{ $genderBreakdown['female'] }}</span>
                                    @endif
                                    @if($genderBreakdown['other'] > 0)
                                        <span class="gender-badge other">⚧ {{ $genderBreakdown['other'] }}</span>
                                    @endif
                                </div>
                            @endif
                            
                            @if(!empty($sharingBreakdown) && is_array($sharingBreakdown))
                                <div class="sharing-breakdown">
                                    @foreach($sharingBreakdown as $type => $count)
                                        <span class="sharing-badge">{{ $type }}: {{ $count }}</span>
                                    @endforeach
                                </div>
                            @endif
                            
                            @if(!empty($booking['passengers']) && is_array($booking['passengers']))
                                <div class="passenger-summary">
                                    <div class="passenger-names">
                                        @foreach($booking['passengers'] as $passenger)
                                            @if(is_array($passenger))
                                                <span class="passenger-name-tag 
                                                    @if(($passenger['gender'] ?? 'male') == 'male') male-tag
                                                    @elseif(($passenger['gender'] ?? '') == 'female') female-tag
                                                    @else other-tag
                                                    @endif">
                                                    {{ Str::limit($passenger['name'] ?? 'Unknown', 15) }}
                                                    <span class="mini-gender">
                                                        @if(($passenger['gender'] ?? 'male') == 'male')♂
                                                        @elseif(($passenger['gender'] ?? '') == 'female')♀
                                                        @else⚧
                                                        @endif
                                                    </span>
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            <div class="booking-status">
                                @if ($isActive)
                                    <span class="status-ready">✓ Ready to assign ({{ $booking['passenger_count'] }} seats)</span>
                                @else
                                    <span class="status-pending">Select {{ $booking['passenger_count'] }} seats</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="empty-bookings">
                        <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                            </path>
                        </svg>
                        <h4>No bookings available</h4>
                        <p>All bookings have been assigned or no bookings exist for this date</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    {{-- Pending Bookings Modal --}}
    @if ($showPendingBookingsModal)
        <div class="modal-overlay" wire:click="closePendingBookingsModal">
            <div class="modal-content super-pending-modal" wire:click.stop>
                <div class="modal-header">
                    <h3>Pending Bookings <span class="header-badge">{{ $totalPendingCount }}</span></h3>
                    <button type="button" class="modal-close" wire:click="closePendingBookingsModal">&times;</button>
                </div>
                
                <div class="modal-body">
                    @if(count($allPendingBookings) > 0)
                        <div class="search-container">
                            <div class="search-wrapper">
                                <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <input type="text" class="search-input" placeholder="Search by name, email, phone, package..." wire:model.live="searchTerm">
                                @if($searchTerm)
                                    <button class="clear-search" wire:click="$set('searchTerm', '')">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                            
                            <div class="filter-tabs">
                                <button class="filter-tab {{ !$filterStatus ? 'active' : '' }}" wire:click="$set('filterStatus', '')">All ({{ $totalPendingCount }})</button>
                                <button class="filter-tab {{ $filterStatus == 'filled' ? 'active' : '' }}" wire:click="$set('filterStatus', 'filled')">✅ Filled</button>
                                <button class="filter-tab {{ $filterStatus == 'unfilled' ? 'active' : '' }}" wire:click="$set('filterStatus', 'unfilled')">⏳ Unfilled</button>
                            </div>
                            
                            <div class="search-stats">Showing {{ count($filteredPendingBookings) }} of {{ $totalPendingCount }} bookings</div>
                        </div>
                        
                        <div class="pending-table-container">
                            <table class="pending-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer Details</th>
                                        <th>Package & Date</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Link</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($filteredPendingBookings as $booking)
                                        @php
                                            $filledCount = $booking['filled_count'] ?? 0;
                                            $totalPassengers = $booking['passenger_count'];
                                            $isFullyFilled = ($filledCount == $totalPassengers && $totalPassengers > 0);
                                            $filledPercentage = $totalPassengers > 0 ? round(($filledCount / $totalPassengers) * 100) : 0;
                                        @endphp
                                        <tr class="{{ $isFullyFilled ? 'row-filled' : 'row-unfilled' }}">
                                            <td><span class="booking-id-badge">#{{ $booking['id'] }}</span></td>
                                            <td>
                                                <div class="customer-info">
                                                    <span class="customer-name">{{ $booking['full_name'] }}</span>
                                                    <span class="customer-pax">{{ $totalPassengers }} pax</span>
                                                    <div class="filled-progress">
                                                        <div class="progress-bar"><div class="progress-fill" style="width: {{ $filledPercentage }}%"></div></div>
                                                        <span class="filled-text {{ $isFullyFilled ? 'text-success' : 'text-warning' }}">{{ $filledCount }}/{{ $totalPassengers }} filled</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="package-info">
                                                    <span class="package-name">{{ Str::limit($booking['package_title'], 20) }}</span>
                                                    <span class="package-date">{{ $booking['start_date'] }}</span>
                                                    <span class="booking-time">{{ $booking['created_at'] }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="contact-info">
                                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking['phone']) }}?text={{ urlencode('Pending booking #'.$booking['id'].' - '.($isFullyFilled ? 'Ready for seat selection' : 'Please complete your details')) }}" class="phone" target="_blank" title="Open WhatsApp">
                                                        <svg class="contact-icon-small" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                                        </svg>
                                                        <span>{{ $booking['phone'] }}</span>
                                                    </a>
                                                    <div class="email">
                                                        <svg class="contact-icon-small" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                        </svg>
                                                        <span>{{ Str::limit($booking['email'], 20) }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="status-badge {{ $isFullyFilled ? 'status-filled' : 'status-unfilled' }}">
                                                    @if($isFullyFilled)
                                                        <span class="status-icon">✅</span><span>Filled</span>
                                                    @else
                                                        <span class="status-icon">⏳</span><span>Pending</span><span class="pending-count">{{ $totalPassengers - $filledCount }}</span>
                                                    @endif
                                                </div>
                                                @if(!empty($booking['passengers']) && count($booking['passengers']) > 0 && !$isFullyFilled)
                                                    <div class="passenger-preview">
                                                        @foreach($booking['passengers'] as $passenger)
                                                            @if(empty($passenger['name']) || $passenger['name'] == 'Unknown')
                                                                <span class="passenger-dot missing" title="Missing details"></span>
                                                            @else
                                                                <span class="passenger-dot filled" title="{{ $passenger['name'] }}"></span>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="token-info">
                                                    @php
                                                        $token = $booking['booking_token'] ?? 'Not generated';
                                                        $bookingLink = $token && $token != 'Not generated' ? 'https://tripogramclub.com/booking-detail?id=' . $token : '#';
                                                    @endphp
                                                    @if($token && $token != 'Not generated')
                                                        <div style="display: flex; align-items: center; gap: 8px;">
                                                            <a href="{{ $bookingLink }}" target="_blank" style="color: #4158D0; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                                                <span class="token-badge" title="{{ $token }}">{{ Str::limit($token, 8) }}</span>
                                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                                </svg>
                                                            </a>
                                                            <button class="copy-token-btn" onclick="navigator.clipboard.writeText('{{ $bookingLink }}'); showNotification('Link copied!')" title="Copy booking link">
                                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    @else
                                                        <span class="token-badge not-generated">Not generated</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="view-assign-btn" wire:click="$set('package_id', {{ $booking['package_id'] }}); $set('date', '{{ $booking['start_date'] }}'); closePendingBookingsModal()" title="View and assign seats">
                                                        <svg class="btn-icon-small" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                        </svg>
                                                    </button>
                                                    <button class="email-btn" wire:click="sendBookingEmail({{ $booking['id'] }})" wire:loading.attr="disabled" title="Send email to {{ $booking['email'] }}">
                                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                        </svg>
                                                        <span wire:loading.remove>Send</span>
                                                        <span wire:loading>Sending...</span>
                                                    </button>
                                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking['phone']) }}?text={{ urlencode('Pending booking #'.$booking['id'].' - '.($isFullyFilled ? 'Ready for seat selection' : 'Please complete your details').' Please fill your details on this link: https://tripogramclub.com/booking-detail?id='.($booking['booking_token'] ?? 'null')) }}" class="whatsapp-btn" target="_blank" title="Send WhatsApp message">
                                                        <svg fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.99.58 3.93 1.68 5.58L2.23 22l4.64-1.48c1.63.88 3.47 1.35 5.37 1.35 5.46 0 9.91-4.45 9.91-9.91 0-5.46-4.45-9.92-9.91-9.92zm0 18.08c-1.67 0-3.3-.45-4.73-1.3l-.34-.2-2.75.88.94-2.67-.22-.35c-.94-1.44-1.44-3.14-1.44-4.91 0-4.56 3.71-8.27 8.27-8.27s8.27 3.71 8.27 8.27-3.71 8.28-8.27 8.28z"></path>
                                                            <path d="M16.47 13.27c-.27-.14-1.59-.78-1.84-.87-.25-.09-.43-.14-.61.14-.18.28-.7.87-.86 1.05-.16.18-.32.2-.59.07-.27-.14-1.14-.42-2.17-1.34-.8-.72-1.34-1.6-1.5-1.88-.16-.28-.02-.43.12-.57.12-.13.27-.34.4-.51.14-.17.18-.29.27-.48.09-.19.05-.36-.02-.5-.07-.14-.61-1.47-.84-2.01-.22-.52-.45-.45-.61-.46h-.52c-.18 0-.48.07-.73.34-.25.27-.95.93-.95 2.27s.97 2.63 1.11 2.81c.14.19 1.89 2.93 4.56 4.02.64.26 1.13.42 1.52.54.64.19 1.23.16 1.69.1.52-.07 1.59-.65 1.81-1.28.22-.63.22-1.17.16-1.28-.07-.11-.25-.18-.52-.31z"></path>
                                                        </svg>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-pending">
                            <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h4>All Caught Up! 🎉</h4>
                            <p>No pending bookings - all seats have been assigned</p>
                        </div>
                    @endif
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" wire:click="closePendingBookingsModal">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Passenger Selection Modal --}}
    @if ($showPassengerModal)
        <div class="modal-overlay" wire:click="closePassengerModal">
            <div class="modal-content passenger-modal" wire:click.stop>
                <div class="modal-header">
                    <h3>Assign Passengers to Seats</h3>
                    <button type="button" class="modal-close" wire:click="closePassengerModal">&times;</button>
                </div>
                
                <div class="modal-body">
                    <div class="passenger-modal-grid">
                        <div class="passengers-list-section">
                            <h4>Passengers ({{ count($bookingPassengers) }})</h4>
                            <div class="passengers-grid">
                                @foreach($bookingPassengers as $index => $passenger)
                                    @if(is_array($passenger))
                                        @php
                                            $assignedSeatId = $passengerToSeatMap[$index] ?? null;
                                            $otherAssignedSeatIds = [];
                                            foreach($passengerToSeatMap as $idx => $seatId) {
                                                if($idx != $index && !is_null($seatId)) {
                                                    $otherAssignedSeatIds[] = $seatId;
                                                }
                                            }
                                        @endphp
                                        <div class="passenger-card @if($assignedSeatId) assigned @endif">
                                            <div class="passenger-card-header">
                                                <span class="passenger-sr">#{{ $loop->iteration }}</span>
                                                <span class="passenger-gender-badge 
                                                    @if(($passenger['gender'] ?? 'male') == 'male') male-badge
                                                    @elseif(($passenger['gender'] ?? '') == 'female') female-badge
                                                    @else other-badge
                                                    @endif">
                                                    @if(($passenger['gender'] ?? 'male') == 'male')♂
                                                    @elseif(($passenger['gender'] ?? '') == 'female')♀
                                                    @else⚧
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="passenger-name">{{ $passenger['name'] ?? 'Unknown' }}</div>
                                            <div class="passenger-details">
                                                <span class="sharing-tag">{{ $passenger['sharing_type'] ?? 'N/A' }}</span>
                                                @if(!empty($passenger['contact']))
                                                    <span class="contact-tag">{{ $passenger['contact'] }}</span>
                                                @endif
                                            </div>
                                            <div class="passenger-assignment">
                                                <select class="seat-select" wire:change="assignPassengerToSeat({{ $index }}, $event.target.value)">
                                                    <option value="">Select Seat</option>
                                                    @foreach($availableSeatsForBooking as $seat)
                                                        @if(!isset($seat['is_captain']) || !$seat['is_captain'])
                                                            @php
                                                                $isSeatTakenByOther = in_array($seat['id'], $otherAssignedSeatIds);
                                                                $isThisPassengerSeat = ($assignedSeatId == $seat['id']);
                                                            @endphp
                                                            @if(!$isSeatTakenByOther || $isThisPassengerSeat)
                                                                <option value="{{ $seat['id'] }}" @if($isThisPassengerSeat) selected @endif>
                                                                    Bus {{ $seat['bus_label'] }} - Seat {{ $seat['seat_no'] }}
                                                                    @if($isThisPassengerSeat) (Selected) @endif
                                                                </option>
                                                            @endif
                                                        @endif
                                                    @endforeach
                                                </select>
                                                @if($assignedSeatId)
                                                    @php $assignedSeat = collect($availableSeatsForBooking)->firstWhere('id', $assignedSeatId); @endphp
                                                    @if($assignedSeat)
                                                        <div style="margin-top: 5px; font-size: 11px; color: #10b981;">✓ Seat {{ $assignedSeat['seat_no'] }} assigned</div>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        
                        <div class="seats-preview-section">
                            <h4>Selected Seats ({{ count($availableSeatsForBooking) }})</h4>
                            <div class="seats-grid">
                                @foreach($availableSeatsForBooking as $seat)
                                    @if(!isset($seat['is_captain']) || !$seat['is_captain'])
                                        @php
                                            $assignedTo = null;
                                            $assignedGender = null;
                                            foreach($passengerToSeatMap as $pIndex => $sId) {
                                                if($sId == $seat['id']) {
                                                    $assignedTo = $bookingPassengers[$pIndex]['name'] ?? 'Unknown';
                                                    $assignedGender = $bookingPassengers[$pIndex]['gender'] ?? 'male';
                                                    break;
                                                }
                                            }
                                        @endphp
                                        <div class="seat-preview-card @if($assignedTo) assigned @endif">
                                            <div class="seat-number">Seat {{ $seat['seat_no'] }}</div>
                                            <div class="bus-label">{{ $seat['bus_label'] }}</div>
                                            @if($assignedTo)
                                                <div class="assigned-to">
                                                    <span class="assigned-badge">✓</span>
                                                    <span class="assigned-name">{{ Str::limit($assignedTo, 20) }}</span>
                                                    <span class="gender-indicator-small {{ $assignedGender }}">
                                                        @if($assignedGender == 'male')♂
                                                        @elseif($assignedGender == 'female')♀
                                                        @else⚧
                                                        @endif
                                                    </span>
                                                </div>
                                            @else
                                                <div class="unassigned">Available</div>
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            
                            @php
                                $assignedCount = count(array_filter($passengerToSeatMap));
                                $totalCount = count($bookingPassengers);
                            @endphp
                            <div class="assignment-summary">
                                <div class="summary-bar"><div class="summary-progress" style="width: {{ $totalCount > 0 ? ($assignedCount / $totalCount) * 100 : 0 }}%"></div></div>
                                <div class="summary-text">{{ $assignedCount }} of {{ $totalCount }} passengers assigned</div>
                                @if($assignedCount < count($availableSeatsForBooking))
                                    <div style="margin-top: 8px; font-size: 11px; color: #f59e0b; text-align: center;">⚠️ {{ count($availableSeatsForBooking) - $assignedCount }} seats still available</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" wire:click="closePassengerModal">Cancel</button>
                    <button type="button" class="btn-primary" wire:click="savePassengerAssignments" @if(count(array_filter($passengerToSeatMap)) != count($bookingPassengers)) disabled @endif>
                        Assign Selected Passengers
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Gender Modal --}}
    @if ($showGenderModal)
        <div class="modal-overlay" wire:click="closeGenderModal">
            <div class="modal-content" wire:click.stop>
                <div class="modal-header">
                    <h3>Select Gender</h3>
                    <button type="button" class="modal-close" wire:click="closeGenderModal">&times;</button>
                </div>
                
                <div class="modal-body">
                    <div class="gender-options">
                        <label class="gender-option @if($selectedGender == 'male') selected @endif" wire:click="$set('selectedGender', 'male')">
                            <div class="gender-icon male">♂</div><span>Male</span>
                        </label>
                        <label class="gender-option @if($selectedGender == 'female') selected @endif" wire:click="$set('selectedGender', 'female')">
                            <div class="gender-icon female">♀</div><span>Female</span>
                        </label>
                        <label class="gender-option @if($selectedGender == 'other') selected @endif" wire:click="$set('selectedGender', 'other')">
                            <div class="gender-icon other">⚧</div><span>Other</span>
                        </label>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" wire:click="closeGenderModal">Cancel</button>
                    <button type="button" class="btn-primary" wire:click="saveGender">Save</button>
                </div>
            </div>
        </div>
    @endif
    {{-- Custom CSS --}}
    <style>
        /* Base Styles with proper scrolling */
        /* Search Bar Styles */

           .seat-captain .seat-number {
            background: #f59e0b !important;
            border-color: #d97706 !important;
            position: relative;
            box-shadow: 0 0 0 2px #f59e0b, 0 2px 8px rgba(245, 158, 11, 0.3);
        }

        .seat-captain .seat-number::before {
            content: '👑';
            position: absolute;
            top: -8px;
            right: -8px;
            font-size: 12px;
            background: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            z-index: 5;
        }

        .seat-captain {
            cursor: not-allowed !important;
        }

        .seat-captain input[type="checkbox"] {
            display: none;
        }

        .legend-color.captain { 
            background: #f59e0b; 
            position: relative;
        }
        .legend-color.captain::after {
            content: '👑';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 10px;
            color: white;
        }

.seat-captain{
    background:#ef4444 !important;
    color:white;
    border:2px solid #dc2626;
}

        .captain-section {
    background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
    border: 2px solid #e5e7eb;
    transition: all 0.3s ease;
}

.captain-section:hover {
    border-color: #f59e0b;
}

.captain-section input[type="checkbox"] {
    transition: all 0.2s ease;
}

.captain-section input[type="checkbox"]:checked {
    transform: scale(1.1);
}

      ..captain-section {
    background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
    border: 2px solid #e5e7eb;
    transition: all 0.3s ease;
}

.captain-section:hover {
    border-color: #f59e0b;
}

.captain-section input[type="checkbox"] {
    transition: all 0.2s ease;
}

.captain-section input[type="checkbox"]:checked {
    transform: scale(1.1);
}

.seat-selected .seat-number {
    box-shadow: 0 0 0 2px #f59e0b, 0 2px 8px rgba(245, 158, 11, 0.4);
}
        .copy-token-btn {
    background: none;
    border: none;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #6b7280;
    text-decoration: none;
    transition: all 0.2s ease;
}

.copy-token-btn:hover {
    background: #f3f4f6;
    color: #4158D0;
    transform: scale(1.1);
}

.copy-token-btn svg {
    width: 14px;
    height: 14px;
}

/* Tooltip */
.copy-token-btn:hover::after {
    content: attr(title);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #1f2937;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 10px;
    white-space: nowrap;
    margin-bottom: 4px;
    z-index: 10;
}
        /* Filter Tabs */
.filter-tabs {
    display: flex;
    gap: 10px;
    margin: 15px 0;
    flex-wrap: wrap;
}

.filter-tab {
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 30px;
    padding: 8px 16px;
    font-size: 0.85rem;
    font-weight: 500;
    color: #4b5563;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-tab:hover {
    background: #e5e7eb;
}

.filter-tab.active {
    background: #4158D0;
    border-color: #4158D0;
    color: white;
}

/* Progress Bar */
.filled-progress {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 4px;
}

.progress-bar {
    flex: 1;
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: #10b981;
    border-radius: 2px;
    transition: width 0.3s ease;
}

.filled-text {
    font-size: 0.7rem;
    font-weight: 500;
    min-width: 45px;
}

.filled-text.text-success {
    color: #10b981;
}

.filled-text.text-warning {
    color: #f59e0b;
}

/* Row States */
.row-filled {
    background-color: #f0fdf4;
}

.row-filled:hover {
    background-color: #dcfce7 !important;
}

.row-unfilled {
    background-color: #fff7ed;
}

.row-unfilled:hover {
    background-color: #ffedd5 !important;
}

/* Status Badge */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge.status-filled {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.status-unfilled {
    background: #fed7aa;
    color: #92400e;
}

.status-icon {
    font-size: 0.9rem;
}

.pending-count {
    background: rgba(0,0,0,0.1);
    padding: 2px 6px;
    border-radius: 12px;
    margin-left: 4px;
}

/* Passenger Preview Dots */
.passenger-preview {
    display: flex;
    gap: 4px;
    margin-top: 6px;
    flex-wrap: wrap;
}

.passenger-dot {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: inline-block;
}

.passenger-dot.filled {
    background: #10b981;
    border: 2px solid #34d399;
}

.passenger-dot.missing {
    background: #f59e0b;
    border: 2px solid #fbbf24;
    position: relative;
}

.passenger-dot.missing::after {
    content: '?';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 10px;
    font-weight: bold;
}

/* WhatsApp Button */
.whatsapp-btn {
    background: #25D366;
    color: white;
    border: none;
    border-radius: 8px;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.whatsapp-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(37, 211, 102, 0.3);
}

.whatsapp-btn svg {
    width: 20px;
    height: 20px;
}

/* Phone Link */
.phone {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #2563eb;
    text-decoration: none;
    transition: all 0.2s ease;
}

.phone:hover {
    color: #1d4ed8;
    text-decoration: underline;
}

/* Table adjustments */
.pending-table {
    min-width: 1600px;
}

.customer-info {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.action-buttons {
    display: flex;
    gap: 6px;
    align-items: center;
    flex-wrap: wrap;
}
.search-container {
    margin-bottom: 20px;
}

.search-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 12px;
    width: 18px;
    height: 18px;
    color: #9ca3af;
}

.search-input {
    width: 100%;
    padding: 12px 40px 12px 40px;
    border: 2px solid #e5e7eb;
    border-radius: 30px;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.search-input:focus {
    outline: none;
    border-color: #4158D0;
    box-shadow: 0 0 0 3px rgba(65, 88, 208, 0.1);
}

.clear-search {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #9ca3af;
    border-radius: 50%;
}

.clear-search:hover {
    background: #f3f4f6;
    color: #4b5563;
}

.clear-search svg {
    width: 16px;
    height: 16px;
}

.search-stats {
    text-align: right;
    font-size: 0.85rem;
    color: #6b7280;
    margin-top: 8px;
}

/* Filled Badge */
.filled-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 500;
    margin-left: 4px;
}

.filled-badge.filled-all {
    background: #d1fae5;
    color: #065f46;
}

.filled-badge.filled-partial {
    background: #fef3c7;
    color: #92400e;
}

/* Token Info */
.token-info {
    display: flex;
    align-items: center;
    gap: 6px;
}

.token-badge {
    background: #f3f4f6;
    color: #4b5563;
    padding: 4px 8px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    font-family: monospace;
}

.token-badge.not-generated {
    background: #fee2e2;
    color: #b91c1c;
}

.copy-token-btn {
    background: none;
    border: none;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #6b7280;
    transition: all 0.2s ease;
}

.copy-token-btn:hover {
    background: #f3f4f6;
    color: #4158D0;
}

.copy-token-btn svg {
    width: 14px;
    height: 14px;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 8px;
    align-items: center;
}

.view-assign-btn {
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.view-assign-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(5, 150, 105, 0.3);
}

.email-btn {
    background: linear-gradient(135deg, #4158D0 0%, #C850C0 100%);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.email-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(65, 88, 208, 0.3);
}

.email-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.email-btn svg {
    width: 16px;
    height: 16px;
}

/* Notification */
.notification-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #10b981;
    color: white;
    padding: 12px 24px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    z-index: 10000;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Table adjustments */
.pending-table {
    min-width: 1400px; /* Increased for new columns */
}

.customer-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
              .super-pending-header {
            background: linear-gradient(135deg, #4158D0 0%, #C850C0 46%, #FFCC70 100%);
            border-radius: 20px;
            padding: 24px 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .super-pending-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .super-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            position: relative;
            z-index: 1;
        }

        .super-header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-icon-wrapper {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }

        .header-icon {
            width: 30px;
            height: 30px;
            color: white;
        }

        .header-text {
            display: flex;
            flex-direction: column;
        }

        .header-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .header-subtitle {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .super-header-right {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .pending-badge-large {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(5px);
            border-radius: 50px;
            padding: 12px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .pending-number {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
        }

        .pending-label {
            font-size: 1rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .view-all-btn {
            background: white;
            border: none;
            border-radius: 50px;
            padding: 14px 30px;
            font-size: 1rem;
            font-weight: 600;
            color: #4158D0;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .view-all-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .btn-arrow {
            width: 20px;
            height: 20px;
            transition: transform 0.3s ease;
        }

        .view-all-btn:hover .btn-arrow {
            transform: translateX(5px);
        }
.super-pending-modal {
    max-width: 1200px !important;
    width: 95% !important;
}

.pending-table-container {
    max-height: 500px;
    overflow-y: auto;
    overflow-x: auto;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background: white;
}

.pending-table-container::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.pending-table-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.pending-table-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.pending-table-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.pending-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1000px;
    font-size: 0.9rem;
}

.pending-table thead {
    position: sticky;
    top: 0;
    background: #f8fafc;
    z-index: 10;
}

.pending-table th {
    padding: 16px 12px;
    text-align: left;
    font-weight: 600;
    color: #1f2937;
    border-bottom: 2px solid #e2e8f0;
    background: #f8fafc;
    white-space: nowrap;
}

.pending-table td {
    padding: 16px 12px;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: middle;
}

.pending-table tbody tr {
    transition: background-color 0.2s ease;
}

.pending-table tbody tr:hover {
    background-color: #f9fafb;
}

/* Booking ID Badge */
.booking-id-badge {
    background: #e5e7eb;
    color: #4b5563;
    padding: 4px 8px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    white-space: nowrap;
}

/* Customer Info */
.customer-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.customer-name {
    font-weight: 600;
    color: #1f2937;
    white-space: nowrap;
}

.customer-pax {
    background: #dbeafe;
    color: #1d4ed8;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
    width: fit-content;
}

/* Package Info */
.package-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.package-name {
    font-weight: 500;
    color: #059669;
    white-space: nowrap;
}

.package-date {
    background: #f3f4f6;
    color: #4b5563;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    display: inline-block;
    width: fit-content;
}

.booking-time {
    font-size: 0.7rem;
    color: #9ca3af;
    white-space: nowrap;
}

/* Contact Info */
.contact-info {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.phone, .email {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    color: #4b5563;
    white-space: nowrap;
}

.contact-icon-small {
    width: 14px;
    height: 14px;
    color: #9ca3af;
}

/* Passenger Tags */
.passenger-tags {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.gender-summary {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
}

.gender-tag {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 2px;
    white-space: nowrap;
}

.gender-tag.male-tag {
    background: #dbeafe;
    color: #1d4ed8;
}

.gender-tag.female-tag {
    background: #fce7f3;
    color: #be185d;
}

.gender-tag.other-tag {
    background: #ede9fe;
    color: #6d28d9;
}

.passenger-names-preview {
    display: flex;
    align-items: center;
    gap: 4px;
    flex-wrap: wrap;
}

.passenger-mini-tag {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
}

.passenger-mini-tag.male-mini {
    background: #dbeafe;
    color: #1d4ed8;
}

.passenger-mini-tag.female-mini {
    background: #fce7f3;
    color: #be185d;
}

.passenger-mini-tag.other-mini {
    background: #ede9fe;
    color: #6d28d9;
}

.passenger-more {
    background: #f3f4f6;
    color: #6b7280;
    padding: 2px 6px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 500;
}

/* View & Assign Button */
.view-assign-btn {
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 8px 16px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
    box-shadow: 0 2px 4px rgba(5, 150, 105, 0.2);
}

.view-assign-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(5, 150, 105, 0.3);
}

.btn-icon-small {
    width: 16px;
    height: 16px;
}

/* Header Badge */
.header-badge {
    background: #059669;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    margin-left: 10px;
}

/* Empty State */
.empty-pending {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.empty-pending .empty-icon {
    width: 80px;
    height: 80px;
    color: #10b981;
    margin-bottom: 20px;
}

.empty-pending h4 {
    color: #374151;
    margin-bottom: 8px;
    font-size: 1.5rem;
}

.empty-pending p {
    color: #6b7280;
    font-size: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
    .super-pending-modal {
        width: 98% !important;
    }
    
    .pending-table th,
    .pending-table td {
        padding: 12px 8px;
        font-size: 0.8rem;
    }
    
    .view-assign-btn {
        padding: 6px 12px;
        font-size: 0.75rem;
    }
}
        .quick-stats {
            display: flex;
            gap: 30px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 1;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.85rem;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Super Pending Modal */
        .super-pending-modal {
            max-width: 800px !important;
            width: 95% !important;
        }

        .header-badge {
            background: #4158D0;
            color: white;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-left: 10px;
        }

        .package-group {
            margin-bottom: 25px;
        }

        .package-group-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
        }

        .package-group-header h4 {
            margin: 0;
            color: #4158D0;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .package-count {
            background: #f3f4f6;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            color: #4b5563;
        }

        .booking-meta {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .booking-date {
            background: #4158D0;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
        }

        .booking-contact-info {
            background: #f9fafb;
            border-radius: 8px;
            padding: 10px;
            margin: 10px 0;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .super-header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .super-header-right {
                width: 100%;
                justify-content: space-between;
            }
            
            .quick-stats {
                flex-wrap: wrap;
                gap: 15px;
            }
            
            .stat-item {
                flex: 1;
                min-width: 80px;
            }
            
            .pending-badge-large {
                padding: 8px 15px;
            }
            
            .pending-number {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .super-pending-header {
                padding: 20px;
            }
            
            .header-title {
                font-size: 1.4rem;
            }
            
            .header-icon-wrapper {
                width: 45px;
                height: 45px;
            }
            
            .view-all-btn {
                padding: 10px 20px;
            }
        }
        .main-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            margin-top: 20px;
            height: calc(100vh - 250px);
            overflow-y: auto;
            padding-right: 8px;
        }

        /* Custom scrollbar styling */
        .main-grid::-webkit-scrollbar {
            width: 8px;
        }

        .main-grid::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .main-grid::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .main-grid::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Buses section with independent scroll */
        .buses-section {
            height: fit-content;
        }

        .buses-grid {
            display: grid;
            gap: 20px;
            max-height: calc(100vh - 350px);
            overflow-y: auto;
            padding-right: 8px;
        }

        .buses-grid::-webkit-scrollbar {
            width: 6px;
        }

        .buses-grid::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .buses-grid::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #000000;
            margin: 0;
        }

        .selection-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .selected-count {
            background: #3b82f6;
            color: #fff;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        /* Bus Card Styles */
        .bus-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: box-shadow 0.2s;
        }

        .bus-card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .bus-header {
            margin-bottom: 16px;
            gap: 12px;
        }

        .bus-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .bus-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #000000;
            margin: 0;
            margin-bottom: 10px;
        }

        .bus-type {
            font-size: 0.875rem;
            color: #454e61;
            background: #f3f4f6;
            padding: 2px 8px;
            border-radius: 12px;
            align-self: flex-start;
        }

        .bus-content {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }

        /* Seat Layout */
        .seat-layout-container {
            background: #f8fafc;
            border-radius: 8px;
            padding: 16px;
            border: 1px solid #e2e8f0;
        }

        .seat-layout {
            display: inline-block;
            background: #fff;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .seat-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .passage {
            width: 40px;
            height: 4px;
            background: #cbd5e1;
            border-radius: 2px;
            margin: 0 8px;
        }

        .double-seats {
            display: flex;
            gap: 8px;
        }

        .back-row {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 2px solid #e2e8f0;
        }

        .seat {
            position: relative;
            display: inline-block;
            width: 36px;
            height: 36px;
            cursor: pointer;
            margin: 2px;
        }

        .seat input[type="checkbox"] {
            display: none;
        }

        .seat-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            border-radius: 6px;
            background: #339c00;
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            transition: all 0.2s ease;
            border: 2px solid transparent;
            box-shadow: 0px 0px 3px 2px #0000004d;
        }

        .seat-male .seat-number {
            background: #3b82f6 !important;
            border-color: #2563eb !important;
        }

        .seat-female .seat-number {
            background: #ec4899 !important;
            border-color: #db2777 !important;
        }

        .seat-other .seat-number {
            background: #8b5cf6 !important;
            border-color: #7c3aed !important;
        }

        .seat-booked-male .seat-number {
            background: #1d4ed8 !important;
            border-color: #1e40af !important;
            opacity: 0.7;
        }

        .seat-booked-female .seat-number {
            background: #be185d !important;
            border-color: #9d174d !important;
            opacity: 0.7;
        }

        .seat-booked-other .seat-number {
            background: #6d28d9 !important;
            border-color: #5b21b6 !important;
            opacity: 0.7;
        }

        .seat-booked .seat-number {
            background: #6b7280 !important;
            border-color: #4b5563 !important;
            opacity: 0.7;
        }

        .seat-selected .seat-number {
            background: #f59e0b !important;
            color: #fff;
            border-color: #d97706 !important;
            transform: scale(1.05);
            box-shadow: 0 0 0 2px #f59e0b, 0 2px 8px rgba(245, 158, 11, 0.4);
        }

        .seat:hover .seat-number:not(.seat-booked .seat-number):not(.seat-booked-male .seat-number):not(.seat-booked-female .seat-number):not(.seat-booked-other .seat-number) {
            transform: scale(1.1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .seat-booked,
        .seat-booked-male,
        .seat-booked-female,
        .seat-booked-other {
            cursor: not-allowed !important;
        }

        .seat-tooltip {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #1f2937;
            color: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
            z-index: 10;
            margin-bottom: 4px;
        }

        .seat-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 4px solid transparent;
            border-top-color: #1f2937;
        }

        .seat:hover .seat-tooltip {
            opacity: 1;
        }

        .gender-icon {
            position: absolute;
            bottom: -4px;
            right: -4px;
            font-size: 8px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            width: 12px;
            height: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            line-height: 1;
        }

        .gender-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #1f2937;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }

        .legend-color.male { background: #3b82f6; }
        .legend-color.female { background: #ec4899; }
        .legend-color.other { background: #8b5cf6; }
        .legend-color.selected { background: #f59e0b; }
        .legend-color.booked { background: #6b7280; }

        /* Assigned Bookings */
        .assigned-bookings {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            max-height: 400px;
            overflow-y: auto;
        }

        .assigned-bookings::-webkit-scrollbar {
            width: 6px;
        }

        .assigned-bookings::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .assigned-bookings::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .assigned-title {
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 12px;
            position: sticky;
            top: 0;
            background: #fff;
            padding: 8px 0;
            z-index: 5;
        }

        .bookings-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .assigned-booking {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .guest-name {
            font-weight: 600;
            color: #1f2937;
            font-size: 0.875rem;
        }

        .guest-pax {
            font-size: 0.75rem;
            color: #454e61;
            background: #e5e7eb;
            padding: 2px 6px;
            border-radius: 8px;
        }

        /* Gender breakdown */
        .gender-breakdown {
            display: flex;
            gap: 12px;
            margin: 8px 0 12px;
            flex-wrap: wrap;
        }

        .gender-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            background: white;
            border: 1px solid #e5e7eb;
        }

        .gender-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .gender-dot.male-dot {
            background: #3b82f6;
        }

        .gender-dot.female-dot {
            background: #ec4899;
        }

        .gender-dot.other-dot {
            background: #8b5cf6;
        }

        /* Seat Assignments */
        .seat-assignments {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin: 12px 0;
            max-height: 300px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .seat-assignments::-webkit-scrollbar {
            width: 4px;
        }

        .seat-assignments::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .seat-assignments::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .seat-assignment-card {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px 12px;
        }

        .seat-assignment-card.simple {
            background: #f9fafb;
        }

        .seat-info {
            min-width: 100px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .seat-number {
            font-weight: 600;
            color: #1f2937;
            font-size: 13px;
        }

        .gender-label {
            font-size: 11px;
            font-weight: 500;
        }

        .gender-label.male-text {
            color: #3b82f6;
        }

        .gender-label.female-text {
            color: #ec4899;
        }

        .gender-label.other-text {
            color: #8b5cf6;
        }

        .passenger-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .passenger-name {
            font-weight: 500;
            color: #1f2937;
            font-size: 13px;
        }

        .passenger-name.text-muted {
            color: #6b7280;
            font-style: italic;
        }

        .sharing-badge {
            background: #f3f4f6;
            color: #4b5563;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 500;
            display: inline-block;
            width: fit-content;
        }

        .contact-info {
            background: #dbeafe;
            color: #1d4ed8;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 500;
            display: inline-block;
            width: fit-content;
        }

        /* Bookings Section */
        .bookings-section {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            height: fit-content;
            max-height: calc(100vh - 350px);
            overflow-y: auto;
        }

        .bookings-section::-webkit-scrollbar {
            width: 6px;
        }

        .bookings-section::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .bookings-section::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .booking-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 12px;
        }

        .booking-card:last-child {
            margin-bottom: 0;
        }

        .booking-card:hover:not(.booking-inactive) {
            border-color: #3b82f6;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .booking-active {
            border-color: #339c00;
            background: #f0fdf4;
            box-shadow: 0 2px 4px rgba(51, 156, 0, 0.1);
        }

        .booking-inactive {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }

        .booking-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .booking-info .guest-name {
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937;
        }

        .booking-info .guest-pax {
            background: #e5e7eb;
            color: #4b5563;
            padding: 4px 8px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
        }

        .booking-id {
            font-size: 10px;
            color: #6b7280;
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .booking-status {
            margin-top: 12px;
            text-align: right;
        }

        .status-ready {
            color: #10b981;
            font-size: 11px;
            font-weight: 600;
            background: #d1fae5;
            padding: 4px 12px;
            border-radius: 16px;
            display: inline-block;
        }

        .status-pending {
            color: #6b7280;
            font-size: 11px;
            background: #f3f4f6;
            padding: 4px 12px;
            border-radius: 16px;
            display: inline-block;
        }

        /* Buttons */
        .btn-primary {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #3b82f6;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-danger {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #ef0000;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-remove-seats {
            display: flex;
            align-items: center;
            gap: 4px;
            background: transparent;
            color: #ef0000;
            border: 1px solid #ef0000;
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
            width: fit-content;
        }

        .btn-remove-seats:hover {
            background: #ef0000;
            color: #fff;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 0.7rem;
        }

        .btn-icon {
            width: 16px;
            height: 16px;
        }

        .btn-download {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #10b981;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-download:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .bus-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            justify-content: flex-end;
        }

        /* Empty States */
        .empty-state,
        .empty-bookings {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }

        .empty-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 16px;
            color: #d1d5db;
        }

        .empty-state h3,
        .empty-bookings h4 {
            color: #374151;
            margin-bottom: 8px;
        }

        .empty-state p,
        .empty-bookings p {
            font-size: 0.875rem;
        }

        .no-assignments {
            text-align: center;
            padding: 30px 20px;
            color: #6b7280;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px dashed #d1d5db;
        }

        .no-assignments .empty-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 12px;
            color: #9ca3af;
        }

        .no-assignments h4 {
            color: #374151;
            margin-bottom: 4px;
            font-size: 1rem;
        }

        .no-assignments p {
            font-size: 0.875rem;
            color: #6b7280;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            padding: 0;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            color: #1f2937;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6b7280;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .modal-close:hover {
            background: #f3f4f6;
            color: #374151;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .gender-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        
        .gender-option {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .gender-option:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
        }
        
        .gender-option.selected {
            border-color: #339c00;
            background: #f0fdf4;
        }
        
        .gender-option .gender-icon {
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        .gender-option .gender-icon.male {
            color: #3b82f6;
        }
        
        .gender-option .gender-icon.female {
            color: #ec4899;
        }
        
        .gender-option .gender-icon.other {
            color: #8b5cf6;
        }
        
        .modal-footer {
            padding: 16px 20px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }

        /* Passenger Modal Styles - Fixed */
        .passenger-modal {
            max-width: 900px !important;
            width: 95% !important;
            max-height: 90vh !important;
            display: flex;
            flex-direction: column;
        }

        .passenger-modal .modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            max-height: calc(90vh - 130px);
        }

        .passenger-modal-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .passengers-list-section,
        .seats-preview-section {
            background: #f8fafc;
            border-radius: 8px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .passengers-list-section h4,
        .seats-preview-section h4 {
            margin: 0 0 15px 0;
            color: #1f2937;
            font-size: 1rem;
            font-weight: 600;
            position: sticky;
            top: 0;
            background: #f8fafc;
            padding: 8px 0;
            z-index: 5;
        }

        .passengers-grid {
            display: grid;
            gap: 12px;
            max-height: 400px;
            overflow-y: auto;
            padding-right: 8px;
        }

        .passengers-grid::-webkit-scrollbar {
            width: 6px;
        }

        .passengers-grid::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .passengers-grid::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .passenger-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            transition: all 0.2s;
        }

        .passenger-card.assigned {
            border-color: #10b981;
            background: #f0fdf4;
        }

        .passenger-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .passenger-sr {
            font-size: 11px;
            color: #6b7280;
            background: #f3f4f6;
            padding: 2px 8px;
            border-radius: 12px;
        }

        .passenger-gender-badge {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
        }

        .passenger-gender-badge.male-badge {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .passenger-gender-badge.female-badge {
            background: #fce7f3;
            color: #be185d;
        }

        .passenger-gender-badge.other-badge {
            background: #ede9fe;
            color: #6d28d9;
        }

        .sharing-tag {
            background: #e5e7eb;
            color: #4b5563;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
        }

        .contact-tag {
            background: #dbeafe;
            color: #1d4ed8;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
        }

        /* Fixed Dropdown Styles */
        .seat-select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 13px;
            background: white;
            margin-top: 10px;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%234b5563' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 40px;
            transition: all 0.2s ease;
            position: relative;
            z-index: 10;
        }

        .seat-select:hover {
            border-color: #3b82f6;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .seat-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .seat-select:disabled {
            background-color: #f3f4f6;
            cursor: not-allowed;
            opacity: 0.6;
        }

        /* Option styling for dropdown */
        .seat-select option {
            padding: 12px;
            font-size: 13px;
            background: white;
            color: #1f2937;
        }

        .seat-select option:checked {
            background: #3b82f6 linear-gradient(0deg, #3b82f6 0%, #3b82f6 100%);
            color: white;
        }

        .seat-select option:hover {
            background: #f3f4f6;
        }

        /* Seats Preview */
        .seats-grid {
            display: grid;
            gap: 10px;
            max-height: 400px;
            overflow-y: auto;
            padding-right: 8px;
        }

        .seats-grid::-webkit-scrollbar {
            width: 6px;
        }

        .seats-grid::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .seats-grid::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .seat-preview-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px;
        }

        .seat-preview-card.assigned {
            border-color: #10b981;
            background: #f0fdf4;
        }

        .bus-label {
            font-size: 11px;
            color: #6b7280;
            margin: 2px 0 6px;
        }

        .assigned-to {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: #059669;
            flex-wrap: wrap;
        }

        .assigned-badge {
            background: #10b981;
            color: white;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }

        .assigned-name {
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 120px;
        }

        .unassigned {
            font-size: 11px;
            color: #9ca3af;
            font-style: italic;
        }

        /* Assignment Summary */
        .assignment-summary {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            position: sticky;
            bottom: 0;
            background: #f8fafc;
        }

        .summary-bar {
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .summary-progress {
            height: 100%;
            background: #10b981;
            transition: width 0.3s ease;
        }

        .summary-text {
            font-size: 12px;
            color: #4b5563;
            text-align: center;
        }

        /* Responsive Design */
        @media (min-width: 768px) {
            .main-grid {
                grid-template-columns: 1.5fr 1fr;
                height: calc(100vh - 200px);
            }

            .bus-content {
                grid-template-columns: 1fr 300px;
            }

            .seat {
                width: 40px;
                height: 40px;
            }

            .seat-number {
                font-size: 12px;
            }
            
            .gender-icon {
                font-size: 10px;
                width: 14px;
                height: 14px;
                bottom: -5px;
                right: -5px;
            }
        }

        @media (min-width: 1024px) {
            .main-grid {
                grid-template-columns: 2fr 1fr;
            }

            .bus-content {
                grid-template-columns: 1fr 350px;
            }
        }

        @media (max-width: 767px) {
            .main-grid {
                height: calc(100vh - 150px);
            }

            .section-header {
                flex-direction: column;
                align-items: stretch;
            }

            .bus-header {
                flex-direction: column;
                align-items: stretch;
            }

            .bus-info {
                align-items: center;
                text-align: center;
            }

            .bus-type {
                align-self: center;
            }

            .seat-layout {
                padding: 12px;
            }

            .seat {
                width: 32px;
                height: 32px;
            }

            .seat-number {
                font-size: 10px;
            }

            .passage {
                width: 20px;
                margin: 0 4px;
            }

            .double-seats {
                gap: 4px;
            }

            .back-row {
                gap: 4px;
            }
            
            .gender-icon {
                font-size: 7px;
                width: 10px;
                height: 10px;
                bottom: -3px;
                right: -3px;
            }
            
            .gender-options {
                grid-template-columns: 1fr;
            }

            .bus-actions {
                flex-direction: column;
                width: 100%;
            }

            .btn-download,
            .btn-danger {
                width: 100%;
                justify-content: center;
            }

            .passenger-modal-grid {
                grid-template-columns: 1fr;
            }

            .passenger-modal {
                width: 98% !important;
                max-height: 95vh !important;
            }

            .passenger-modal .modal-body {
                max-height: calc(95vh - 120px);
            }

            .passengers-grid,
            .seats-grid {
                max-height: 300px;
            }
        }

        @media (max-width: 480px) {
            .bus-card {
                padding: 16px;
            }

            .seat-layout-container {
                padding: 12px;
            }

            .seat-layout {
                padding: 8px;
            }

            .seat {
                width: 28px;
                height: 28px;
                margin: 1px;
            }

            .seat-number {
                font-size: 9px;
            }

            .passage {
                width: 16px;
            }

            .assigned-bookings {
                padding: 12px;
            }
            
            .gender-icon {
                font-size: 6px;
                width: 9px;
                height: 9px;
                bottom: -2px;
                right: -2px;
            }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .animate-spin {
            animation: spin 1s linear infinite;
        }
        /* Add to your existing CSS */

/* Ensure assigned bookings content is always visible in download */
.bus-card .assigned-bookings {
    /* Keep your existing styles for normal viewing */
    max-height: 400px;
    overflow-y: auto;
}

/* But when cloning for download, we'll override via JavaScript */
.for-download .assigned-bookings,
.for-download .seat-assignments,
.for-download [class*="scroll"] {
    max-height: none !important;
    overflow: visible !important;
}

/* Improve print/download styles */
@media print {
    .bus-card .assigned-bookings,
    .bus-card .seat-assignments {
        max-height: none !important;
        overflow: visible !important;
    }
    
    .bus-actions, 
    .btn-remove-seats, 
    .btn-download, 
    .btn-danger {
        display: none !important;
    }
}
    </style>
       <script>
        function loadHtml2Canvas() {
            return new Promise((resolve, reject) => {
                if (window.html2canvas) {
                    resolve();
                    return;
                }
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }

        const buttonOriginalTexts = new Map();

        async function downloadBusImage(button) {
            let originalText = '';
            try {
                originalText = button.innerHTML;
                buttonOriginalTexts.set(button, originalText);
                button.innerHTML = `<svg class="btn-icon animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v4m0 12v4m8-10h-4M6 12H2m15.364-7.364l-2.828 2.828M7.464 17.536l-2.828 2.828m0-12.728l2.828 2.828m9.168 9.168l2.828 2.828"></path></svg> Generating...`;
                button.disabled = true;

                await loadHtml2Canvas();
                const busCard = button.closest('.bus-card');
                if (!busCard) throw new Error('Bus card not found');

                const busTitle = busCard.querySelector('.bus-title').textContent.trim();
                const fileName = `Bus_Seating_${busTitle.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.jpg`;

                const clone = busCard.cloneNode(true);
                const elementsToRemove = clone.querySelectorAll('.bus-actions, .btn-remove-seats, .btn-download, .btn-danger, .btn-remove-seats');
                elementsToRemove.forEach(element => element.remove());

                const assignedBookings = clone.querySelector('.assigned-bookings');
                if (assignedBookings) {
                    assignedBookings.style.maxHeight = 'none';
                    assignedBookings.style.overflow = 'visible';
                }

                const seatAssignments = clone.querySelectorAll('.seat-assignments');
                seatAssignments.forEach(el => {
                    el.style.maxHeight = 'none';
                    el.style.overflow = 'visible';
                });

                const scrollableElements = clone.querySelectorAll('[style*="overflow"]');
                scrollableElements.forEach(el => {
                    if (el.classList.contains('assigned-bookings') || el.classList.contains('seat-assignments') || el.classList.contains('bookings-list')) {
                        el.style.maxHeight = 'none';
                        el.style.overflow = 'visible';
                    }
                });

                const bookingItems = clone.querySelectorAll('.assigned-booking');
                bookingItems.forEach(item => {
                    item.style.display = 'block';
                    item.style.opacity = '1';
                });

                const tempContainer = document.createElement('div');
                tempContainer.style.position = 'fixed';
                tempContainer.style.left = '-9999px';
                tempContainer.style.top = '0';
                tempContainer.style.zIndex = '-9999';
                tempContainer.style.width = busCard.offsetWidth + 'px';
                tempContainer.style.backgroundColor = '#ffffff';
                tempContainer.appendChild(clone);
                document.body.appendChild(tempContainer);

                await new Promise(resolve => setTimeout(resolve, 100));

                const canvas = await html2canvas(clone, {
                    backgroundColor: '#ffffff',
                    scale: 3,
                    useCORS: true,
                    allowTaint: false,
                    logging: false,
                    windowWidth: clone.scrollWidth,
                    windowHeight: clone.scrollHeight,
                    onclone: function(clonedDoc, element) {
                        const clonedBusCard = element;
                        clonedBusCard.style.width = busCard.offsetWidth + 'px';
                        const textElements = clonedBusCard.querySelectorAll('*');
                        textElements.forEach(el => {
                            if (el.style) {
                                el.style.overflow = 'visible';
                                el.style.maxHeight = 'none';
                            }
                        });
                        const guestNames = clonedBusCard.querySelectorAll('.guest-name');
                        guestNames.forEach(name => {
                            name.style.fontSize = '14px';
                            name.style.fontWeight = 'bold';
                        });
                        const seatInfo = clonedBusCard.querySelectorAll('.seat-info');
                        seatInfo.forEach(info => {
                            info.style.marginBottom = '4px';
                        });
                    }
                });

                document.body.removeChild(tempContainer);
                const image = canvas.toDataURL('image/jpeg', 1.0);
                downloadImage(image, fileName);

            } catch (error) {
                console.error('Error generating image:', error);
                alert('Error generating download. Please try again. Error: ' + error.message);
            } finally {
                const storedText = buttonOriginalTexts.get(button) || originalText;
                button.innerHTML = storedText;
                button.disabled = false;
            }
        }

        function showNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'notification-toast';
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 2000);
        }

        function downloadImage(dataUrl, filename) {
            const link = document.createElement('a');
            link.href = dataUrl;
            link.download = filename;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        const style = document.createElement('style');
        style.textContent = `
            .bus-card.for-download .assigned-bookings,
            .bus-card.for-download .seat-assignments {
                max-height: none !important;
                overflow: visible !important;
            }
            .bus-card.for-download [style*="overflow"] {
                overflow: visible !important;
                max-height: none !important;
            }
        `;
        document.head.appendChild(style);

        document.addEventListener('livewire:initialized', () => {
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('seat-select')) e.stopPropagation();
            });
            document.addEventListener('focusin', function(e) {
                if (e.target.tagName === 'SELECT') {
                    e.target.style.position = 'relative';
                    e.target.style.zIndex = '10000';
                }
            });
            document.addEventListener('focusout', function(e) {
                if (e.target.tagName === 'SELECT') e.target.style.zIndex = '10';
            });
            Livewire.on('scroll-to-selected', () => {
                const selectedSeats = document.querySelectorAll('.seat-selected');
                if (selectedSeats.length > 0) {
                    selectedSeats[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        });
    </script>
</x-filament-panels::page>