<?php

namespace App\Service;

class TextPreprocessor
{
    public function cleanAndSplitText(string $filePath): string
    {
        $fileContent = file_get_contents($filePath);
        $cleanedContent = strip_tags($fileContent); // Supprimer les balises HTML
        return $cleanedContent;
    }
}
