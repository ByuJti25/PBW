<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Auction;

class BidderAuctionController extends Controller
{
    /**
     * Display a listing of active and scheduled auctions.
     */
    public function index(Request $request)
    {
        $auctions = Auction::with(['highestBid'])
            ->whereIn('status', ['active', 'scheduled'])
            ->orderByRaw("FIELD(status, 'active', 'scheduled')") // Active first, then scheduled
            ->orderBy('ends_at', 'asc')
            ->get();

        return response()->json([
            'auctions' => $auctions,
        ]);
    }

    /**
     * Display the specified auction with relationships.
     */
    public function show(Auction $auction)
    {
        // Load relationships: seller, bids (ordered newest first) and their bidders
        $auction->load([
            'seller',
            'highestBid',
            'bids' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'bids.bidder'
        ]);

        return response()->json([
            'auction' => $auction,
        ]);
    }
}
