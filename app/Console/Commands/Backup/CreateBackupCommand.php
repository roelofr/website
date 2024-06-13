<?php

declare(strict_types=1);

namespace App\Console\Commands\Backup;

use App\Enums\BackupType;
use App\Helpers\Str;
use App\Models;
use App\Models\Backup;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media as MediaLibraryMediaModel;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class CreateBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = <<<'CMD'
            backup:create
                {--full : Create a full backup}
        CMD;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a backup of all models and associated media assets.';

    /**
     * The backup we're working on.
     */
    private Backup $backup;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (! Config::get('gumbo.backups.enabled')) {
            $this->error('Backups are disabled.');

            return Command::INVALID;
        }

        $isFullBackup = $this->option('full');

        // Create configured backup
        $backup = Backup::make([
            'type' => $isFullBackup ? BackupType::Full : BackupType::Incremental,
        ]);

        // Find the previous backup
        $previousBackup = Backup::query()
            ->when($isFullBackup, fn ($query) => $query->where('type', BackupType::Full))
            ->latest()
            ->first();

        // Associate the previous backup, or ensure we're making a full one
        if ($previousBackup) {
            $backup->previous()->associate($previousBackup);
        } elseif (! $isFullBackup) {
            $backup->type = BackupType::Full;
            $this->warn('No previous full backup found. Creating a full backup instead');
        }

        // Actually create the backup
        $backup->save();

        // Assign locally
        $this->backup = $backup;

        try {
            $this->createBackup();

            $backup->completed_at = Date::now();
            $backup->save();

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $backup->failed_at = Date::now();
            $backup->failed_reason = Str::limit($exception->getMessage(), 200);
            $backup->save();

            return Command::FAILURE;
        }
    }

    /**
     * Actually create the backup.
     */
    protected function createBackup(): void
    {
        $this->info('Creating backup...');

        // Determine image disk now, it's commonly used
        $imageDisk = Config::get('gumbo.images.disk');

        // Create a backup of basic models
        $this->createBackupForResource(Models\Activity::class, [
            'poster' => $imageDisk,
        ]);
        $this->createBackupForResource(Models\Page::class, [
            'cover' => $imageDisk,
        ]);
        $this->createBackupForResource(Models\NewsItem::class, [
            'cover' => $imageDisk,
        ]);

        // Backup document system
        $this->createBackupForResource(Models\FileBundle::class);
        $this->createBackupForResource(Models\Media::class);

        // Backup gallery
        $this->createBackupForResource(Models\Gallery\Album::class);
        $this->createBackupForResource(Models\Gallery\Photo::class, [
            'path' => $imageDisk,
        ]);
    }

    /**
     * Creates a backup for the given model, and all assets specified in the $assets property.
     *
     * @param string $model Model class to backup
     * @param string[] $assets Array of assets to backup, in format 'property' => 'disk'
     * @return int Number of assets backed up
     */
    protected function createBackupForResource(string $model, array $assets = []): int
    {
        $cursor = $this->createQueryForModel($model)->cursor();
        $backupCount = 0;

        /** @var Model $modelInstance */
        foreach ($cursor as $modelInstance) {
            [$path, $logPrefix] = $this->getBackupPropertiesFor($modelInstance);

            // Determine data
            $modelData = $modelInstance->toArray();

            // Report for debug
            $this->line("{$logPrefix} <fg=gray>Writing model data</>", null, OutputInterface::VERBOSITY_DEBUG);

            // Write model data
            $this->write(
                "{$path}/model.json",
                json_encode($modelData, JSON_PRETTY_PRINT),
            );

            // Report for verbose
            $this->line("{$logPrefix} <info>Wrote model data</>", null, OutputInterface::VERBOSITY_VERY_VERBOSE);

            // Handle media objects
            if ($modelInstance instanceof MediaLibraryMediaModel) {
                // Report for debug
                $this->line("{$logPrefix} <fg=gray>Writing model media</>", null, OutputInterface::VERBOSITY_DEBUG);

                // Find media data
                $this->createBackupForResourceProperty(
                    $modelInstance,
                    'media',
                    $modelInstance->getPath(),
                    $modelInstance->disk ?? Config::get('media-library.disk_name'),
                );

                // Report for verbose
                $this->line("{$logPrefix} <info>Wrote model media</>", null, OutputInterface::VERBOSITY_VERY_VERBOSE);
            }

            // Write all assets, if present
            foreach ($assets as $asset => $assetDisk) {
                $assetValue = $modelInstance->getAttribute($asset);

                // Report for debug
                $this->line("{$logPrefix} <fg=gray>Writing model property [{$asset}]</>", null, OutputInterface::VERBOSITY_DEBUG);

                $this->createBackupForResourceProperty(
                    $modelInstance,
                    $asset,
                    $assetValue,
                    $assetDisk,
                );

                // Report for verbose
                $this->line("{$logPrefix} <info>Wrote model property [{$asset}]</>", null, OutputInterface::VERBOSITY_VERY_VERBOSE);
            }

            $this->line("{$logPrefix} <info>Completed backup</>", null, OutputInterface::VERBOSITY_VERBOSE);

            $backupCount++;
        }

        [1 => $logPrefix] = $this->getBackupPropertiesFor($model);
        $this->line("{$logPrefix} <info>Completed backup of {$backupCount} items</>", null, OutputInterface::VERBOSITY_VERBOSE);

        return $backupCount;
    }

    protected function getBackupPropertiesFor(Model|string $model): array
    {
        $modelName = Str::of(class_basename($model))->snake()->plural();
        if (is_string($model)) {
            return ["models/{$modelName}", "[{$modelName}]"];
        }

        $modelKey = $model->getKey();

        return ["models/{$modelName}/{$modelKey}", "[{$modelName}][{$modelKey}]"];
    }

    protected function createBackupForResourceProperty(Model $model, string $asset, mixed $path, string $disk): bool
    {
        [$targetPath, $logPrefix] = $this->getBackupPropertiesFor($model);

        // Check if empty
        if (empty($path)) {
            $this->line("{$logPrefix} <fg=gray>Skipping empty asset [{$asset}]</>", null, OutputInterface::VERBOSITY_DEBUG);

            return true;
        }

        // Check if found
        if (! Storage::disk($disk)->exists($path)) {
            $this->line("{$logPrefix} <error>Failed to write [{$asset}]: no such file named \"{$path}\" on disk [{$disk}]</>");

            // Try local disk if it's not currently on that
            if ($disk !== null && $disk !== 'local') {
                $this->line("{$logPrefix} <fg=gray>Trying local disk for [{$asset}]</>");

                return $this->createBackupForResourceProperty($model, $asset, $path, 'local');
            }

            return false;
        }

        // Write asset by using a read stream from the other disk
        $this->write(
            sprintf('%s/%s/%s', $targetPath, $asset, basename($path)),
            fn () => Storage::disk($disk)->readStream($path),
        );

        // Report and return
        $this->line("{$logPrefix} <info>Wrote asset [{$asset}]</>", null, OutputInterface::VERBOSITY_VERY_VERBOSE);

        return true;
    }

    protected function createQueryForModel(string $model): Builder
    {
        throw_unless(is_a($model, Model::class, true), new RuntimeException('The model must be an Eloquent model type.'));

        $query = $model::withoutGlobalScopes();

        if ($this->backup->type === BackupType::Full) {
            return $query;
        }

        /** @var Model $modelInstance */
        $modelInstance = new $model();

        // Prevent any results from the query
        if (! $modelInstance->usesTimestamps()) {
            return $query->whereNull($modelInstance->getKeyName());
        }

        // Find the last backup before this one
        $lastBackupDate = Backup::query()->where([
            ['id', '!=', $this->backup->id],
        ])->latest()->first()->created_at;

        // No backups have been made yet, create a full backup instead
        if ($lastBackupDate === null) {
            return $query;
        }

        // Find all models modified after the last backup
        return $query->where(fn ($query) => $query->orWhere([
            [$modelInstance->getCreatedAtColumn(), '>', $lastBackupDate],
            [$modelInstance->getUpdatedAtColumn(), '>', $lastBackupDate],
        ]));
    }

    /**
     * Writes contents to the backup.
     * @param mixed|resource|string $data
     * @return bool
     * @throws InvalidArgumentException
     * @throws Exception
     */
    protected function write(string $path, $data): void
    {
        $this->info("Writing file [<info>{$path}</>]", null, OutputInterface::VERBOSITY_DEBUG);

        try {
            Storage::disk($this->backup->disk)->write("{$this->backup->path}/{$path}", value($data));
        } catch (Exception $exception) {
            /** @var Exception $exception */
            $this->line("<error>Failed to write file [{$path}]</>: {$exception->getMessage()}");
        }
    }
}
