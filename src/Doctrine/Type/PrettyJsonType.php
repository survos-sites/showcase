<?php

declare(strict_types=1);

namespace App\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Types\Exception\SerializationFailed;
use JsonException;

// JsonType's own encoding escapes "/" (json_encode's default), which makes stored
// composer.json blobs unreadable and breaks naive LIKE '%survos/foo-bundle%' lookups.
// This keeps slashes and unicode literal, matching how composer.json is written on disk.
final class PrettyJsonType extends JsonType
{
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        } catch (JsonException $e) {
            throw SerializationFailed::new($value, 'json', $e->getMessage(), $e);
        }
    }
}
