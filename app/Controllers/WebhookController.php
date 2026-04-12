<?php
namespace App\Controllers;

use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use App\Models\Transaction;

class WebhookController {
    public function handle() {
        $payload = file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'];

        try {
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (\UnexpectedValueException $e) {
            http_response_code(400);
            exit();
        } catch (SignatureVerificationException $e) {
            http_response_code(400);
            exit();
        }

        if ($event->type == 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;
            $auctionId = $paymentIntent->metadata->auction_id;
            $transactionModel = new Transaction();
            $transaction = $transactionModel->findByAuctionId($auctionId);
            if ($transaction) {
                $transactionModel->updatePaymentStatus($transaction['id'], 'paid', 'stripe');
            }
        }
    }