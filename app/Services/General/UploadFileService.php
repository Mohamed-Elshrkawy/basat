<?php

namespace App\Services\General;

use getID3;
use Exception;
use DOMDocument;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;


class UploadFileService
{
    private const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp', 'image/svg+xml'];
    private const ALLOWED_SVG_TYPES = ['image/svg+xml'];
    private const ALLOWED_VIDEO_TYPES = [
        'video/mp4',
        'video/mpeg',
        'video/quicktime',
        'video/x-msvideo',
        'video/x-ms-wmv',
        'video/webm',
        'video/ogg',
        'video/3gpp',
        'video/x-flv'
    ];
    private const ALLOWED_AUDIO_TYPES = [
        'audio/mpeg',
        'audio/wav',
        'audio/ogg',
        'audio/mp3',
        'audio/mp4',
        'audio/aac',
        'audio/webm',
        'audio/flac',
        'audio/x-wav',
        'audio/wave'
    ];

    private const THUMBNAIL_WIDTH = 300;
    private const THUMBNAIL_HEIGHT = 300;

    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB for documents
    private const MAX_SVG_SIZE = 2 * 1024 * 1024; // 2MB for SVG
    private const MAX_VIDEO_SIZE = 100 * 1024 * 1024; // 100MB for videos
    private const MAX_AUDIO_SIZE = 25 * 1024 * 1024; // 25MB for audio
    private const MAX_IMAGE_SIZE = 2 * 1024 * 1024; // 2MB for images
    private const MAX_PDF_SIZE = 20 * 1024 * 1024; // 20MB for PDF
    private const DEFAULT_IMAGE_QUALITY = 85;

    /**
     * Upload and process image files
     */
    public static function uploadImg(
        UploadedFile|array $files,
        string             $url = 'images',
        string             $key = 'image',
        ?int               $width = null,
        ?int               $height = null,
        int                $quality = self::DEFAULT_IMAGE_QUALITY
    ): string|array
    {
        try {
            $imageManager = new ImageManager(new Driver());
            $directory = self::createDirectory('images', $url);

            if (is_array($files)) {
                return self::processMultipleImages($files, $directory, $key, $width, $height, $quality, $imageManager);
            }

            return self::processSingleImage($files, $directory, $width, $height, $quality, $imageManager);
        } catch (Exception $e) {
            Log::error('Image upload failed: ' . $e->getMessage());
            throw new Exception('Failed to upload image: ' . $e->getMessage());
        }
    }

    /**
     * Upload and process video files
     */
    public static function uploadVideo(
        UploadedFile|array $files,
        string             $url = 'videos',
        string             $key = 'video',
        bool               $generateThumbnail = false,
        bool               $generateSLH = false
    ): string|array
    {
        try {
            $directory = self::createDirectory('videos', $url);
            $thumbnailDirectory = self::createDirectory('thumbnails', 'videos');

            if (is_array($files)) {
                return self::processMultipleVideos($files, $directory, $key);
            }

            return self::processSingleVideo($files, $directory, $generateThumbnail, $generateSLH, $thumbnailDirectory);
        } catch (Exception $e) {
            Log::error('Video upload failed: ' . $e->getMessage());
            throw new Exception('Failed to upload video: ' . $e->getMessage());
        }
    }

    /**
     * Upload PDF files with thumbnail generation
     */
    public static function uploadPdf(
        UploadedFile|array $files,
        string             $url = 'documents',
        string             $key = 'pdf',
        bool               $generateThumbnail = false
    ): string|array
    {
        try {
            $directory = self::createDirectory('documents', $url);
            $thumbnailDirectory = self::createDirectory('thumbnails', 'documents');

            if (is_array($files)) {
                return self::processMultiplePdfs($files, $directory, $key, $generateThumbnail, $thumbnailDirectory);
            }

            return self::processSinglePdf($files, $directory, $generateThumbnail, $thumbnailDirectory);
        } catch (Exception $e) {
            Log::error('PDF upload failed: ' . $e->getMessage());
            throw new Exception('Failed to upload PDF: ' . $e->getMessage());
        }
    }

    /**
     * Upload and process audio files
     */
    public static function uploadAudio(
        UploadedFile|array $files,
        string             $url = 'audio',
        string             $key = 'audio'
    ): string|array
    {
        try {
            $directory = self::createDirectory('audio', $url);

            if (is_array($files)) {
                return self::processMultipleAudio($files, $directory, $key);
            }

            return self::processSingleAudio($files, $directory);
        } catch (Exception $e) {
            Log::error('Audio upload failed: ' . $e->getMessage());
            throw new Exception('Failed to upload audio: ' . $e->getMessage());
        }
    }

    /**
     * Upload general files (no specific processing)
     */
    public static function uploadFile(
        UploadedFile|array $files,
        string             $url = 'files',
        string             $key = 'file'
    ): string|array
    {
        try {
            $directory = self::createDirectory('files', $url);

            if (is_array($files)) {
                return self::processMultipleFiles($files, $directory, $key);
            }

            return self::processSingleFile($files, $directory);
        } catch (Exception $e) {
            Log::error('File upload failed: ' . $e->getMessage());
            throw new Exception('Failed to upload file: ' . $e->getMessage());
        }
    }

    /**
     * Create directory structure
     */
    private static function createDirectory(string $baseDir, string $subDir): string
    {
        if (in_array($subDir, ['images', 'videos', 'audio', 'files', 'documents']) && $baseDir != 'thumbnails') {
            $path = storage_path("app/public/{$subDir}/");
        } else {
            $path = storage_path("app/public/{$baseDir}/{$subDir}/");
        }

        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }

        return $path;
    }

    /**
     * Validate uploaded file
     */
    private static function validateFile(UploadedFile $file, string $fileType = 'general'): void
    {
        if (!$file->isValid()) {
            throw new Exception('Invalid file upload');
        }

        $mimeType = $file->getMimeType();
        $maxSize = self::getMaxFileSize($mimeType, $fileType);

        if ($file->getSize() > $maxSize) {
            $limit = $maxSize / (1024 * 1024);
            throw new Exception("File size exceeds maximum limit of {$limit}MB");
        }

        // Validate file type based on category
        match ($fileType) {
            'image' => self::validateImageType($mimeType),
            'video' => self::validateVideoType($mimeType),
            'audio' => self::validateAudioType($mimeType),
            default => null // No specific validation for general files
        };

        // Additional SVG security validation
        if (in_array($mimeType, self::ALLOWED_SVG_TYPES)) {
            self::validateSvgContent($file);
        }
    }

    /**
     * Get maximum file size based on type
     */
    private static function getMaxFileSize(string $mimeType, string $fileType): int
    {
        return match (true) {
            in_array($mimeType, self::ALLOWED_SVG_TYPES) => self::MAX_SVG_SIZE,
            in_array($mimeType, self::ALLOWED_VIDEO_TYPES) => self::MAX_VIDEO_SIZE,
            in_array($mimeType, self::ALLOWED_AUDIO_TYPES) => self::MAX_AUDIO_SIZE,
            default => self::MAX_FILE_SIZE
        };
    }

    /**
     * Validate image file type
     */
    private static function validateImageType(string $mimeType): void
    {
        if (!in_array($mimeType, self::ALLOWED_IMAGE_TYPES)) {
            throw new Exception('Invalid image format');
        }
    }

    /**
     * Validate video file type
     */
    private static function validateVideoType(string $mimeType): void
    {
        if (!in_array($mimeType, self::ALLOWED_VIDEO_TYPES)) {
            throw new Exception('Invalid video format');
        }
    }

    /**
     * Validate audio file type
     */
    private static function validateAudioType(string $mimeType): void
    {
        if (!in_array($mimeType, self::ALLOWED_AUDIO_TYPES)) {
            throw new Exception('Invalid audio format');
        }
    }

    /**
     * Process single image
     */
    private static function processSingleImage(
        UploadedFile $file,
        string       $directory,
        ?int         $width,
        ?int         $height,
        int          $quality,
        ImageManager $imageManager
    ): string
    {
        self::validateFile($file, 'image');

        $fileName = Str::uuid() . '.webp';

        if (in_array($file->getMimeType(), self::ALLOWED_SVG_TYPES)) {
            return self::uploadSvg($file, $directory);
        }

        if ($file->getMimeType() === 'image/gif') {
            return self::uploadGif($file, $directory);
        }

        $image = $imageManager
            ->read($file->getPathname())
            ->orient();

        if ($width || $height) {
            $image->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        $image->toWebp($quality)->save($directory . $fileName);

        return $fileName;
    }

    /**
     * Process single video
     */
    private static function processSingleVideo(
        UploadedFile $file,
        string       $directory,
        bool         $generateThumbnail = false,
        bool         $generateSLH = false,
        string       $thumbnailDirectory = ''
    ): array
    {
        $result = [];
        self::validateFile($file, 'video');

        $fileName = self::generateFileName($file);
        $filePath = $directory . $fileName;

        if (!$file->move($directory, $fileName)) {
            throw new Exception('Failed to move uploaded video');
        }

        $result['filename'] = $fileName;

        try {
            if($generateSLH) {
                $hlsDir = rtrim($directory, '/') . '/hls/' . pathinfo($fileName, PATHINFO_FILENAME);
                if (!is_dir($hlsDir)) {
                    mkdir($hlsDir, 0777, true);
                }

                self::generateMultiQualityHLS($filePath, $hlsDir, 'stream');

                $result['hls_name'] = 'storage/videos/hls/' . pathinfo($fileName, PATHINFO_FILENAME) . '/stream_master.m3u8';
                $result['hls_240p_name'] = 'storage/videos/hls/' . pathinfo($fileName, PATHINFO_FILENAME) . '/stream_240p.m3u8';
                $result['hls_360p_name'] = 'storage/videos/hls/' . pathinfo($fileName, PATHINFO_FILENAME) . '/stream_360p.m3u8';
                $result['hls_480p_name'] = 'storage/videos/hls/' . pathinfo($fileName, PATHINFO_FILENAME) . '/stream_480p.m3u8';
                $result['hls_720p_name'] = 'storage/videos/hls/' . pathinfo($fileName, PATHINFO_FILENAME) . '/stream_720p.m3u8';
                $result['hls_1080p_name'] = 'storage/videos/hls/' . pathinfo($fileName, PATHINFO_FILENAME) . '/stream_1080p.m3u8';

            }
            if ($generateThumbnail && $thumbnailDirectory) {
                $thumbnailName = self::generateVideoThumbnail($filePath, $thumbnailDirectory, $fileName);

                $result['thumbnail_name'] = 'storage/thumbnails/videos/' . $thumbnailName;
            }

        } catch (Exception $e) {
            Log::warning('Failed to generate HLS: ' . $e->getMessage());
        }

        return $result;
    }


    /**
     * Process multiple videos
     */
    private static function processMultipleVideos(
        array  $files,
        string $directory,
        string $key,
        bool   $generateThumbnail = false,
        string $thumbnailDirectory = ''
    ): array
    {
        $videos = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $fileName = self::processSingleVideo($file, $directory, $generateThumbnail, $thumbnailDirectory);
                $videos[] = [$key => $fileName];
            }
        }

        return $videos;
    }

    /**
     * Process single PDF with thumbnail generation
     */
    private static function processSinglePdf(
        UploadedFile $file,
        string       $directory,
        bool         $generateThumbnail = false,
        string       $thumbnailDirectory = ''
    ): array
    {
        // Validate PDF
        if ($file->getMimeType() !== 'application/pdf') {
            throw new Exception('Invalid PDF file');
        }

        self::validateFile($file);

        $fileName = self::generateFileName($file);
        $filePath = $directory . $fileName;

        if (!$file->move($directory, $fileName)) {
            throw new Exception('Failed to move uploaded PDF');
        }

        $result = ['filename' => $fileName];

        // Generate thumbnail if requested
        if ($generateThumbnail && $thumbnailDirectory) {
            try {
                $thumbnailName = self::generatePdfThumbnail($filePath, $thumbnailDirectory, $fileName);
                $result['thumbnail'] = $thumbnailName;
            } catch (Exception $e) {
                Log::warning('Failed to generate PDF thumbnail: ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Process multiple PDFs with thumbnails
     */
    private static function processMultiplePdfs(
        array  $files,
        string $directory,
        string $key,
        bool   $generateThumbnail = false,
        string $thumbnailDirectory = ''
    ): array
    {
        $pdfs = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $result = self::processSinglePdf($file, $directory, $generateThumbnail, $thumbnailDirectory);
                $pdfs[] = [$key => $result];
            }
        }

        return $pdfs;
    }

    /**
     * Generate a unique file name
     */
    private static function processSingleAudio(UploadedFile $file, string $directory): string
    {
        self::validateFile($file, 'audio');

        $fileName = self::generateFileName($file);
        $filePath = $directory . $fileName;

        if (!$file->move($directory, $fileName)) {
            throw new Exception('Failed to move uploaded audio');
        }

        // Extract audio metadata (optional)
        // self::extractAudioMetadata($filePath);

        return $fileName;
    }

    /**
     * Process multiple audio files
     */
    private static function processMultipleAudio(array $files, string $directory, string $key): array
    {
        $audioFiles = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $fileName = self::processSingleAudio($file, $directory);
                $audioFiles[] = [$key => $fileName];
            }
        }

        return $audioFiles;
    }

    /**
     * Generate a unique file name
     */
    private static function processMultipleImages(
        array        $files,
        string       $directory,
        string       $key,
        ?int         $width,
        ?int         $height,
        int          $quality,
        ImageManager $imageManager
    ): array
    {
        $images = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $fileName = self::processSingleImage($file, $directory, $width, $height, $quality, $imageManager);
                $images[] = [$key => $fileName];
            }
        }

        return $images;
    }

    /**
     * Process single file
     */
    private static function processSingleFile(UploadedFile $file, string $directory): string
    {
        self::validateFile($file);

        $fileName = self::generateFileName($file);

        if (!$file->move($directory, $fileName)) {
            throw new Exception('Failed to move uploaded file');
        }

        return $fileName;
    }

    /**
     * Process multiple files
     */
    private static function processMultipleFiles(array $files, string $directory, string $key): array
    {
        $uploadedFiles = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $fileName = self::processSingleFile($file, $directory);
                $uploadedFiles[] = [$key => $fileName];
            }
        }

        return $uploadedFiles;
    }

    /**
    * Generate multi-quality HLS playlist
    */
    private static function generateMultiQualityHLS(string $videoPath, string $outputDir, string $baseName = 'stream'): bool
    {
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $ffmpegPath = self::findFFmpeg();
        if (!$ffmpegPath) {
            throw new Exception('FFmpeg not found. Cannot generate HLS.');
        }

        $qualities = [
            '240'  => ['videoBitrate' => '400k',  'audioBitrate' => '64k'],
            '360'  => ['videoBitrate' => '800k',  'audioBitrate' => '96k'],
            '480'  => ['videoBitrate' => '1400k', 'audioBitrate' => '128k'],
            '720'  => ['videoBitrate' => '2800k', 'audioBitrate' => '128k'],
            '1080' => ['videoBitrate' => '5000k', 'audioBitrate' => '192k'],
        ];

        $playlistFiles = [];

        foreach ($qualities as $label => $config) {
            $outputPath = "{$outputDir}/{$baseName}_{$label}p.m3u8";

            $process = new Process([
                $ffmpegPath,
                '-i', $videoPath,
                '-c:a', 'aac',
                '-ar', '48000',
                '-c:v', 'h264',
                '-profile:v', 'main',
                '-crf', '20',
                '-sc_threshold', '0',
                '-g', '48',
                '-keyint_min', '48',
                '-b:v', $config['videoBitrate'],
                '-maxrate', $config['videoBitrate'],
                '-bufsize', '1200k',
                '-b:a', $config['audioBitrate'],
                '-vf', "scale=-2:{$label}",
                '-hls_time', '4',
                '-hls_playlist_type', 'vod',
                '-hls_segment_filename', "{$outputDir}/{$baseName}_{$label}p_%03d.ts",
                '-f', 'hls',
                $outputPath
            ]);

            $process->setTimeout(0);
            $process->run(function ($type, $buffer) use ($label) {
                if ($type === Process::ERR) {
                    Log::info("FFmpeg [$label] STDERR: " . $buffer);
                } else {
                    Log::debug("FFmpeg [$label] STDOUT: " . $buffer);
                }
            });

            if (!$process->isSuccessful()) {
                Log::error('FFmpeg failed for ' . $label . 'p: ' . $process->getErrorOutput());
                throw new ProcessFailedException($process);
            }

            $playlistFiles[] = [
                'path'       => "{$baseName}_{$label}p.m3u8",
                'bandwidth'  => (int) filter_var($config['videoBitrate'], FILTER_SANITIZE_NUMBER_INT) * 1024,
                'resolution' => "auto x {$label}",
            ];
        }

        $master = "#EXTM3U\n";
        foreach ($playlistFiles as $file) {
            $master .= "#EXT-X-STREAM-INF:BANDWIDTH={$file['bandwidth']},RESOLUTION={$file['resolution']}\n{$file['path']}\n";
        }

        file_put_contents("{$outputDir}/{$baseName}_master.m3u8", $master);

        return true;
    }
    /**
     * Handle SVG upload (no processing, direct upload)
     */
    private static function uploadSvg(UploadedFile $file, string $directory): string
    {
        $fileName = self::generateFileName($file);

        if (!$file->move($directory, $fileName)) {
            throw new Exception('Failed to upload SVG');
        }

        return $fileName;
    }

    /**
     * Validate SVG content for security
     */
    private static function validateSvgContent(UploadedFile $file): void
    {
        $content = file_get_contents($file->getPathname());

        if ($content === false) {
            throw new Exception('Cannot read SVG file');
        }

        // Check for potentially dangerous elements
        $dangerousPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/<iframe\b[^>]*>/mi',
            '/<object\b[^>]*>/mi',
            '/<embed\b[^>]*>/mi',
            '/javascript:/mi',
            '/data:text\/html/mi',
            '/vbscript:/mi',
            '/on\w+\s*=/mi', // onclick, onload, etc.
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                throw new Exception('SVG contains potentially dangerous content');
            }
        }

        // Validate XML structure
        $previousUseErrors = libxml_use_internal_errors(true);
        $dom = new DOMDocument();

        if (!$dom->loadXML($content)) {
            libxml_use_internal_errors($previousUseErrors);
            throw new Exception('Invalid SVG XML structure');
        }

        libxml_use_internal_errors($previousUseErrors);

        // Check if it's actually an SVG
        if ($dom->documentElement->nodeName !== 'svg') {
            throw new Exception('File is not a valid SVG');
        }
    }

    private static function uploadGif(UploadedFile $file, string $directory): string
    {
        $fileName = self::generateFileName($file);

        if (!$file->move($directory, $fileName)) {
            throw new Exception('Failed to upload GIF');
        }

        return $fileName;
    }

    /**
     * Generate video thumbnail using FFmpeg
     */
    private static function generateVideoThumbnail(string $videoPath, string $thumbnailDir, string $originalFileName): string
    {
        $ffmpegPath = self::findFFmpeg();
        if (!$ffmpegPath) {
            throw new Exception('FFmpeg not found. Cannot generate video thumbnail.');
        }

        $thumbnailName = pathinfo($originalFileName, PATHINFO_FILENAME) . '_thumb.webp';
        $thumbnailPath = rtrim($thumbnailDir, '/') . '/' . $thumbnailName;

        if (!is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0777, true);
        }

        $process = new Process([
            $ffmpegPath,
            '-i', $videoPath,
            '-ss', '00:00:02',
            '-vframes', '1',
            '-vf', sprintf(
                'scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2',
                self::THUMBNAIL_WIDTH,
                self::THUMBNAIL_HEIGHT,
                self::THUMBNAIL_WIDTH,
                self::THUMBNAIL_HEIGHT
            ),
            '-vcodec', 'libwebp',
            '-compression_level', '6',
            '-qscale', '90',
            '-y',
            $thumbnailPath
        ]);

        $process->setTimeout(0);
        $process->run(function ($type, $buffer) {
            if ($type === Process::ERR) {
                Log::debug('[FFmpeg thumbnail STDERR] ' . $buffer);
            } else {
                Log::debug('[FFmpeg thumbnail STDOUT] ' . $buffer);
            }
        });

        if (!$process->isSuccessful() || !file_exists($thumbnailPath)) {
            Log::error('Failed to generate thumbnail: ' . $process->getErrorOutput());
            throw new ProcessFailedException($process);
        }

        return $thumbnailName;
    }
    /**
     * Generate PDF thumbnail using ImageMagick
     */
    private static function generatePdfThumbnail(string $pdfPath, string $thumbnailDir, string $originalFileName): string
    {
        $convertPath = self::findImageMagick();
        if (!$convertPath) {
            throw new Exception('ImageMagick not found. Cannot generate PDF thumbnail.');
        }

        $thumbnailName = pathinfo($originalFileName, PATHINFO_FILENAME) . '_thumb.jpg';
        $thumbnailPath = rtrim($thumbnailDir, '/') . '/' . $thumbnailName;

        if (!is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0777, true);
        }

        $process = new Process([
            $convertPath,
            '-density', '150',
            "{$pdfPath}[0]",
            '-resize', sprintf('%dx%d', self::THUMBNAIL_WIDTH, self::THUMBNAIL_HEIGHT),
            '-quality', '90',
            $thumbnailPath
        ]);

        $process->setTimeout(0);
        $process->run(function ($type, $buffer) {
            if ($type === Process::ERR) {
                Log::debug('[ImageMagick thumbnail STDERR] ' . $buffer);
            } else {
                Log::debug('[ImageMagick thumbnail STDOUT] ' . $buffer);
            }
        });

        if (!$process->isSuccessful() || !file_exists($thumbnailPath)) {
            Log::error('Failed to generate PDF thumbnail: ' . $process->getErrorOutput());
            throw new ProcessFailedException($process);
        }

        return $thumbnailName;
    }
    /**
     * Find FFmpeg binary path
     */
    private static function findFFmpeg(): ?string
    {
        $possiblePaths = [
            'C:\\ffmpeg\\bin\\ffmpeg.exe',
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/opt/homebrew/bin/ffmpeg',
            'ffmpeg', // System PATH
        ];

        foreach ($possiblePaths as $path) {
            if ($path === 'ffmpeg') {
                try {
                    $process = new Process(['which', 'ffmpeg']);
                    $process->run();

                    if ($process->isSuccessful()) {
                        $foundPath = trim($process->getOutput());
                        if ($foundPath && is_executable($foundPath)) {
                            return $foundPath;
                        }
                    }

                } catch (ProcessFailedException $e) {
                    Log::warning('Failed to locate ffmpeg via PATH: ' . $e->getMessage());
                }
            } elseif (is_executable($path)) {
                return $path;
            }
        }

        Log::error('FFmpeg binary not found in any known path.');
        return null;
    }
    /**
     * Find ImageMagick convert binary path
     */
    private static function findImageMagick(): ?string
    {
        $possiblePaths = [
            '/usr/bin/convert',
            '/usr/local/bin/convert',
            '/opt/homebrew/bin/convert',
            '/usr/bin/magick',
            '/usr/local/bin/magick',
            '/opt/homebrew/bin/magick',
            'convert',
            'magick',
        ];

        foreach ($possiblePaths as $path) {
            if (in_array($path, ['convert', 'magick'])) {
                try {
                    $process = new Process(['which', $path]);
                    $process->run();

                    if ($process->isSuccessful()) {
                        $foundPath = trim($process->getOutput());
                        if ($foundPath && is_executable($foundPath)) {
                            return $path === 'magick' ? "{$foundPath} convert" : $foundPath;
                        }
                    }
                } catch (ProcessFailedException $e) {
                    Log::warning("Failed to locate ImageMagick binary '{$path}': " . $e->getMessage());
                }
            } elseif (is_executable($path)) {
                return $path;
            }
        }

        Log::error('ImageMagick binary not found in any known path.');
        return null;
    }
    /**
     * Extract video metadata using getID3 library (optional)
     */
    private static function extractVideoMetadata(string $filePath): ?array
    {
        try {
            if (!class_exists('\getID3')) {
                return null;
            }

            $getID3 = new getID3();
            $info = $getID3->analyze($filePath);

            return [
                'duration' => $info['playtime_seconds'] ?? null,
                'width' => $info['video']['resolution_x'] ?? null,
                'height' => $info['video']['resolution_y'] ?? null,
                'format' => $info['fileformat'] ?? null,
                'bitrate' => $info['bitrate'] ?? null,
                'size' => $info['filesize'] ?? null,
            ];
        } catch (Exception $e) {
            Log::warning('Failed to extract video metadata: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract audio metadata using getID3 library (optional)
     */
    private static function extractAudioMetadata(string $filePath): ?array
    {
        try {
            if (!class_exists('\getID3')) {
                return null;
            }

            $getID3 = new getID3();
            $info = $getID3->analyze($filePath);

            return [
                'duration' => $info['playtime_seconds'] ?? null,
                'bitrate' => $info['audio']['bitrate'] ?? null,
                'sample_rate' => $info['audio']['sample_rate'] ?? null,
                'channels' => $info['audio']['channels'] ?? null,
                'format' => $info['fileformat'] ?? null,
                'title' => $info['tags']['id3v2']['title'][0] ?? null,
                'artist' => $info['tags']['id3v2']['artist'][0] ?? null,
                'album' => $info['tags']['id3v2']['album'][0] ?? null,
                'size' => $info['filesize'] ?? null,
            ];
        } catch (Exception $e) {
            Log::warning('Failed to extract audio metadata: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate a unique file name
     */
    private static function generateFileName(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return Str::uuid() . '.' . $extension;
    }

    private static function getFileName(UploadedFile $file): string
    {
        return $file->getClientOriginalName();
    }

    /**
     * Delete uploaded file
     */
    public static function deleteFile(string $fileName, string $directory = 'images'): bool
    {
        try {
            $path = "public/{$directory}/{$fileName}";

            if (Storage::exists($path)) {
                return Storage::delete($path);
            }

            return true;
        } catch (Exception $e) {
            Log::error('File deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get file URL for any media type
     */
    public static function getFileUrl(string $fileName, string $directory = 'images'): string
    {
        return Storage::url("{$directory}/{$fileName}");
    }

    /**
     * Get media info (works for images, videos, audio)
     */
    public static function getMediaInfo(string $fileName, string $directory = 'images'): array
    {
        $path = storage_path("app/public/{$directory}/{$fileName}");

        if (!file_exists($path)) {
            throw new Exception('File not found');
        }

        $info = [
            'filename' => $fileName,
            'size' => filesize($path),
            'mime_type' => mime_content_type($path),
            'extension' => pathinfo($fileName, PATHINFO_EXTENSION),
            'url' => self::getFileUrl($fileName, $directory),
        ];

        // Add specific metadata based on file type
        $mimeType = $info['mime_type'];

        if (in_array($mimeType, self::ALLOWED_VIDEO_TYPES)) {
            $info['type'] = 'video';
            $info['metadata'] = self::extractVideoMetadata($path);
        } elseif (in_array($mimeType, self::ALLOWED_AUDIO_TYPES)) {
            $info['type'] = 'audio';
            $info['metadata'] = self::extractAudioMetadata($path);
        } elseif (in_array($mimeType, self::ALLOWED_IMAGE_TYPES)) {
            $info['type'] = 'image';

            if (!in_array($mimeType, self::ALLOWED_SVG_TYPES)) {
                $dimensions = getimagesize($path);
                $info['metadata'] = [
                    'width' => $dimensions[0] ?? null,
                    'height' => $dimensions[1] ?? null,
                ];
            }
        } else {
            $info['type'] = 'file';
        }

        return $info;
    }
}
