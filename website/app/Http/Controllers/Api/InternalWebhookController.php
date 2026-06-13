<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Events\NewMessageAnalyzed;

class InternalWebhookController extends Controller
{
    public function broadcastMessage(Request $request)
    {
        // Security check
        $apiKey = $request->header('X-Internal-Api-Key');
        $expectedKey = env('INTERNAL_API_KEY', 'emotext_secret_internal_key_2026');
        
        if (!$apiKey || $apiKey !== $expectedKey) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'message_id' => 'required|integer'
        ]);

        $message = Message::find($request->message_id);

        if ($message) {
            // Dispatch event to broadcast via Reverb
            broadcast(new NewMessageAnalyzed($message));
            return response()->json(['status' => 'Broadcasted']);
        }

        return response()->json(['error' => 'Message not found'], 404);
    }
}
