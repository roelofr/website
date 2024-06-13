<?php

declare(strict_types=1);

namespace App\Console\Commands\Backup;

use App\Helpers\Str;
use App\Models\Activity;
use App\Models\NewsItem;
use App\Models\Page;
use App\Models\Sponsor;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

class RestoreImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:restore-images {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restores images for all given activities';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Get filename
        $filename = $this->argument('file');
        if (! preg_match('/^[a-z0-9_-]+\.zip$/', $filename)) {
            $this->error('File does not seem valid');
        }

        // Fetch file
        $filePath = sprintf('%s/%s', BackupImagesCommand::BASE_PATH, $filename);

        // Log
        $this->line("Restoring from <info>{$filePath}</>...");

        // Get a tempname
        $tempFile = tempnam(sys_get_temp_dir(), 'zipfile');
        $originalStream = Storage::readStream($filePath);

        // Copy stream
        file_put_contents($tempFile, $originalStream);

        // Close streams
        fclose($originalStream);

        // Get zip handle
        $zip = new ZipArchive();
        $zip->open($tempFile);

        // Arhive
        $this->line("Fetching map <info>{$filePath}</>...");
        $map = $this->mapArchive($zip);

        // Get all activities
        $this->assign($zip, $map, Activity::class);

        // Get all sponsors
        $this->assign($zip, $map, Sponsor::class);

        // Get all pages
        $this->assign($zip, $map, Page::class);

        // Get all news items
        $this->assign($zip, $map, NewsItem::class);

        // Close it
        $zip->close();
    }

    /**
     * Maps the archive to an array.
     */
    public function mapArchive(ZipArchive $zip): array
    {
        // Result
        $result = [];

        // Iterate files
        for ($index = 0; $index < $zip->numFiles; $index++) {
            // Get file name
            $file = $zip->getNameIndex($index);

            // Skip if invalid URL
            if (! preg_match('/^\/?([a-z0-9-]+)\/([a-z0-9-]+)\/([a-z0-9-]+)\//', $file)) {
                continue;
            }

            // Get parts
            [$model, $primaryKey, $property] = explode('/', $file, 4);

            // Make model map
            if (! isset($result[$model])) {
                $result[$model] = [];
            }

            // Make primaryKey map
            if (! isset($result[$primaryKey])) {
                $result[$model][$primaryKey] = [];
            }

            // Assign property
            $result[$model][$primaryKey][$property] = $index;
        }

        // Sort model names
        ksort($result);

        // Sort results per model
        foreach (array_keys($result) as $key) {
            ksort($result[$key]);
        }

        // Done
        return $result;
    }

    public function assign(ZipArchive $zip, array $map, string $className): void
    {
        // Prep some values
        $pathName = Str::snake(class_basename($className), '-');
        $classDisplay = ucwords(Str::snake(class_basename($className), ' '));

        // Skip
        if (! isset($map[$pathName])) {
            $this->line(
                "No items for <info>{$classDisplay}</>.",
                null,
                OutputInterface::VERBOSITY_VERBOSE,
            );

            return;
        }

        // Prep a base
        $baseClass = new $className();
        assert($baseClass instanceof Model);

        // Get affected models
        $classCursor = $className::query()
            ->whereIn($baseClass->getKeyName(), array_keys($map[$pathName]))
            ->cursor();

        // Keep track of updates
        $this->line(
            "Updating <info>{$classDisplay}</>...",
            null,
            OutputInterface::VERBOSITY_VERY_VERBOSE,
        );
        $updateCount = 0;

        // Class cursor
        foreach ($classCursor as $model) {
            assert($model instanceof Model);
            $newValues = $map[$pathName][$model->getKey()];

            // Iterate values
            foreach ($newValues as $propertyName => $fileIndex) {
                $this->line(
                    "Updating <info>{$propertyName}</> on <comment>{$classDisplay} #{$model->getKey()}</>...",
                    null,
                    OutputInterface::VERBOSITY_DEBUG,
                );

                // Prep a tempfle
                $tempFile = tempnam(sys_get_temp_dir(), 'image');

                // Write contents
                file_put_contents($tempFile, $zip->getFromIndex($fileIndex));

                // Check file
                $storagePath = Storage::disk('public')->writeFile("images/{$pathName}", new File($tempFile));
                if (! $storagePath) {
                    continue;
                }

                // Assign file
                $model->{$propertyName} = $storagePath;

                // Save it
                $model->save([$propertyName]);

                // Report
                $this->line(
                    "Updated <info>{$propertyName}</> on <comment>{$classDisplay} #{$model->getKey()}</>.",
                    null,
                    OutputInterface::VERBOSITY_VERBOSE,
                );
                $updateCount++;

                // Delete temp file
                unlink($tempFile);
            }
        }

        // Done
        $this->line(
            "Updated <info>{$updateCount}</> on <comment>{$classDisplay}</>.",
            null,
            OutputInterface::VERBOSITY_NORMAL,
        );
    }

    /**
     * Asks for the backup if none is specified.
     *
     * @return void
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // Check for msising arguments
        $fileName = $this->argument('file');
        if (! empty($fileName)) {
            return;
        }

        // Get arguments
        $files = Storage::files(BackupImagesCommand::BASE_PATH);

        // Only show zip files
        $zipFiles = [];
        foreach ($files as $file) {
            $file = basename($file);
            if (! preg_match('/^[a-z0-9_-]+\.zip$/', $file)) {
                continue;
            }

            $zipFiles[] = $file;
        }

        // Add zipfile
        if (empty($zipFiles)) {
            $this->warn('There are no backups available');

            return;
        }

        // Ask what
        $file = $this->choice('What backup to apply?', $zipFiles);

        // Save it
        if (! $file) {
            return;
        }

        $input->setArgument('file', $file);
    }
}
