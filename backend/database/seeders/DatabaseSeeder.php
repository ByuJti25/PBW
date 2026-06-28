<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Test Users
        $seller = User::create([
            'name' => 'Budi Seller',
            'email' => 'seller@example.com',
            'password' => bcrypt('password'),
        ]);

        $bidder = User::create([
            'name' => 'Andi Bidder',
            'email' => 'bidder@example.com',
            'password' => bcrypt('password'),
        ]);

        // 2. Create Sample Auctions
        // Active auction (long running)
        \App\Models\Auction::create([
            'seller_id' => $seller->id,
            'title' => 'iPhone 15 Pro Max Titanium',
            'description' => 'iPhone 15 Pro Max warna Blue Titanium, kapasitas 256GB. Kondisi mulus 99% lengkap dengan box original dan kabel charger.',
            'start_price' => 15000000,
            'bid_increment' => 200000,
            'starts_at' => now()->subHours(1),
            'ends_at' => now()->addHours(3),
            'status' => 'active',
        ]);

        // Scheduled auction (starts tomorrow)
        \App\Models\Auction::create([
            'seller_id' => $seller->id,
            'title' => 'MacBook Air M3 2024',
            'description' => 'MacBook Air dengan chip M3 terbaru, RAM 16GB, SSD 512GB. Pembelian resmi ibox, garansi masih aktif.',
            'start_price' => 18000000,
            'bid_increment' => 500000,
            'starts_at' => now()->addDays(1),
            'ends_at' => now()->addDays(2),
            'status' => 'scheduled',
        ]);

        // Active auction ending in 45 seconds (ideal for testing automatic closing and anti-sniping)
        \App\Models\Auction::create([
            'seller_id' => $seller->id,
            'title' => 'Custom Mechanical Keyboard GMMK Pro',
            'description' => 'Custom keyboard GMMK Pro, switch lubed Gateron Yellow, keycap retro PBT. Kondisi prima dan suara thacky.',
            'start_price' => 2500000,
            'bid_increment' => 50000,
            'starts_at' => now()->subMinutes(5),
            'ends_at' => now()->addSeconds(45),
            'status' => 'active',
        ]);
    }
}
