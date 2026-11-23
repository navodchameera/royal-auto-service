<?php
defined('APP_PATH') or exit;

class PhotoUploader
{
    private const MAX_BYTES = 5 * 1024 * 1024;   // 5 MB per photo
    private const TYPES = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];

    public static function dir(): string
    {
        return dirname(APP_PATH) . '/uploads/booking_photos/';
    }

    // Checks one $_FILES entry without saving it
    public static function inspect(?array $f): array
    {
        $none = ['empty' => false, 'error' => null, 'ext' => null];

        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['empty' => true, 'error' => null, 'ext' => null];
        }
        if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE || $f['size'] > self::MAX_BYTES) {
            return ['error' => 'is larger than 5 MB'] + $none;
        }
        if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            return ['error' => 'could not be uploaded'] + $none;
        }
        $info = @getimagesize($f['tmp_name']);   // proves it is a real image
        if (!$info || !isset(self::TYPES[$info[2]])) {
            return ['error' => 'must be a JPG, PNG or WebP image'] + $none;
        }
        return ['ext' => self::TYPES[$info[2]]] + $none;
    }

    // Saves a file that passed inspect() and returns the new random file name
    public static function store(array $f, string $ext): string
    {
        $dir = self::dir();
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new RuntimeException('Cannot create upload folder');
        }
        $name = bin2hex(random_bytes(16)) . '.' . $ext;   // never use the visitor's file name
        if (!move_uploaded_file($f['tmp_name'], $dir . $name)) {
            throw new RuntimeException('Cannot save photo');
        }
        return $name;
    }
}
