<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WatermarkService
{
    /**
     * Get the path to the TTF font, downloading it if not present.
     */
    protected function getFontPath(): ?string
    {
        $fontDir = storage_path('app/fonts');
        $fontPath = $fontDir . '/Roboto-Regular.ttf';

        if (file_exists($fontPath)) {
            return $fontPath;
        }

        try {
            if (!is_dir($fontDir)) {
                mkdir($fontDir, 0755, true);
            }

            // Download font from googlefonts/roboto github raw repository
            $url = 'https://raw.githubusercontent.com/googlefonts/roboto/main/src/hinted/Roboto-Regular.ttf';
            
            Log::info("WatermarkService: Downloading Roboto font from GitHub...");
            $response = Http::timeout(10)->get($url);

            if ($response->successful()) {
                file_put_contents($fontPath, $response->body());
                Log::info("WatermarkService: Font downloaded successfully to: {$fontPath}");
                return $fontPath;
            }
            
            Log::warning("WatermarkService: Failed to download font. Status: " . $response->status());
        } catch (\Exception $e) {
            Log::warning("WatermarkService: Error downloading font: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Apply a visual watermark (diagonal lines and text) to an image.
     *
     * @param string $sourcePath Path relative to the storage public disk (e.g. galleries/1/raw/photo.jpg)
     * @param string $outputPath Path relative to the storage public disk where watermarked image will be saved
     * @return bool
     */
    public function applyWatermark(string $sourcePath, string $outputPath): bool
    {
        $publicDisk = Storage::disk(config('filesystems.gallery_disk', 'public'));

        if (!$publicDisk->exists($sourcePath)) {
            Log::error("WatermarkService: Source image does not exist: {$sourcePath}");
            return false;
        }

        $fullSourcePath = $publicDisk->path($sourcePath);
        
        // Ensure destination directory exists
        $outputDirectory = dirname($outputPath);
        if (!$publicDisk->exists($outputDirectory)) {
            $publicDisk->makeDirectory($outputDirectory);
        }
        $fullOutputPath = $publicDisk->path($outputPath);

        // Get Image Type / Extension
        $extension = strtolower(pathinfo($fullSourcePath, PATHINFO_EXTENSION));

        // Load image using GD
        $image = null;
        try {
            switch ($extension) {
                case 'jpeg':
                case 'jpg':
                    $image = @imagecreatefromjpeg($fullSourcePath);
                    break;
                case 'png':
                    $image = @imagecreatefrompng($fullSourcePath);
                    break;
                case 'webp':
                    $image = @imagecreatefromwebp($fullSourcePath);
                    break;
                default:
                    // Try to load as string/any
                    $image = @imagecreatefromstring(file_get_contents($fullSourcePath));
                    break;
            }
        } catch (\Exception $e) {
            Log::error("WatermarkService: Error loading image with GD: " . $e->getMessage());
        }

        if (!$image) {
            Log::warning("WatermarkService: Falling back to simple file copy for: {$sourcePath}");
            return $publicDisk->copy($sourcePath, $outputPath);
        }

        // Apply visual watermarking
        $width = imagesx($image);
        $height = imagesy($image);

        // 1. Draw Diagonal Lines
        // Color: white, semi-transparent (alpha: 95 out of 127)
        $lineColor = imagecolorallocatealpha($image, 255, 255, 255, 95);
        if ($lineColor !== false) {
            // Set thickness relative to image size
            $thickness = max(2, round($width / 800));
            imagesetthickness($image, $thickness);

            // Draw line 1: top-left to bottom-right
            imageline($image, 0, 0, $width, $height, $lineColor);
            // Draw line 2: top-right to bottom-left
            imageline($image, 0, $height, $width, 0, $lineColor);
        }

        // 2. Draw Text Watermark (Premium typography using TTF, fallback to built-in GD font)
        $fontPath = $this->getFontPath();
        $studioName = strtoupper(\App\Models\Setting::getValue('studio_name', 'Raka Photo Studio'));
        $watermarkText = "© {$studioName} - PREVIEW ONLY";

        if ($fontPath && function_exists('imagettftext')) {
            // Dynamic Font Size (3.5% of width)
            $fontSize = max(14, round($width * 0.035));
            $angle = 25; // 25 degrees angle

            // Color: white, semi-transparent (alpha: 85 out of 127)
            $textColor = imagecolorallocatealpha($image, 255, 255, 255, 85);
            $shadowColor = imagecolorallocatealpha($image, 0, 0, 0, 95); // Dark shadow for readability

            if ($textColor !== false && $shadowColor !== false) {
                // Calculate bounding box to center it
                $bbox = imagettfbbox($fontSize, $angle, $fontPath, $watermarkText);
                
                // Get box width and height
                $textWidth = abs($bbox[4] - $bbox[0]);
                $textHeight = abs($bbox[5] - $bbox[1]);

                // Centering calculations
                $x = round(($width - $textWidth) / 2);
                $y = round(($height + $textHeight) / 2);

                // Draw shadow first
                imagettftext($image, $fontSize, $angle, $x + 2, $y + 2, $shadowColor, $fontPath, $watermarkText);
                
                // Draw text
                imagettftext($image, $fontSize, $angle, $x, $y, $textColor, $fontPath, $watermarkText);
            }
        } else {
            // Fallback: draw a banner and write text using native GD font
            $bannerHeight = max(40, round($height / 12));
            $y1 = round(($height - $bannerHeight) / 2);
            $y2 = $y1 + $bannerHeight;

            // Semi-transparent black banner
            $bannerColor = imagecolorallocatealpha($image, 0, 0, 0, 70);
            if ($bannerColor !== false) {
                imagefilledrectangle($image, 0, $y1, $width, $y2, $bannerColor);
            }

            // Solid white text
            $textColor = imagecolorallocate($image, 255, 255, 255);
            if ($textColor !== false) {
                $fontSize = 5; // Built-in font size (1-5)
                $fontWidth = imagefontwidth($fontSize);
                $fontHeight = imagefontheight($fontSize);

                $textWidth = strlen($watermarkText) * $fontWidth;
                $x = round(($width - $textWidth) / 2);
                $y = round($y1 + ($bannerHeight - $fontHeight) / 2);

                imagestring($image, $fontSize, $x, $y, $watermarkText, $textColor);
            }
        }

        // Save watermarked image to output directory
        $saved = false;
        try {
            switch ($extension) {
                case 'jpeg':
                case 'jpg':
                    $saved = imagejpeg($image, $fullOutputPath, 85); // 85% quality
                    break;
                case 'png':
                    // Enable transparency preservation
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                    $saved = imagepng($image, $fullOutputPath, 6); // compression level 6
                    break;
                case 'webp':
                    $saved = imagewebp($image, $fullOutputPath, 80);
                    break;
                default:
                    $saved = imagejpeg($image, $fullOutputPath, 85);
                    break;
            }
        } catch (\Exception $e) {
            Log::error("WatermarkService: Error saving watermarked image: " . $e->getMessage());
        } finally {
            imagedestroy($image);
        }

        return $saved;
    }
}
