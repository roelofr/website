<?php

declare(strict_types=1);

namespace Database\Seeders\Content;

use App\Models\Content\MailTemplate;
use App\Models\Data\Content\MailTemplateParam;
use App\Services\FrontmatterParser;
use Illuminate\Database\Seeder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;

class MailTemplateSeeder extends Seeder
{
    private const MAIL_TEMPLATE_RESOURCE_DIR = 'markdown/mail-templates';

    private const VALID_HEADER_KEYS = [
        'label',
        'subject',
        'parameters',
        'footnote',
    ];

    private FrontmatterParser $parser;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(FrontmatterParser $parser)
    {
        $this->parser = $parser;

        // Load all templates from the resources/markdown/mail directory
        $fs = new Filesystem();
        $seenFiles = [];

        foreach ($fs->allFiles(resource_path(self::MAIL_TEMPLATE_RESOURCE_DIR)) as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'md') {
                continue;
            }

            try {
                $fileParams = $this->processFile($file);
                $fileLabel = $fileParams['label'];

                if (isset($seenFiles[$fileLabel])) {
                    throw new RuntimeException("Duplicate mail template label: {$fileLabel}");
                }

                $seenFiles[$fileLabel] = $fileParams;
            } catch (InvalidArgumentException $exception) {
                Log::error('Failed to process {file}: {exception}', ['file' => $file, 'exception' => $exception]);

                throw new RuntimeException("Failed to process [{$file->getRelativePathname()}]: {$exception->getMessage()}");
            }
        }

        foreach ($seenFiles as $fileLabel => $fileParams) {
            MailTemplate::updateOrCreate(
                ['label' => $fileLabel],
                $fileParams,
            );
        }

        MailTemplate::query()
            ->whereNotIn('label', array_keys($seenFiles))
            ->delete();
    }

    private function processFile(SplFileInfo $file): array
    {
        // Construct default parameters, based from file info
        $contents = $file->getContents();
        $filename = Str::beforeLast($file->getRelativePathname(), '.md');
        $defaultParams = [
            'label' => Str::slug($filename),
            'params' => [],
            'subject' => Str::title($filename),
            'body' => $contents,
            'footnote' => null,
        ];

        // Load the header, if no header is present, this'll be null and we'll just return as-is.
        $header = $this->parser->parseHeader($contents);
        if ($header === null) {
            return $defaultParams;
        }

        // Check header keys
        $headerKeys = array_keys($header);
        $invalidHeaderKeys = array_diff($headerKeys, self::VALID_HEADER_KEYS);

        if ($invalidHeaderKeys) {
            $mergedHeaderKeys = implode(', ', $invalidHeaderKeys);

            throw new InvalidArgumentException("Invalid header keys [{$mergedHeaderKeys}].");
        }

        // Validate all template parameters
        $validTemplateParams = [];
        foreach ($header['parameters'] ?? [] as $key => $param) {
            // Process a simple list of params
            if (is_int($key) && is_string($param)) {
                $validTemplateParams[] = new MailTemplateParam(
                    name: $param,
                    description: null,
                );

                continue;
            }

            // Process a map of name: description
            if (is_string($param) && is_string($param)) {
                $validTemplateParams[] = new MailTemplateParam(
                    name: $key,
                    description: $param,
                );

                continue;
            }

            // Process a map of { name: ..., description: ... }
            if (is_array($param) && Arr::has($param, ['name', 'description'])) {
                $validTemplateParams[] = new MailTemplateParam(
                    name: $param['name'],
                    description: $param['description'],
                );

                continue;
            }

            // Fail
            throw new InvalidArgumentException("Invalid parameter definition on key [{$key}].");
        }

        // Done, create the new MailTemplate attributes
        return array_merge($defaultParams, array_filter([
            'body' => $this->parser->parseBody($contents),
            'label' => $header['label'] ?? null,
            'subject' => $header['subject'] ?? null,
            'footnote' => $header['footnote'] ?? null,
            'params' => $validTemplateParams,
        ]));
    }
}
