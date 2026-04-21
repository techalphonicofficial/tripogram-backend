<!DOCTYPE html>
<html>
<head>
    <title>Complete Your Booking</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; max-width: 500px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; border-radius: 10px;">
        <h1>Booking Confirmation</h1>
    </div>
    
    <div style="padding: 30px; text-align: center;">
        <h2>Hello {{ $booking->full_name }},</h2>
        
        <p style="font-size: 18px;"><strong>Please fill your details</strong></p>
        
        <p style="background: #f0f0f0; padding: 15px; border-radius: 5px;">
            <strong>Token:</strong> {{ $booking->booking_token ?? 'N/A' }}
        </p>
        
        <a href="{{ $bookingLink }}" 
           style="background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0;">
            Fill Details
        </a>
    </div>
</body>
</html>