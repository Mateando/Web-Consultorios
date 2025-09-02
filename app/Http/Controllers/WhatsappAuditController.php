<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhatsappAudit;
use Illuminate\Support\Facades\Auth;

class WhatsappAuditController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'appointment_id' => 'nullable|exists:appointments,id',
            'recipient_phone' => 'required|string',
            'message' => 'nullable|string',
            'meta' => 'nullable|array',
        ]);

        $audit = WhatsappAudit::create([
            'appointment_id' => $data['appointment_id'] ?? null,
            'user_id' => Auth::id(),
            'recipient_phone' => $data['recipient_phone'],
            'message' => $data['message'] ?? null,
            'meta' => $data['meta'] ?? null,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return response()->json(['ok' => true, 'id' => $audit->id], 201);
    }
}
