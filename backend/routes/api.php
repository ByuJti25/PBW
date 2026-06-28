<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SellerAuctionController;
use App\Http\Controllers\Api\BidderAuctionController;
use App\Http\Controllers\Api\BidController;

// Public Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public Bidder routes (Viewing active auctions)
Route::get('/auctions', [BidderAuctionController::class, 'index']);
Route::get('/auctions/{auction}', [BidderAuctionController::class, 'show']);

// Protected routes (Requires Sanctum auth)
Route::middleware('auth:sanctum')->group(function () {
    // Current user details & Logout
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Seller Endpoints
    Route::prefix('seller')->group(function () {
        Route::get('/auctions', [SellerAuctionController::class, 'index']);
        Route::post('/auctions', [SellerAuctionController::class, 'store']);
        Route::get('/auctions/{auction}', [SellerAuctionController::class, 'show']);
        Route::put('/auctions/{auction}', [SellerAuctionController::class, 'update']);
        Route::post('/auctions/{auction}', [SellerAuctionController::class, 'update']); // For image uploads on update
        Route::delete('/auctions/{auction}', [SellerAuctionController::class, 'destroy']);
    });

    // Bidder Endpoints (Placing bid)
    Route::post('/auctions/{auction}/bid', [BidController::class, 'store']);
});
