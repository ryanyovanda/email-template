<?php

namespace App\Services\Cv;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser as PdfParser;
use ZipArchive;

/**
 * Pulls plain text out of an uploaded CV so the AI has something to work from.
 * Scanned/image-only PDFs yield nothing useful — those come back as `empty`
 * and the user is asked to paste their CV text instead.
 */
class CvTextExtractor
{
    public const STATUS_PARSED = 'parsed';

    public const STATUS_EMPTY = 'empty';

    public const STATUS_UNSUPPORTED = 'unsupported';

    public const STATUS_FAILED = 'failed';

    /** Roughly the point where more CV text stops helping the model. */
    private const MAX_CHARS = 20000;

    /** Below this, the extraction is treated as having produced nothing usable. */
    private const MIN_USEFUL_CHARS = 200;

    /**
     * @return array{status: string, text: string}
     */
    public function extract(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            $text = match ($extension) {
                'pdf' => $this->fromPdf($file->getRealPath()),
                'docx' => $this->fromDocx($file->getRealPath()),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::warning('CV text extraction failed.', ['extension' => $extension, 'error' => $e->getMessage()]);

            return ['status' => self::STATUS_FAILED, 'text' => ''];
        }

        if ($text === null) {
            return ['status' => self::STATUS_UNSUPPORTED, 'text' => ''];
        }

        $text = $this->tidy($text);

        if (mb_strlen($text) < self::MIN_USEFUL_CHARS) {
            return ['status' => self::STATUS_EMPTY, 'text' => $text];
        }

        return ['status' => self::STATUS_PARSED, 'text' => $text];
    }

    private function fromPdf(string $path): string
    {
        return (new PdfParser)->parseFile($path)->getText();
    }

    private function fromDocx(string $path): string
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();

        // Paragraph and line breaks become real newlines before tags are stripped.
        $xml = preg_replace('/<\/w:p>/', "\n", $xml) ?? $xml;
        $xml = preg_replace('/<w:br[^>]*\/>/', "\n", $xml) ?? $xml;

        return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function tidy(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;
        $text = trim($text);

        return mb_substr($text, 0, self::MAX_CHARS);
    }
}
