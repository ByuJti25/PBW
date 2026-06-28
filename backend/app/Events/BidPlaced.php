<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use App\Models\Auction;

class BidPlaced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $auction;

    /**
     * Create a new event instance.
     */
    public function __construct(Auction $auction)
    {
        $this->auction = $auction;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        // Reload relations to make sure they are fresh
        $this->auction->load(['highestBid', 'bids.bidder']);

        return [
            'auction_id' => $this->auction->id,
            'current_price' => (float) $this->auction->current_price,
            'bid_count' => $this->auction->bids()->count(),
            'ends_at' => $this->auction->ends_at->toIso8601String(),
            'bids' => $this->auction->bids()->with('bidder')->orderBy('created_at', 'desc')->get(),
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('auction.' . $this->auction->id),
        ];
    }
}
