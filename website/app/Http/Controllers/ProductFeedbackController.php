<?php

namespace App\Http\Controllers;

use App\Models\ProductFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductFeedbackController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'message' => 'required|string|max:2000',
        ]);

        ProductFeedback::create([
            'name'    => $request->name,
            'email'   => $request->email,
            'message' => $request->message,
            'user_id' => Auth::id() ?: null,
            'status'  => 'unread'
        ]);

        return back()->with('success', 'Terima kasih atas pesan dan masukan Anda! Kami akan segera menindaklanjutinya.');
    }
}
