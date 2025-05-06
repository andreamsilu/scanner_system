<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use App\Models\ScanLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QrScannerController extends Controller
{
    public function index()
    {
        return view('scanner.index');
    }

    public function validateToken(Request $request): JsonResponse
    {
        $token = $request->input('token');
        
        $attendee = Attendee::where('qr_token', $token)->first();

        if (!$attendee) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code. Please try again.'
            ], 422);
        }

        if ($attendee->scanned) {
            return response()->json([
                'success' => false,
                'message' => 'This QR code has already been scanned.'
            ], 422);
        }

        // Mark as scanned and create log
        $attendee->update(['scanned' => true]);
        
        ScanLog::create([
            'attendee_id' => $attendee->id,
            'scanned_by' => auth()->user()->name,
            'device_info' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'attendee' => $attendee
        ]);
    }
} 