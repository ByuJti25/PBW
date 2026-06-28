<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:close-expired-auctions')]
#[Description('Command description')]
class CloseExpiredAuctions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = \Illuminate\Support\Carbon::now();

        // 1. Activate scheduled auctions that have reached starts_at
        $scheduledAuctions = \App\Models\Auction::where('status', 'scheduled')
            ->where('starts_at', '<=', $now)
            ->get();

        foreach ($scheduledAuctions as $auction) {
            $auction->update(['status' => 'active']);
            $this->info("Activated auction: ID {$auction->id} - {$auction->title}");
        }

        // 2. Close active auctions that have reached ends_at
        $expiredAuctions = \App\Models\Auction::where('status', 'active')
            ->where('ends_at', '<=', $now)
            ->get();

        foreach ($expiredAuctions as $auction) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($auction) {
                $lockedAuction = \App\Models\Auction::where('id', $auction->id)
                    ->lockForUpdate()
                    ->first();

                if ($lockedAuction && $lockedAuction->status === 'active') {
                    $lockedAuction->status = 'ended';
                    $lockedAuction->save();

                    // Broadcast the ended event with winner announcement
                    broadcast(new \App\Events\AuctionEnded($lockedAuction));
                }
            });

            $this->info("Closed expired auction: ID {$auction->id} - {$auction->title}");
        }

        return Command::SUCCESS;
    }
}
