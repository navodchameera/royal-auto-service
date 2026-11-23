<?php
defined('APP_PATH') or exit;

class Photo
{
    public function add(int $bookingId, string $file, string $area): void
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO photos (booking_id, file_path, photo_area) VALUES (:b, :f, :a)'
        );
        $stmt->execute(['b' => $bookingId, 'f' => $file, 'a' => $area]);
    }
}
