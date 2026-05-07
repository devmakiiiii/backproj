<?php
namespace App\Controllers;

use App\Models\Auction;
use App\Models\User;
use App\Services\AuthService;

class AuctionController {
    private $auctionModel;
    private $userModel;

    public function __construct() {
        $this->auctionModel = new Auction();
        $this->userModel = new User();
    }

    public function index() {
        header('Content-Type: application/json');
        $filters = $_GET;
        $auctions = $this->auctionModel->getActiveAuctions($filters);
        $categories = $this->auctionModel->getCategories();
        echo json_encode(['auctions' => $auctions, 'categories' => $categories]);
    }

    public function show($id) {
        header('Content-Type: application/json');
        $auction = $this->auctionModel->findById($id);
        if (!$auction) {
            http_response_code(404);
            echo json_encode(['error' => 'Auction not found']);
            return;
        }
        $bids = $this->auctionModel->getBids($id);
        echo json_encode(['auction' => $auction, 'bids' => $bids]);
    }

    public function create() {
        header('Content-Type: application/json');
        $user = AuthService::verifyToken();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $input = $_POST;
        if (!$input || empty($input['title']) || empty($input['description']) || empty($input['starting_price']) || empty($input['end_time'])) {
            http_response_code(400);
            echo json_encode(['error' => 'All fields required']);
            return;
        }
        
        $imageUrls = [];
        
        if (!empty($_FILES['images']['name'][0])) {
            $uploadDir = __DIR__ . '/../../public/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            foreach ($_FILES['images']['error'] as $key => $error) {
                if ($error === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                    $filename = uniqid('img_') . '.' . $ext;
                    $destination = $uploadDir . $filename;
                    
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $destination)) {
                        $imageUrls[] = '/uploads/' . $filename;
                    }
                }
            }
        }
        
        try {
            $this->auctionModel->create($input, $user->user_id, $imageUrls);
            http_response_code(201);
            echo json_encode(['message' => 'Auction created successfully']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create auction: ' . $e->getMessage()]);
        }
    }

    public function placeBid($id) {
        header('Content-Type: application/json');
        $user = AuthService::verifyToken();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['bid_amount'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Bid amount required']);
            return;
        }
        if ($this->auctionModel->placeBid($id, $user->user_id, $input['bid_amount'])) {
            echo json_encode(['message' => 'Bid placed successfully']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid bid']);
        }
    }

    public function closeAuction($id) {
        if ($this->auctionModel->closeAuction($id)) {
            return true;
        }
        return false;
    }
}