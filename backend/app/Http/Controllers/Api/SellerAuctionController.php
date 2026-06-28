<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Auction;
use App\Http\Requests\Auction\StoreAuctionRequest;
use App\Http\Requests\Auction\UpdateAuctionRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class SellerAuctionController extends Controller
{
    /**
     * Display a listing of the seller's auctions.
     */
    public function index(Request $request)
    {
        $auctions = $request->user()->auctions()
            ->with(['highestBid'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'auctions' => $auctions,
        ]);
    }

    /**
     * Store a newly created auction.
     */
    public function store(StoreAuctionRequest $request)
    {
        $validated = $request->validated();
        $validated['seller_id'] = $request->user()->id;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('auctions', 'public');
            $validated['image_path'] = $path;
        }

        // Determine initial status based on starts_at
        $startsAt = Carbon::parse($validated['starts_at']);
        if ($startsAt->lte(now())) {
            $validated['status'] = 'active';
        } else {
            $validated['status'] = 'scheduled';
        }

        $auction = Auction::create($validated);

        return response()->json([
            'message' => 'Auction created successfully',
            'auction' => $auction->load('highestBid'),
        ], 201);
    }

    /**
     * Display the specified auction.
     */
    public function show(Auction $auction)
    {
        // Ensure the seller owns the auction
        if ($auction->seller_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized access to this auction.',
            ], 403);
        }

        return response()->json([
            'auction' => $auction->load(['highestBid', 'bids.bidder']),
        ]);
    }

    /**
     * Update the specified auction.
     */
    public function update(UpdateAuctionRequest $request, Auction $auction)
    {
        // The UpdateAuctionRequest has already validated that:
        // 1. The user owns the auction.
        // 2. The auction is in 'scheduled' status.
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($auction->image_path) {
                Storage::disk('public')->delete($auction->image_path);
            }
            $path = $request->file('image')->store('auctions', 'public');
            $validated['image_path'] = $path;
        }

        // Update initial status based on starts_at if it's changing
        if (isset($validated['starts_at'])) {
            $startsAt = Carbon::parse($validated['starts_at']);
            if ($startsAt->lte(now())) {
                $validated['status'] = 'active';
            } else {
                $validated['status'] = 'scheduled';
            }
        }

        $auction->update($validated);

        return response()->json([
            'message' => 'Auction updated successfully',
            'auction' => $auction->fresh()->load('highestBid'),
        ]);
    }

    /**
     * Remove the specified auction from storage.
     */
    public function destroy(Auction $auction)
    {
        // Enforce: Penjual hanya boleh menghapus lelang selama lelang belum dimulai (status 'scheduled').
        if ($auction->seller_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ($auction->status !== 'scheduled') {
            return response()->json([
                'message' => 'You cannot delete an auction that has already started.',
            ], 422);
        }

        // Delete image if exists
        if ($auction->image_path) {
            Storage::disk('public')->delete($auction->image_path);
        }

        $auction->delete();

        return response()->json([
            'message' => 'Auction deleted successfully',
        ]);
    }
}

