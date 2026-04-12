<?php
namespace App\Controllers;

use App\Models\Transaction;
use App\Services\AuthService;
use Stripe\Stripe;  // Fixed: was "Striper\Stripe"
use Stripe\PaymentIntent;

class PaymentController {
    private $transactionModel;

    public function __construct() {
        $this->transactionModel = new Transaction();
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
    }

    public function createPaymentIntent($auctionId) {
        header('Content-Type: application/json');
        $user = AuthService::verifyToken();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        $transaction = $this->transactionModel->findByAuctionId($auctionId);
        if (!$transaction || $transaction['buyer_id'] !== $user->user_id) {
            http_response_code(403);
            echo json_encode(['error' => 'Not Authorized to pay for this auction']);
            return;
        }
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $transaction['amount'] * 100,
                'currency' => 'usd',  // Changed from 'php' to 'usd'; adjust as needed
                'metadata' => ['auction_id' => $auctionId],
            ]);
            echo json_encode(['client_secret' => $paymentIntent->client_secret]);  // Fixed: was 'clientSecret'
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function confirmPayment($auctionId) {
        header('Content-Type: application/json');
        $user = AuthService::verifyToken();  // Added auth check
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        $transaction = $this->transactionModel->findByAuctionId($auctionId);  // Added validation
        if (!$transaction || $transaction['buyer_id'] !== $user->user_id) {
            http_response_code(403);
            echo json_encode(['error' => 'Not authorized']);
            return;
        }
        $this->transactionModel->updatePaymentStatus($transaction['id'], 'paid', 'stripe');  // Use transaction id, not auctionId
        echo json_encode(['message' => 'Payment confirmed']);
    }
}