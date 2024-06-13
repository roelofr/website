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
use Illuminate\Support\Facades\Storage;
use SplFileInfo;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

class BackupImagesCommand extends Command
{
    public const BASE_PATH = 'backup/images/';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a backup of the images on activities, sponsors, pages and news';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Prep a temp file
        $tempname = tempnam(sys_get_temp_dir(), 'zipfile');

        // Out file
        $outFile = sprintf('backup-%s.zip', date('Y-m-d_H-i-s'));

        // Get zip handle
        $zip = new ZipArchive();
        $zip->open($tempname, ZipArchive::OVERWRITE);

        // Add archive comment
        $zip->setArchiveComment(sprintf(
            'Image backup generated on %s.',
            date('l, dS \o\f F Y \a\t H:is (T)'),
        ));

        // Get all activities
        $this->storeImages($zip, Activity::class, ['poster']);

        // Get all sponsors
        $this->storeImages($zip, Sponsor::class, ['canvas']);

        // Get all pages
        $this->storeImages($zip, Page::class, ['canvas']);

        // Get all news items
        $this->storeImages($zip, NewsItem::class, ['canvas']);

        // Save it
        $this->line('Saving...', null, OutputInterface::VERBOSITY_VERBOSE);
        $zip->close();

        // Store it
        if ($path = Storage::putFileAs(self::BASE_PATH, new SplFileInfo($tempname), $outFile)) {
            $this->line("Wrote backup as <info>{$path}</>.");
        }

        // Delete temp file
        unlink($tempname);
    }

    /**
     * Stores images on the $propeties on $className in $zip.
     */
    public function storeImages(ZipArchive &$zip, string $className, array $properties): void
    {
        // Prep name
        $baseName = ucwords(Str::snake(class_basename($className), ' '));
        $pathName = Str::snake($baseName, '-');

        // Log
        $this->line("Adding {$baseName} images", null, OutputInterface::VERBOSITY_VERY_VERBOSE);
        $count = 0;

        // Iterate all nodes
        foreach ($className::cursor() as $item) {
            assert($item instanceof Model);
            $itemId = $item->getKey();

            // Iterate requested properties
            foreach ($properties as $propertyName) {
                $path = $item->{$propertyName};

                // Skip if missing or empty
                if (empty($path) || ! Storage::disk('public')->exists($path)) {
                    $this->line(
                        "Skipping <info>{$propertyName}</> on <comment>{$baseName} #{$itemId}</>.",
                        null,
                        OutputInterface::VERBOSITY_DEBUG,
                    );

                    continue;
                }

                // Prep normal name and extension
                $attachmentNameParts = explode('.', $path);
                $attachmentExt = Str::lower(array_pop($attachmentNameParts));
                $attachmentName = Str::slug(implode('.', $attachmentNameParts));

                // Prep filename
                $filename = sprintf(
                    '%s/%s/%s/%s.%s',
                    $pathName,
                    $item->getKey(),
                    $propertyName,
                    $attachmentName,
                    $attachmentExt,
                );

                // Get storage
                $contents = Storage::disk('public')->get($path);

                // Write to zip
                $zip->addFromString($filename, $contents);
                $zip->setCompressionName($filename, ZipArchive::CM_STORE);

                // Add line
                $this->line(
                    "Wrote <info>{$propertyName}</> from <comment>{$baseName} #{$itemId}</> as <info>{$filename}</>.",
                    null,
                    OutputInterface::VERBOSITY_DEBUG,
                );

                // Raise count
                $count++;
            }
        }

        // Log result
        $this->line("Added <info>{$count}</> {$baseName} images", null, OutputInterface::VERBOSITY_VERBOSE);
    }
}
