<?php

declare(strict_types=1);

namespace App\Bots\Commands;

use App\Helpers\Str;
use App\Models\Webcam\Camera;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Telegram\Bot\Actions;
use Telegram\Bot\FileUpload\InputFile;

/**
 * @codeCoverageIgnore
 */
class WebcamCommand extends Command
{
    private const REPLY_GUEST = <<<'MSG'
    🔒 Deze camera is alleen toegankelijk voor leden.

    Log in via /login.
    MSG;

    private const REPLY_NOT_AVAILABLE = <<<'MSG'
    😕 Deze foto is niet (meer) beschikbaar

    De opgevraagde webcam is momenteel niet beschikbaar,
    De foto is mogelijk verouderd.
    MSG;

    private const REPLY_NO_SUCH_CAMERA = <<<'MSG'
    🔒 Deze camera is niet beschikbaar.

    De opgevraagde camera kon niet worden gevonden.
    MSG;

    private const REPLY_FILE_LOST = <<<'MSG'
    De-… de foto is zoek 🥺

    Sorry, er moet een recente foto van deze camera zijn,
    maar hij lijkt niet meer te bestaan.
    MSG;

    protected ?Collection $cams = null;

    /**
     * The name of the Telegram command.
     *
     * @var string
     */
    protected $name = 'plazacam';

    /**
     * The Telegram command description.
     *
     * @var string
     */
    protected $description = 'Toont de plaza of koffiecam';

    /**
     * Get Command Aliases.
     *
     * Helpful when you want to trigger command with more than one name.
     */
    public function getAliases(): array
    {
        $cameras = $this->getCameras()
            ->where('slug', '!=', $this->getName());

        return Collection::make()
            ->merge($cameras->pluck('slug'))
            ->merge($cameras->pluck('command'))
            ->map(fn ($value) => Str::slug($value))
            ->reject(fn ($value) => $value === $this->getName() || ! Str::endsWith($value, 'cam'))
            ->unique()
            ->toArray();
    }

    public function getDescriptionFor(string $command): string
    {
        $targetCamera = $this->getCameras()
            ->filter(fn ($row) => Str::slug($row->command) === $command || Str::slug($row->slug) === $command)
            ->first();

        return $targetCamera ? "Toont de {$targetCamera->name}" : $this->description;
    }

    /**
     * Handle the activity.
     */
    public function handle()
    {
        // Get user
        $user = $this->getUser();

        // Reject if rate-limited
        if (! $user) {
            $this->replyWithMessage(['text' => self::REPLY_GUEST]);

            return;
        }

        // Get image
        $requested = Str::slug($this->getMessageCommand() ?? $this->getName());
        $webcam = Camera::query()
            ->where(function (Builder $query) use ($requested) {
                $query
                    ->where('slug', $requested)
                    ->orWhere('command', $requested);
            })
            ->first();

        if (! $webcam) {
            $this->replyWithMessage([
                'text' => $this->formatText(self::REPLY_NO_SUCH_CAMERA, e(strip_tags($requested))),
            ]);

            return;
        }

        // Check if expired
        if ($webcam->is_expired || $webcam->device?->path === null) {
            $this->replyWithMessage([
                'text' => $this->formatText(self::REPLY_NOT_AVAILABLE, $webcam->name),
            ]);

            return;
        }

        $device = $webcam->device;
        $disk = Storage::disk(Config::get('gumbo.images.disk'));

        // Send upload status
        $this->replyWithChatAction(['action' => Actions::UPLOAD_PHOTO]);

        // Check if image exists
        if ($disk->missing($device->path)) {
            $this->replyWithMessage([
                'text' => $this->formatText(self::REPLY_FILE_LOST),
            ]);

            report(new RuntimeException(
                "Failed to retrieve photo file [{$device->path}] for webcam [{$webcam->id}] (device [{$device->id}]!",
            ));

            return;
        }

        // Prep file
        $file = new InputFile(
            $disk->readStream($device->path),
            (string) Str::of("{$webcam->slug}.jpg")->ascii()->lower(),
        );

        // Return message
        $this->replyWithPhoto([
            'photo' => $file,
            'caption' => sprintf('%s van %s', $webcam->name, $device->updated_at->isoFormat('ddd D MMM YYYY, HH:mm (z)')),
        ]);
    }

    protected function getCameras(): Collection
    {
        return $this->cams ??= Camera::all();
    }
}
