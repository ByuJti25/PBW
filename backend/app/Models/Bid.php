<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bid extends Model
{
    protected $fillable = [
        'auction_id',
        'user_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Get the auction for which this bid was placed.
     */
    public function auction()
    {
        return $this->belongsTo(Auction::class, 'auction_id');
    }

    /**
     * Get the bidder who placed this bid.
     */
    public function bidder()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias for bidder relationship.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
