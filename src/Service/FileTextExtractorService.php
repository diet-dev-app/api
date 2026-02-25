<?php
// src/Service/FileTextExtractorService.php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Extracts plain text from uploaded documents (PDF, DOCX, Markdown / plain text).
 *
 * Dependencies (install inside the PHP container):
 *   composer require smalot/pdfparser phpoffice/phpword
 */
class FileTextExtractorService
{
    /** Maximum allowed file size in bytes (5 MB). */
    public const MAX_SIZE_BYTES = 5 * 1024 * 1024;

    /** MIME types accepted by the import endpoint. */
    public const SUPPORTED_MIMES = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/markdown',
        'text/plain',
    ];

    /**
     * Extract plain text from an uploaded file.
     *
     * @param UploadedFile $file The uploaded file
     * @return string            Extracted plain text
     *
     * @throws \InvalidArgumentException When the MIME type is not supported
     * @throws \RuntimeException         When text extraction fails
     */
    public function extract(UploadedFile $file): string
    {
        $mime = $file->getMimeType() ?? '';
        $path = $file->getRealPath();

        if (!$path || !file_exists($path)) {
            throw new \RuntimeException('Uploaded file could not be read from disk.');
        }

        return match (true) {
            $mime === 'application/pdf'                                                                   => $this->extractPdf($path),
            $mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'          => $this->extractDocx($path),
            in_array($mime, ['text/markdown', 'text/plain'], true)                                       => $this->extractText($path),
            // Fallback: try text read for unknown MIME that physically looks like text
            default => throw new \InvalidArgumentException(
                sprintf(
                    'Unsupported file type "%s". Allowed: PDF, DOCX, Markdown, plain text.',
                    $mime
                )
            ),
        };
    }

    /**
     * Return whether a MIME type is supported by this extractor.
     */
    public function isSupported(string $mime): bool
    {
        return in_array($mime, self::SUPPORTED_MIMES, true);
    }

    // -------------------------------------------------------------------------
    // Private extraction methods
    // -------------------------------------------------------------------------

    /**
     * Extract text from a PDF file using smalot/pdfparser.
     */
    private function extractPdf(string $path): string
    {
        if (!class_exists(\Smalot\PdfParser\Parser::class)) {
            throw new \RuntimeException(
                'PDF parsing requires "smalot/pdfparser". '
                . 'Run: docker-compose exec php composer require smalot/pdfparser'
            );
        }

        try {
            $parser   = new \Smalot\PdfParser\Parser();
            $pdf      = $parser->parseFile($path);
            $text     = $pdf->getText();

            if (empty(trim($text))) {
                throw new \RuntimeException(
                    'The PDF appears to be empty or contains only images (no extractable text).'
                );
            }

            return $text;
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to parse PDF: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Extract text from a DOCX file using phpoffice/phpword.
     */
    private function extractDocx(string $path): string
    {
        if (!class_exists(\PhpOffice\PhpWord\IOFactory::class)) {
            throw new \RuntimeException(
                'DOCX parsing requires "phpoffice/phpword". '
                . 'Run: docker-compose exec php composer require phpoffice/phpword'
            );
        }

        try {
            $phpWord  = \PhpOffice\PhpWord\IOFactory::load($path);
            $sections = $phpWord->getSections();
            $lines    = [];

            foreach ($sections as $section) {
                foreach ($section->getElements() as $element) {
                    $lines[] = $this->elementToText($element);
                }
            }

            $text = implode("\n", array_filter($lines));

            if (empty(trim($text))) {
                throw new \RuntimeException('The DOCX file appears to be empty.');
            }

            return $text;
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to parse DOCX: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Read plain text or Markdown files directly.
     */
    private function extractText(string $path): string
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException('Could not read the text file.');
        }

        if (empty(trim($content))) {
            throw new \RuntimeException('The file is empty.');
        }

        return $content;
    }

    /**
     * Recursively convert a PhpWord element to its text representation.
     */
    private function elementToText(object $element): string
    {
        // TextRun contains child elements
        if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
            $parts = [];
            foreach ($element->getElements() as $child) {
                $parts[] = $this->elementToText($child);
            }
            return implode('', $parts);
        }

        // Simple Text element
        if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
            return $element->getText();
        }

        // Table element – iterate rows and cells
        if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
            $rows = [];
            foreach ($element->getRows() as $row) {
                $cells = [];
                foreach ($row->getCells() as $cell) {
                    $cellParts = [];
                    foreach ($cell->getElements() as $cellElement) {
                        $cellParts[] = $this->elementToText($cellElement);
                    }
                    $cells[] = implode(' ', array_filter($cellParts));
                }
                $rows[] = implode(' | ', $cells);
            }
            return implode("\n", $rows);
        }

        return '';
    }
}
