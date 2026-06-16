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
        $expectedKey = env('INTERNAL_API_KEY', '');
        
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

    public function resolveContact(Request $request, $sender_id)
    {
        $user = $request->user_from_token;
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Auto-resolve pending complaints for this sender
        $updated = Message::where('user_id', $user->id)
            ->where('sender_id', $sender_id)
            ->where('status', 'pending')
            ->update([
                'status' => 'resolved',
                'resolved_by' => $user->id,
                'updated_at' => now(), // resolved_at if we had one, but updated_at works
            ]);

        return response()->json(['status' => 'success', 'resolved_count' => $updated]);
    }
}
