<?php
/**
 * Simple QR Code Library Wrapper
 * This is a lightweight wrapper for QR code generation
 */

class QRcode {
    const QR_ECLEVEL_L = 1;
    const QR_ECLEVEL_M = 2;
    const QR_ECLEVEL_Q = 3;
    const QR_ECLEVEL_H = 4;

    /**
     * Generate QR code image
     */
    public static function png($text, $outfile = false, $level = self::QR_ECLEVEL_M, $size = 3, $margin = 4) {
        // For demo purposes, we'll create a simple placeholder
        // In production, you would use a proper QR library like endroid/qr-code

        $width = 264; // Base size for QR code
        $height = 264;

        // Create image
        $image = imagecreate($width, $height);

        // Colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        // Fill with white background
        imagefill($image, 0, 0, $white);

        // Create a simple pattern (this is just for demo - real QR would be much more complex)
        self::drawSimpleQRPattern($image, $black, $white, $text);

        if ($outfile === false) {
            header('Content-Type: image/png');
            imagepng($image);
        } else {
            imagepng($image, $outfile);
        }

        imagedestroy($image);
        return true;
    }

    /**
     * Create a simple QR-like pattern (for demo purposes)
     */
    private static function drawSimpleQRPattern($image, $black, $white, $text) {
        $size = 264;
        $modules = 21; // Standard QR code size
        $moduleSize = $size / $modules;

        // Create a hash-based pattern from the text
        $hash = md5($text);
        $pattern = [];

        for ($i = 0; $i < strlen($hash); $i++) {
            $pattern[] = hexdec($hash[$i]) % 2;
        }

        // Draw finder patterns (corners)
        self::drawFinderPattern($image, $black, 0, 0, $moduleSize);
        self::drawFinderPattern($image, $black, 14 * $moduleSize, 0, $moduleSize);
        self::drawFinderPattern($image, $black, 0, 14 * $moduleSize, $moduleSize);

        // Draw data pattern
        $patternIndex = 0;
        for ($row = 0; $row < $modules; $row++) {
            for ($col = 0; $col < $modules; $col++) {
                // Skip finder pattern areas
                if (self::isFinderArea($row, $col)) continue;

                $x = $col * $moduleSize;
                $y = $row * $moduleSize;

                if ($pattern[$patternIndex % count($pattern)]) {
                    imagefilledrectangle($image, $x, $y, $x + $moduleSize - 1, $y + $moduleSize - 1, $black);
                }
                $patternIndex++;
            }
        }
    }

    /**
     * Draw finder pattern (corner squares)
     */
    private static function drawFinderPattern($image, $black, $x, $y, $moduleSize) {
        // 7x7 finder pattern
        for ($row = 0; $row < 7; $row++) {
            for ($col = 0; $col < 7; $col++) {
                $shouldFill = false;

                // Outer border
                if ($row == 0 || $row == 6 || $col == 0 || $col == 6) {
                    $shouldFill = true;
                }
                // Inner square
                if ($row >= 2 && $row <= 4 && $col >= 2 && $col <= 4) {
                    $shouldFill = true;
                }

                if ($shouldFill) {
                    $px = $x + ($col * $moduleSize);
                    $py = $y + ($row * $moduleSize);
                    imagefilledrectangle($image, $px, $py, $px + $moduleSize - 1, $py + $moduleSize - 1, $black);
                }
            }
        }
    }

    /**
     * Check if position is in finder pattern area
     */
    private static function isFinderArea($row, $col) {
        // Top-left finder
        if ($row < 9 && $col < 9) return true;
        // Top-right finder
        if ($row < 9 && $col > 11) return true;
        // Bottom-left finder
        if ($row > 11 && $col < 9) return true;

        return false;
    }
}

// Define constants for compatibility
if (!defined('QR_ECLEVEL_L')) define('QR_ECLEVEL_L', QRcode::QR_ECLEVEL_L);
if (!defined('QR_ECLEVEL_M')) define('QR_ECLEVEL_M', QRcode::QR_ECLEVEL_M);
if (!defined('QR_ECLEVEL_Q')) define('QR_ECLEVEL_Q', QRcode::QR_ECLEVEL_Q);
if (!defined('QR_ECLEVEL_H')) define('QR_ECLEVEL_H', QRcode::QR_ECLEVEL_H);
?>
