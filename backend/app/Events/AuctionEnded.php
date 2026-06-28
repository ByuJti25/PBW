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

class AuctionEnded implements ShouldBroadcast
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
        $this->auction->load(['highestBid.bidder']);
        $winner = $this->auction->highestBid ? $this->auction->highestBid->bidder : null;

        return [
            'auction_id' => $this->auction->id,
            'status' => $this->auction->status,
            'winner' => $winner ? [
                'id' => $winner->id,
                'name' => $winner->name,
                'email' => $winner->email,
            ] : null,
            'winning_bid_amount' => $this->auction->highestBid ? (float) $this->auction->highestBid->amount : null,
            'message' => $winner 
                ? 'Lelang telah berakhir! Pemenangnya adalah ' . $winner->name . ' dengan penawaran Rp ' . number_format($this->auction->highestBid->amount, 0, ',', '.') . '.'
                : 'Lelang telah berakhir tanpa adanya penawaran.',
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
