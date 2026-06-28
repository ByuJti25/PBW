<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use App\Models\User;
use App\Models\Auction;

class OutbidNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $auction;
    public $newPrice;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Auction $auction, float $newPrice)
    {
        $this->user = $user;
        $this->auction = $auction;
        $this->newPrice = $newPrice;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'auction_id' => $this->auction->id,
            'auction_title' => $this->auction->title,
            'new_price' => $this->newPrice,
            'message' => 'Anda telah tergeser (outbid) pada lelang "' . $this->auction->title . '" dengan penawaran baru senilai Rp ' . number_format($this->newPrice, 0, ',', '.') . '.',
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
            new PrivateChannel('App.Models.User.' . $this->user->id),
        ];
    }
}
