<?php
defined('APP_PATH') or exit;

// Manager: what customers said about the service
class ManageReviewsController extends Controller
{
    public function index(): void
    {
        $user   = $this->requireRole('manager');
        $rating = (int)($_GET['rating'] ?? 0);
        if ($rating < 1 || $rating > 5) { $rating = 0; }

        $m = new ManageMore();
        $this->view('manager/reviews', [
            'user' => $user, 'rating' => $rating, 'stats' => $m->reviewStats(), 'reviews' => $m->reviews($rating),
        ]);
    }
}
