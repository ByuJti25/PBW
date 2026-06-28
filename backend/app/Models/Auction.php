<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auction extends Model
{
    protected $fillable = [
        'seller_id',
        'title',
        'description',
        'image_path',
        'start_price',
        'bid_increment',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'start_price' => 'decimal:2',
        'bid_increment' => 'decimal:2',
    ];

    protected $appends = ['current_price', 'image_url'];

    /**
     * Accessor for full image URL.
     */
    public function getImageUrlAttribute()
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    /**
     * Get the seller of this auction.
     */
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Get all bids for this auction.
     */
    public function bids()
    {
        return $this->hasMany(Bid::class, 'auction_id');
    }

    /**
     * Get the highest bid for this auction.
     */
    public function highestBid()
    {
        return $this->hasOne(Bid::class, 'auction_id')->ofMany('amount', 'max');
    }

    /**
     * Accessor for current price.
     */
    public function getCurrentPriceAttribute()
    {
        // If there's a highest bid, return its amount, otherwise start_price
        return $this->highestBid ? (float) $this->highestBid->amount : (float) $this->start_price;
    }
}
