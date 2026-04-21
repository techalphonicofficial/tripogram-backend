<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Stay Layout</title>

    <style>
        body{
            font-family: DejaVu Sans, sans-serif;
            font-size:12px;
        }

        table{
            width:100%;
            border-collapse: collapse;
            margin-bottom:20px;
        }

        th,td{
            border:1px solid #ddd;
            padding:6px;
            text-align:left;
        }

        th{
            background:#f3f4f6;
        }

        .booking-title{
            background:#e5e7eb;
            padding:6px;
            font-weight:bold;
        }

        .guest-box{
            background:#eff6ff;
            padding:4px;
            border-radius:4px;
        }
    </style>
</head>

<body>

@foreach($data as $bookingId => $rooms)

<div class="booking-title">
    Booking #{{ $bookingId }}
</div>

<table>

<thead>
<tr>
<th>Room</th>
<th>Type</th>
<th>Room Type</th>
<th>Bed 1</th>
<th>Bed 2</th>
<th>Bed 3</th>
<th>Bed 4</th>
<th>Bed 5</th>
<th>Bed 6</th>
<th>Total Occupied</th>
<!--<th>Available</th>-->
</tr>
</thead>

<tbody>

@php
    $grandTotal = 0;
@endphp

@foreach($rooms as $roomId => $beds)

@php
    $room = $beds->first();
    $bedMap = $beds->keyBy('bed_number');
    $totalBeds = $room->room->no_of_beds ?? 6;

    // Captain exclude karke count karenge
    $occupiedBeds = $beds->where('is_captain', 0)->count();
    $availableBeds = $totalBeds - $occupiedBeds;

    $grandTotal += $occupiedBeds;
@endphp

<tr>

<td>{{ $roomId }}</td>

<td>{{ ucfirst($room->room->room_type ?? 'other') }}</td>

<td>{{ ucfirst($room->room_type ?? 'other') }}</td>

@for($i=1;$i<=6;$i++)

<td>

@if(isset($bedMap[$i]))

<div class="guest-box">

{{ $bedMap[$i]->guest_name }}

@if($bedMap[$i]->is_captain)
👑 (Captain)
@endif

<br>

<small>
{{ ucfirst($bedMap[$i]->guest_gender) }}
</small>

</div>

@else

@if($i <= $totalBeds)
Empty
@endif

@endif

</td>

@endfor

<td>{{ $occupiedBeds }}</td>

<!--<td>{{ $availableBeds }}</td>-->

</tr>

@endforeach

{{-- Grand Total Row --}}
<tr style="font-weight:bold; background:#f3f4f6;">
    <td colspan="9" style="text-align:right;">Grand Total Occupied Beds (excluding captains):</td>
    <td>{{ $grandTotal }}</td>
    <td></td>
</tr>

</tbody>

</table>

@endforeach

</body>
</html>