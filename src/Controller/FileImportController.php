<?php
// src/Controller/FileImportController.php

namespace App\Controller;

use App\Service\FileTextExtractorService;
use App\Service\MealOptionImportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Handles file uploads of nutritionist diet documents and triggers
 * the AI-powered meal option import pipeline.
 */
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class FileImportController extends AbstractController
{
    /**
     * POST /api/meal-options/import
     *
     * Upload a nutritionist diet document (PDF, DOCX, MD / plain text).
     * OpenAI will identify meal options, estimate calories, and infer
     * ingredients. Created records are returned in the response.
     *
     * Multipart form field: file (required)
     */
    #[Route('/api/meal-options/import', name: 'api_meal_options_import', methods: ['POST'])]
    public function import(
        Request $request,
        FileTextExtractorService $extractor,
        MealOptionImportService $importService,
    ): JsonResponse {
        // ── 1. Validate file presence ─────────────────────────────────────────
        $file = $request->files->get('file');

        if (!$file) {
            return $this->json(['error' => 'No file uploaded. Send the document as a multipart/form-data field named "file".'], 400);
        }

        // ── 2. Validate file size (≤ 5 MB) ───────────────────────────────────
        if ($file->getSize() > FileTextExtractorService::MAX_SIZE_BYTES) {
            return $this->json(
                ['error' => sprintf('File exceeds the maximum allowed size of %d MB.', FileTextExtractorService::MAX_SIZE_BYTES / 1024 / 1024)],
                413
            );
        }

        // ── 3. Validate MIME type ─────────────────────────────────────────────
        $mime = $file->getMimeType() ?? '';

        if (!$extractor->isSupported($mime)) {
            return $this->json(
                [
                    'error'         => sprintf('Unsupported file type "%s".', $mime),
                    'allowed_types' => FileTextExtractorService::SUPPORTED_MIMES,
                ],
                400
            );
        }

        // ── 4. Extract plain text from file ───────────────────────────────────
        try {
            $text = $extractor->extract($file);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => 'Could not extract text from file: ' . $e->getMessage()], 422);
        }

        if (empty(trim($text))) {
            return $this->json(['error' => 'The uploaded file contains no readable text.'], 400);
        }

        // ── 5. Import meal options via OpenAI ─────────────────────────────────
        try {
            $result = $importService->importFromText($text);
        } catch (\RuntimeException $e) {
            // Distinguish AI-extraction failures (422) from underlying API errors (500)
            $isAiParseError = str_contains($e->getMessage(), 'could not extract')
                || str_contains($e->getMessage(), 'does not contain');

            return $this->json(
                ['error' => $e->getMessage()],
                $isAiParseError ? 422 : 500
            );
        }

        // ── 6. Return created meal options ────────────────────────────────────
        return $this->json($result, 201);
    }
}
