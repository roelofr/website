<?php

declare(strict_types=1);

namespace App\Casts\Content;

use App\Models\Data\Content\MailTemplateParam;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Collection;
use JsonException;
use LogicException;
use RuntimeException;

class MailTemplateParams implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if (empty($value)) {
            return Collection::make();
        }

        if (is_string($value)) {
            try {
                $value = json_decode($value, true, 16, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw new RuntimeException(sprintf(
                    'Failed to parse JSON on %s:%s, attribute %s',
                    class_basename($model),
                    $model->getKey(),
                    $key,
                ));
            }
        }

        if (! is_array($value)) {
            throw new LogicException('Invalid mail template parameters stored in database!');
        }

        return array_map(fn ($row) => MailTemplateParam::fromArray($row), $value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        if (! is_iterable($value)) {
            throw new RuntimeException(sprintf(
                'Failed to cast %s to JSON on %s:%s, attribute %s',
                gettype($value),
                class_basename($model),
                $model->getKey(),
                $key,
            ));
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }
}
