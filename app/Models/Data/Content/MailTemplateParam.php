<?php

declare(strict_types=1);

namespace App\Models\Data\Content;

use App\Helpers\Str;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

class MailTemplateParam implements JsonSerializable, Stringable
{
    public readonly string $name;

    public readonly string $description;

    public static function fromArray(array $param): self
    {
        if (! array_key_exists('name', $param) || empty($param['name']) || ! is_string($param['name'])) {
            throw new InvalidArgumentException('Missing required parameters');
        }

        if (array_key_exists('description', $param) && (! is_string($param['description']) || empty($param['description']))) {
            throw new InvalidArgumentException('Description, when present, must be a non-empty string');
        }

        return new self(
            name: $param['name'],
            description: $param['description'] ?? $param['name'],
        );
    }

    /**
     * Makes a new MailTemplateParam, which will be replaced in the mail.
     */
    public function __construct(
        string $name,
        ?string $description,
    ) {
        throw_if(empty($name), InvalidArgumentException::class, 'Name must be a non-empty string');
        throw_unless(Str::slug($name, '_') === $name, InvalidArgumentException::class, "Name [{$name}] must look like a slug with underscores");

        $this->name = $name;

        if (empty($description)) {
            $this->description = $name;
        } else {
            throw_if(empty($description), InvalidArgumentException::class, 'Description must be a non-empty string');

            $this->description = $description;
        }
    }

    /**
     * Returns something that can be sent over API.
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
        ];
    }

    /**
     * Returns just the parameter name, as a logging helper.
     */
    public function __toString(): string
    {
        return $this->name;
    }
}
