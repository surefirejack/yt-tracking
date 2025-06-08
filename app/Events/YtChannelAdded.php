<?php

namespace App\Events;

use App\Models\YtChannel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class YtChannelAdded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public YtChannel $ytChannel;

    /**
     * Create a new event instance.
     */
    public function __construct(YtChannel $ytChannel)
    {
        $this->ytChannel = $ytChannel;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
