<?php

namespace App\Service;

use Exception;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploaderService
{
    private string $targetDirectory;
    private SluggerInterface $slugger;

    public function __construct($targetDirectory, SluggerInterface $slugger)
    {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
    }

    /**
     * @throws Exception
     */
    public function upload(UploadedFile $file, ?string $name = null, ?string $directory = null): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = ($name ?: $safeFilename . '-' . uniqid()) . '.' . $file->guessExtension();

        try {
            if (!$directory) $file->move($this->getTargetDirectory(), $fileName);
            else $file->move($directory, $fileName);
        } catch (FileException $e) {
            throw new Exception('Erreur lors du téléchargement du fichier!');
        }

        return $fileName;
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
}
