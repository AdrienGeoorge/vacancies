<?php

namespace App\Service;

use Exception;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FileUploaderService
{
    private string $targetDirectory;
    private SluggerInterface $slugger;
    private TranslatorInterface $translator;

    private const MAX_WIDTH = 1200;
    private const WEBP_QUALITY = 75;

    public function __construct($targetDirectory, SluggerInterface $slugger, TranslatorInterface $translator)
    {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
        $this->translator = $translator;
    }

    /**
     * @throws Exception
     */
    public function upload(UploadedFile $file, ?string $name = null, ?string $directory = null): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $baseName = $name ?: $safeFilename . '-' . uniqid();

        $targetDir = $directory ?: $this->getTargetDirectory();
        $mimeType = $file->getMimeType();
        $isImage = str_starts_with($mimeType ?? '', 'image/') && $mimeType !== 'image/gif';

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        if ($isImage && function_exists('imagecreatefromjpeg')) {
            $fileName = $baseName . '.webp';
            $targetPath = $targetDir . '/' . $fileName;
            $this->convertAndResizeToWebP($file->getPathname(), $targetPath, $mimeType);
        } else {
            $fileName = $baseName . '.' . $file->guessExtension();
            try {
                $file->move($targetDir, $fileName);
            } catch (FileException $e) {
                throw new Exception($this->translator->trans('file.upload_error'));
            }
        }

        return $fileName;
    }

    /**
     * @throws Exception
     */
    private function convertAndResizeToWebP(string $sourcePath, string $targetPath, ?string $mimeType): void
    {
        $image = match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png'  => @imagecreatefrompng($sourcePath),
            'image/webp' => @imagecreatefromwebp($sourcePath),
            'image/avif' => function_exists('imagecreatefromavif') ? @imagecreatefromavif($sourcePath) : false,
            default      => false,
        };

        if (!$image) {
            throw new Exception($this->translator->trans('file.image_read_error'));
        }

        $origWidth  = imagesx($image);
        $origHeight = imagesy($image);

        if ($origWidth > self::MAX_WIDTH) {
            $ratio     = self::MAX_WIDTH / $origWidth;
            $newWidth  = self::MAX_WIDTH;
            $newHeight = (int) round($origHeight * $ratio);
            $resized   = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            imagedestroy($image);
            $image = $resized;
        }

        if (!imagewebp($image, $targetPath, self::WEBP_QUALITY)) {
            imagedestroy($image);
            throw new Exception($this->translator->trans('file.webp_error'));
        }

        imagedestroy($image);
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
}
