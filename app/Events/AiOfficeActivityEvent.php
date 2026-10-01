<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiOfficeActivityEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $character; // 'dewi', 'singgih', 'andera'
    public string $action;    // 'chat_incoming', 'working', 'idle', 'report'
    public ?string $message;
    public array $extra;

    /**
     * Create a new event instance.
     */
    public function __construct(string $character, string $action, ?string $message = null, array $extra = [])
    {
        $this->character = $character;
        $this->action = $action;
        $this->message = $message;
        $this->extra = $extra;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('ai-office'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'activity';
    }
}
