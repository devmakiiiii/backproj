<?php
namespace App\Controllers;

use App\Models\Auction;
use App\Models\User;

class AuctionController {
    private $auctionModel;
    private $userModel;

    public function __construct() {
        $this->auctionModel = new Auction();
        $this->userModel = new User();
    }

    public function index() {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit();
        }
        $filters = $_GET;
        $auctions = $this->auctionModel->getActiveAuctions($filters);
        $categories = $this->auctionModel->getCategories();
        require __DIR__ . '/../../resources/views/auction/index.php';
    }

    public function show($id) {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit();
        }
        $auction = $this->auctionModel->findById($id);
        if (!$auction) {
            http_response_code(404);
            echo "Auction not found.";
            exit();
        }
        $bids = $this->auctionModel->getBids($id);
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['bid'])) {
                if ($this->auctionModel->placeBid($id, $_SESSION['user_id'], $_POST['bid_amount'])) {
                    header("Location: /auction/$id");
                    exit();
                } else {
                    $error = 'Invalid bid.';
                }
            }
        }
        require __DIR__ . '/../../resources/views/auction/show.php';
    }

    public function create() {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit();
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            // Basic validation
            if (empty($data['title']) || empty($data['description']) || empty($data['starting_price']) || empty($data['end_time'])) {
                $error = 'All fields required.';
            } else {
                $this->auctionModel->create($data, $_SESSION['user_id']);
                header('Location: /auctions');
                exit();
            }
        }
        $categories = $this->auctionModel->getCategories();
        require __DIR__ . '/../../resources/views/auction/create.php';
    }
}