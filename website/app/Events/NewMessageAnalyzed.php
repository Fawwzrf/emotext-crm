<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageAnalyzed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        // Broadcast on a private channel unique to the company (user_id)
        return [
            new PrivateChannel('company.' . $this->message->user_id),
        ];
    }
    
    public function broadcastWith()
    {
        return [
            'id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender_name ?? $this->message->sender_id,
            'message' => $this->message->message,
            'sentiment' => $this->message->sentiment,
            'intent' => $this->message->intent,
            'created_at' => $this->message->created_at ? $this->message->created_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
        ];
    }
}
