<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class FrontmatterParser
{
    public function parseHeader(string $contents): ?array
    {
        return $this->parseDocument($contents)['header'];
    }

    public function parseBody(string $contents): string
    {
        return $this->parseDocument($contents)['body'];
    }

    private function parseDocument(string $document): array
    {
        $header = null;
        $body = $document;

        $splitDocument = preg_split('/^(---|\.\.\.)$/m', $document, 3);

        // A header is likely found, and it's not a Markdown separator.
        if (count($splitDocument) === 3 && empty($splitDocument[0])) {
            try {
                $header = Yaml::parse($splitDocument[1]) ?? [];
                $body = $splitDocument[2];
            } catch (ParseException $exception) {
                Log::warning('Failed to parse Frontmatter on input {document}.', [
                    'document' => $document,
                    'exception' => $exception,
                ]);
            }
        }

        return [
            'header' => $header,
            'body' => trim($body),
        ];
    }
}
