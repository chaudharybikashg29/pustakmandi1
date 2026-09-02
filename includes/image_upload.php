<?php
/* ==========================================
   PUSTAKMANDI — includes/image_upload.php
   Shared helper for handling product image uploads.
   Returns ['success' => bool, 'path' => string|null, 'error' => string|null]
========================================== */

function handleProductImageUpload(array $file): array
{
    // No file selected — not necessarily an error, caller decides if that's ok
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'path' => null, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'path' => null, 'error' => 'Upload failed. Please try again.'];
    }

    $maxBytes = 2 * 1024 * 1024; // 2MB
    if ($file['size'] > $maxBytes) {
        return ['success' => false, 'path' => null, 'error' => 'Image must be under 2MB.'];
    }

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    // Check the real file content, not just the filename extension —
    // a renamed .php file with a .jpg name would still show as image/jpeg
    // in $_FILES, but finfo checks the actual bytes.
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!isset($allowedTypes[$mimeType])) {
        return ['success' => false, 'path' => null, 'error' => 'Only JPG, PNG, WEBP, or GIF images are allowed.'];
    }

    $extension = $allowedTypes[$mimeType];
    $filename  = 'prod_' . uniqid() . '.' . $extension;

    $uploadDir = __DIR__ . '/../uploads/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return ['success' => false, 'path' => null, 'error' => 'Could not save the uploaded image.'];
    }

    // Stored relative to any page one folder below the project root
    // (customer/, seller/) — matches how ../homepage/css/style.css etc. are referenced.
    return ['success' => true, 'path' => '../uploads/products/' . $filename, 'error' => null];
}

function deleteProductImage(?string $imagePath): void
{
    // Only ever delete files we actually uploaded ourselves —
    // never touch external URLs like the sample products' Unsplash links.
    if ($imagePath && str_starts_with($imagePath, '../uploads/products/')) {
        $fullPath = __DIR__ . '/../' . substr($imagePath, 3);
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}