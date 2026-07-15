<?php

namespace App\Services\Ai;

use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class PdfTextExtractionService
{
    public function extract(string $absolutePdfPath): string
    {
        if (!is_file($absolutePdfPath)) {
            throw new RuntimeException('The selected PDF file could not be found.');
        }

        $pdftotextPath = config('ai.content.pdftotext_path', 'pdftotext');
        $process = new Process([$pdftotextPath, '-layout', '-enc', 'UTF-8', $absolutePdfPath, '-']);
        $process->setTimeout(45);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            $errorOutput = trim($exception->getProcess()->getErrorOutput());

            throw new RuntimeException(
                'PDF text extraction failed using "' . $pdftotextPath . '". ' .
                ($errorOutput ? 'pdftotext said: ' . $errorOutput : 'Install Poppler/pdftotext or set AI_PDFTOTEXT_PATH in .env.')
            );
        } catch (ProcessTimedOutException $exception) {
            throw new RuntimeException('PDF text extraction timed out. Try a smaller text-based PDF or install/verify Poppler performance on the server.');
        } catch (\Throwable $exception) {
            throw new RuntimeException('PDF text extraction is not available. Install Poppler/pdftotext and make sure the pdftotext command is available in PATH.');
        }

        $text = trim(preg_replace('/[ \t]+/', ' ', $process->getOutput()));
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        if (mb_strlen($text) < 100) {
            throw new RuntimeException('This PDF does not contain enough readable text for AI summary generation.');
        }

        return $text;
    }
}
