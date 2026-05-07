<?php
namespace App\Console;

use App\Models\Auction;

class EndAuctionsCommand {
    public static function run() {
        $auction = new Auction();
        $auction->endAuctions();
        echo "Auctions processed.";
    }
}