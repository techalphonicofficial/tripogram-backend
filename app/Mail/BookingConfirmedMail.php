<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Address;

class BookingConfirmedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $booking;
    public $other;
    public $itinerary_pdf;

    /**
     * Create a new message instance.
     */
    public function __construct($booking, $other, $itinerary_pdf)
    {
        $this->booking = $booking;
        $this->other = $other;
        $this->itinerary_pdf = $itinerary_pdf;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                'booking@enlivetrips.com',
                'tripogramclub '
            ),
            subject: 'tripogramclub Booking Confirmation - #' . $this->booking->booking_token,
        );
    }

    /**
     * Get the message content definition.
     */
    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'email.booking-confirmed',
    //         with: [
    //             'booking' => $this->booking,
    //             'other' => $this->other,
    //             'itinerary_pdf' => $this->itinerary_pdf,
    //         ],
    //     );
    // }
    public function content(): Content
    {
        $url = 'https://tripogramclub.com/booking-detail?id=' . $this->booking->booking_token;


        return new Content(
            view: 'email.booking-confirmed',
            with: [
                'booking' => $this->booking,
                'other' => $this->other,
                'itinerary_pdf' => $this->itinerary_pdf,
                'extra_url' => $url,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
