<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Auction;
use App\Http\Requests\Bid\PlaceBidRequest;
use App\Events\BidPlaced;
use App\Events\OutbidNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class BidController extends Controller
{
    /**
     * Place a new bid on an active auction.
     */
    public function store(PlaceBidRequest $request, Auction $auction)
    {
        $validated = $request->validated();
        $bidderId = $request->user()->id;
        $amount = (float) $validated['amount'];

        try {
            $result = DB::transaction(function () use ($auction, $bidderId, $amount) {
                // 1. Pessimistic Locking: Lock the auction row.
                $lockedAuction = Auction::where('id', $auction->id)->lockForUpdate()->firstOrFail();

                // Validation Rule 2: Bidder cannot bid on their own auction.
                if ($lockedAuction->seller_id === $bidderId) {
                    return response()->json([
                        'message' => 'Anda tidak boleh menawar pada lelang milik sendiri.'
                    ], 422);
                }

                // Validation Rule 3 & 4: Auction must be active and not ended.
                $now = Carbon::now();
                if ($lockedAuction->status !== 'active' || $now->lt($lockedAuction->starts_at) || $now->gt($lockedAuction->ends_at)) {
                    return response()->json([
                        'message' => 'Penawaran ditolak. Lelang sedang tidak aktif atau sudah berakhir.'
                    ], 422);
                }

                // Validation Rule 1: Bid must be >= highest price + bid_increment (or start_price if no bids)
                $currentHighestBid = $lockedAuction->bids()->lockForUpdate()->orderBy('amount', 'desc')->first();

                $currentPrice = $currentHighestBid ? (float) $currentHighestBid->amount : (float) $lockedAuction->start_price;
                $minBid = $currentHighestBid ? ($currentPrice + (float) $lockedAuction->bid_increment) : (float) $lockedAuction->start_price;

                if ($amount < $minBid) {
                    return response()->json([
                        'message' => 'Nilai tawaran harus minimal Rp ' . number_format($minBid, 0, ',', '.') . '.'
                    ], 422);
                }

                // Anti-sniping Logic: Extend ends_at if a bid is placed in the last 30 seconds.
                $secondsRemaining = $lockedAuction->ends_at->diffInSeconds($now, false);
                // Since now <= ends_at, secondsRemaining will be >= 0.
                if ($secondsRemaining >= 0 && $secondsRemaining <= 30) {
                    // Extend ends_at by 30 seconds from current time
                    $lockedAuction->ends_at = $now->copy()->addSeconds(30);
                    $lockedAuction->save();
                }

                // Insert the new bid
                $newBid = $lockedAuction->bids()->create([
                    'user_id' => $bidderId,
                    'amount' => $amount,
                ]);

                $previousHighestBidder = $currentHighestBid ? $currentHighestBid->bidder : null;

                return [
                    'success' => true,
                    'lockedAuction' => $lockedAuction,
                    'newBid' => $newBid,
                    'previousHighestBidder' => $previousHighestBidder,
                ];
            });

            if ($result instanceof \Illuminate\Http\JsonResponse) {
                return $result;
            }

            $lockedAuction = $result['lockedAuction'];
            $newBid = $result['newBid'];
            $previousHighestBidder = $result['previousHighestBidder'];

            // Reload fresh data with relationships for events
            $lockedAuction->load(['highestBid', 'bids.bidder']);

            // 1. Broadcast to all viewers on public channel (BidPlaced)
            // Use broadcast() helper which triggers Reverb
            broadcast(new BidPlaced($lockedAuction))->toOthers();

            // 2. Notify the outbid user on their private channel
            if ($previousHighestBidder && $previousHighestBidder->id !== $bidderId) {
                broadcast(new OutbidNotification($previousHighestBidder, $lockedAuction, $amount));
            }

            return response()->json([
                'message' => 'Penawaran berhasil ditempatkan.',
                'bid' => $newBid->load('bidder'),
                'auction' => $lockedAuction,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memproses penawaran: ' . $e->getMessage()
            ], 500);
        }
    }
}

