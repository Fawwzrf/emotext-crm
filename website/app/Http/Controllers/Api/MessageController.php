<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi data sesuai Kontrak API 
        $validator = Validator::make($request->all(), [
            'sender_id'  => 'required|string',
            'message'    => 'required|string',
            'sentiment'  => 'required|in:positive,neutral,negative',
            'intent'     => 'required|in:inquiry,complaint,order,other',
            'confidence' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 2. Simpan ke database PostgreSQL [cite: 17, 20]
        $message = Message::create($request->all());

        // 3. Berikan feedback sukses [cite: 18, 39]
        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil dianalisis dan disimpan',
            'data' => $message
        ], 201);
    }
}