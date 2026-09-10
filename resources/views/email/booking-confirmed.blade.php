<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - tripogramclub</title>
</head>

<body style="margin: 0; padding: 0; background-color: #f5f7fa; font-family: Arial, sans-serif;">
    <!-- Main Container -->
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #f5f7fa;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <!-- Email Content Container -->
                <table width="600" border="0" cellpadding="0" cellspacing="0"
                    style="background-color: #ffffff; border-radius: 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">

                    <!-- Header Section -->
                    <tr>
                        <td
                            style="background: #50B101; border-radius: 10px 10px 0 0; padding: 30px 20px; text-align: center; color: white;">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding-bottom: 10px;">
                                        <img src="https://tripogramclub.com/assets/images/logo.png" alt="tripogramclub"
                                            width="100" style="border-radius: 50px; display: block; margin: 0 auto;">
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        style="font-size: 24px; font-weight: bold; padding: 10px 0; font-family: Arial, sans-serif;">
                                        Booking Confirmed!<br> Booking ID: {{$booking->booking_id}}
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        style="font-size: 16px; color: rgba(255,255,255,0.9); font-family: Arial, sans-serif;">
                                        Your {{$other->name}} adventure is officially scheduled
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 30px;">

                            <!-- Greeting -->
                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding-bottom: 20px;">
                                        <h2
                                            style="font-size: 20px; color: #2c3e50; margin: 0; font-family: Arial, sans-serif;">
                                            Hello {{$booking->full_name}},</h2>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-bottom: 20px;">
                                        <span
                                            style="background: #e7f7ef; color: #27ae60; padding: 8px 16px; border-radius: 50px; font-size: 14px; font-weight: bold; display: inline-block;">
                                            Confirmed • Payment ID: {{$booking->payment_id}}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-bottom: 25px;">
                                        <p
                                            style="color: #333333; line-height: 1.6; margin: 0; font-family: Arial, sans-serif;">
                                            Thank you for choosing EnliveTrips. Your booking details are outlined below.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Itinerary Section -->
                            <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                style="background-color: #f8f9fa; border-left: 4px solid #1a73e8; margin-bottom: 25px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <h3
                                            style="font-size: 18px; font-weight: bold; color: #2c3e50; margin: 0 0 15px 0; font-family: Arial, sans-serif;">
                                            Your Travel Itinerary
                                        </h3>
                                        <p
                                            style="color: #333333; line-height: 1.6; margin: 0 0 15px 0; font-family: Arial, sans-serif;">
                                            Your detailed day-by-day itinerary is now available for download. This
                                            includes all activities, timings, hotel information, and important contact
                                            details.
                                        </p>

                                        <!-- Itinerary Highlights -->
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="25%" align="center" style="padding: 10px;">
                                                    <div
                                                        style="background: #e7f0ff; width: 36px; height: 36px; border-radius: 18px; margin: 0 auto 5px; color: #1a73e8; font-size: 16px;">
                                                        🏨</div>
                                                    <div
                                                        style="font-size: 14px; color: #2c3e50; font-family: Arial, sans-serif;">
                                                        Hotel Details</div>
                                                </td>
                                                <td width="25%" align="center" style="padding: 10px;">
                                                    <div
                                                        style="background: #e7f0ff; width: 36px; height: 36px; border-radius: 18px; margin: 0 auto 5px; color: #1a73e8; font-size: 16px;">
                                                        ⏱️</div>
                                                    <div
                                                        style="font-size: 14px; color: #2c3e50; font-family: Arial, sans-serif;">
                                                        Daily Schedule</div>
                                                </td>
                                                <td width="25%" align="center" style="padding: 10px;">
                                                    <div
                                                        style="background: #e7f0ff; width: 36px; height: 36px; border-radius: 18px; margin: 0 auto 5px; color: #1a73e8; font-size: 16px;">
                                                        🍽️</div>
                                                    <div
                                                        style="font-size: 14px; color: #2c3e50; font-family: Arial, sans-serif;">
                                                        Meal Information</div>
                                                </td>
                                                <td width="25%" align="center" style="padding: 10px;">
                                                    <div
                                                        style="background: #e7f0ff; width: 36px; height: 36px; border-radius: 18px; margin: 0 auto 5px; color: #1a73e8; font-size: 16px;">
                                                        📞</div>
                                                    <div
                                                        style="font-size: 14px; color: #2c3e50; font-family: Arial, sans-serif;">
                                                        Emergency Contacts</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Download Button -->
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{$itinerary_pdf}}" download="true"
                                            style="background: #27ae60; color: white; padding: 14px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block; font-family: Arial, sans-serif; font-size: 16px;">
                                            Download Itinerary
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Trip Overview -->
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td>
                                        <h3
                                            style="font-size: 18px; font-weight: bold; color: #1a73e8; margin: 0 0 15px 0; padding-bottom: 8px; border-bottom: 1px solid #eaeaea; font-family: Arial, sans-serif;">
                                            Trip Overview
                                        </h3>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="50%" style="padding: 8px 0;">
                                                    <div
                                                        style="font-size: 14px; color: #7f8c8d; font-family: Arial, sans-serif;">
                                                        Package</div>
                                                    <div
                                                        style="font-size: 16px; font-weight: 500; color: #2c3e50; font-family: Arial, sans-serif;">
                                                        {{$booking->package_title}}
                                                    </div>
                                                </td>
                                                <td width="50%" style="padding: 8px 0;">
                                                    <div
                                                        style="font-size: 14px; color: #7f8c8d; font-family: Arial, sans-serif;">
                                                        Duration</div>
                                                    <div
                                                        style="font-size: 16px; font-weight: 500; color: #2c3e50; font-family: Arial, sans-serif;">
                                                        {{str_replace(['N', 'D', '-'], [' Nights', ' Days', ' - '], $booking->duration)}}
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="50%" style="padding: 8px 0;">
                                                    <div
                                                        style="font-size: 14px; color: #7f8c8d; font-family: Arial, sans-serif;">
                                                        Start Date</div>
                                                    <div
                                                        style="font-size: 16px; font-weight: 500; color: #2c3e50; font-family: Arial, sans-serif;">
                                                        {{date("d M Y", strtotime($booking->start_date))}}
                                                    </div>
                                                </td>
                                                <td width="50%" style="padding: 8px 0;">
                                                    <div
                                                        style="font-size: 14px; color: #7f8c8d; font-family: Arial, sans-serif;">
                                                        End Date</div>
                                                    <div
                                                        style="font-size: 16px; font-weight: 500; color: #2c3e50; font-family: Arial, sans-serif;">
                                                        {{date("d M Y", strtotime($booking->end_date))}}
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="50%" style="padding: 8px 0;">
                                                    <div
                                                        style="font-size: 14px; color: #7f8c8d; font-family: Arial, sans-serif;">
                                                        Pickup & Drop</div>
                                                    <div
                                                        style="font-size: 16px; font-weight: 500; color: #2c3e50; font-family: Arial, sans-serif;">
                                                        {{$booking->pickup}} to {{$booking->drop}}
                                                    </div>
                                                </td>
                                                <td width="50%" style="padding: 8px 0;"></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Fill Travelers Details Button -->
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{$extra_url}}"
                                            style="background: #1a73e8; color: white; padding: 14px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block; font-family: Arial, sans-serif; font-size: 16px;">
                                            Fill Travelers Details
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Cost Breakdown -->
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td>
                                        <h3
                                            style="font-size: 18px; font-weight: bold; color: #1a73e8; margin: 0 0 15px 0; padding-bottom: 8px; border-bottom: 1px solid #eaeaea; font-family: Arial, sans-serif;">
                                            Cost Breakdown
                                        </h3>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                            style="border-collapse: collapse;">
                                            <tr style="background: #f8f9fa;">
                                                <th
                                                    style="padding: 12px 15px; text-align: left; font-weight: bold; color: #2c3e50; font-size: 14px; border-bottom: 1px solid #eaeaea;">
                                                    Activity</th>
                                                <th
                                                    style="padding: 12px 15px; text-align: left; font-weight: bold; color: #2c3e50; font-size: 14px; border-bottom: 1px solid #eaeaea;">
                                                    Quantity</th>
                                                <th
                                                    style="padding: 12px 15px; text-align: left; font-weight: bold; color: #2c3e50; font-size: 14px; border-bottom: 1px solid #eaeaea;">
                                                    Total</th>
                                            </tr>
                                            @php
                                                $hasGST = false;
                                                $totalGST = 0;
                                                $subtotal = 0;
                                            @endphp

                                            @foreach($booking->active_cost as $cost)
                                                @php
                                                    $gstPercent = (float) ($cost['gst_percent'] ?? 0);
                                                    if ($gstPercent > 0) {
                                                        $hasGST = true;
                                                    }
                                                    $totalWithGST = (float) ($cost['total_with_discount_and_gst'] ?? 0);
                                                    $totalWithDiscount = (float) ($cost['total_with_discount'] ?? 0);
                                                    $gstAmount = $totalWithGST - $totalWithDiscount;
                                                    $totalGST += $gstAmount;
                                                    $subtotal += $totalWithDiscount;
                                                @endphp
                                                <tr>
                                                    <td style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0;">
                                                        {{$cost['activity']}}
                                                        @if($gstPercent > 0)
                                                            (Includes {{$gstPercent}}% GST)
                                                        @endif
                                                    </td>
                                                    <td style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0;">
                                                        {{$cost['quantity']}}
                                                    </td>
                                                    <td style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0;">
                                                        ₹{{number_format($totalWithGST, 2)}}</td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Payment Summary -->
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td>
                                        <h3
                                            style="font-size: 18px; font-weight: bold; color: #1a73e8; margin: 0 0 15px 0; padding-bottom: 8px; border-bottom: 1px solid #eaeaea; font-family: Arial, sans-serif;">
                                            Payment Summary
                                        </h3>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                            style="background: #f8f9fa; border-radius: 8px; padding: 20px;">
                                            <tr>
                                                <td style="padding: 8px 0;">
                                                    <table width="100%">
                                                        <tr>
                                                            <td><strong>Subtotal:</strong></td>
                                                            <td align="right">
                                                                <strong>₹{{number_format($subtotal, 2)}}</strong>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>

                                            @if($hasGST && $totalGST > 0)
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <table width="100%">
                                                            <tr>
                                                                <td><strong>Total GST:</strong></td>
                                                                <td align="right">
                                                                    <strong>₹{{number_format($totalGST, 2)}}</strong>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            @endif

                                            <tr>
                                                <td style="padding: 8px 0; border-top: 1px dashed #ddd;">
                                                    <table width="100%">
                                                        <tr>
                                                            <td><strong style="font-size: 16px;">Final Amount:</strong>
                                                            </td>
                                                            <td align="right"><strong
                                                                    style="font-size: 16px;">₹{{number_format($booking->final_amount, 2)}}</strong>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td style="padding: 8px 0;">
                                                    <table width="100%">
                                                        <tr>
                                                            <td>Paid Amount ({{ucfirst($booking->payment_mode)}}):</td>
                                                            <td align="right">
                                                                ₹{{number_format($booking->paid_amount, 2)}}</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td style="padding: 12px 0; border-top: 1px dashed #ddd;">
                                                    <table width="100%">
                                                        <tr>
                                                            <td style="color: #e74c3c; font-weight: bold;">Due Amount:
                                                            </td>
                                                            <td align="right"
                                                                style="color: #e74c3c; font-weight: bold;">
                                                                ₹{{number_format($booking->due_amount, 2)}}</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- What's Next Section -->
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td>
                                        <h3
                                            style="font-size: 18px; font-weight: bold; color: #1a73e8; margin: 0 0 15px 0; padding-bottom: 8px; border-bottom: 1px solid #eaeaea; font-family: Arial, sans-serif;">
                                            What's Next?
                                        </h3>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <!-- Timeline Item 1 -->
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                            style="margin-bottom: 20px;">
                                            <tr>
                                                <td width="30" valign="top" style="padding-right: 10px;">
                                                    <div
                                                        style="width: 16px; height: 16px; border-radius: 8px; background: #1a73e8; border: 3px solid white; box-shadow: 0 0 0 2px #1a73e8;">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div
                                                        style="font-weight: bold; color: #2c3e50; font-family: Arial, sans-serif; margin-bottom: 5px;">
                                                        Booking Confirmed</div>
                                                    <div
                                                        style="font-size: 14px; color: #7f8c8d; font-family: Arial, sans-serif;">
                                                        Your booking is confirmed and secured</div>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Timeline Item 2 -->
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                            style="margin-bottom: 20px;">
                                            <tr>
                                                <td width="30" valign="top" style="padding-right: 10px;">
                                                    <div
                                                        style="width: 16px; height: 16px; border-radius: 8px; background: #1a73e8; border: 3px solid white; box-shadow: 0 0 0 2px #1a73e8;">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div
                                                        style="font-weight: bold; color: #2c3e50; font-family: Arial, sans-serif; margin-bottom: 5px;">
                                                        Download Itinerary</div>
                                                    <div
                                                        style="font-size: 14px; color: #7f8c8d; font-family: Arial, sans-serif;">
                                                        Get your detailed travel plan now</div>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Timeline Item 3 -->
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                            style="margin-bottom: 20px;">
                                            <tr>
                                                <td width="30" valign="top" style="padding-right: 10px;">
                                                    <div
                                                        style="width: 16px; height: 16px; border-radius: 8px; background: #1a73e8; border: 3px solid white; box-shadow: 0 0 0 2px #1a73e8;">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div
                                                        style="font-weight: bold; color: #2c3e50; font-family: Arial, sans-serif; margin-bottom: 5px;">
                                                        Pre-Trip Contact</div>
                                                    <div
                                                        style="font-size: 14px; color: #7f8c8d; font-family: Arial, sans-serif;">
                                                        Our team will contact you 3 days before to confirm pickup
                                                        details</div>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Timeline Item 4 -->
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="30" valign="top" style="padding-right: 10px;">
                                                    <div
                                                        style="width: 16px; height: 16px; border-radius: 8px; background: #1a73e8; border: 3px solid white; box-shadow: 0 0 0 2px #1a73e8;">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div
                                                        style="font-weight: bold; color: #2c3e50; font-family: Arial, sans-serif; margin-bottom: 5px;">
                                                        Enjoy Your Trip!</div>
                                                    <div
                                                        style="font-size: 14px; color: #7f8c8d; font-family: Arial, sans-serif;">
                                                        Relax and get ready for an amazing experience</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Closing -->
                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding-bottom: 15px;">
                                        <p
                                            style="color: #333333; line-height: 1.6; margin: 0; font-family: Arial, sans-serif;">
                                            If you have any questions about your upcoming trip, feel free to reply to
                                            this email or contact us at +91-8287828267.
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-bottom: 20px;">
                                        <p
                                            style="color: #333333; line-height: 1.6; margin: 0; font-family: Arial, sans-serif;">
                                            We wish you safe and happy travels!
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <p
                                            style="color: #333333; line-height: 1.6; margin: 0; font-family: Arial, sans-serif;">
                                            Warm regards,<br><strong>The EnliveTrips Team</strong>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td
                            style="background: #2c3e50; color: #ecf0f1; padding: 30px 20px; text-align: center; border-radius: 0 0 10px 10px;">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding-bottom: 15px;">
                                        <a href="#"
                                            style="color: #bdc3c7; text-decoration: none; font-size: 14px; margin: 0 10px; font-family: Arial, sans-serif;">Our
                                            Website</a>
                                        <a href="#"
                                            style="color: #bdc3c7; text-decoration: none; font-size: 14px; margin: 0 10px; font-family: Arial, sans-serif;">Manage
                                            Booking</a>
                                        <a href="#"
                                            style="color: #bdc3c7; text-decoration: none; font-size: 14px; margin: 0 10px; font-family: Arial, sans-serif;">Contact
                                            Us</a>
                                        <a href="#"
                                            style="color: #bdc3c7; text-decoration: none; font-size: 14px; margin: 0 10px; font-family: Arial, sans-serif;">Privacy
                                            Policy</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <p
                                            style="font-size: 13px; color: #95a5a6; margin: 0; font-family: Arial, sans-serif;">
                                            &copy; 2025 EnliveTrips. All rights reserved.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>