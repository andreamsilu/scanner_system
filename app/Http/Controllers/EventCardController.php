<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use Illuminate\Http\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;

class EventCardController extends Controller
{
    public function download(Attendee $attendee)
    {
        $qrCode = QrCode::size(200)
            ->format('svg')
            ->generate($attendee->qr_token);

        $pdf = PDF::loadView('pdf.event-card', [
            'attendee' => $attendee,
            'qrCode' => $qrCode,
        ]);

        return $pdf->download("event-card-{$attendee->id}.pdf");
    }
} 