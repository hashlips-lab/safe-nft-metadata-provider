<?php

/*
 * This file is part of the Safe NFT Metadata Provider package.
 *
 * (c) Marco Lipparini <developer@liarco.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\MetadataUpdater;

use App\Contract\MetadataUpdaterInterface;
use RuntimeException;

/**
 * This metadata updater replaces each metadata key with the values found inside the given JSON template.
 * Any key which is not found in the template is left as it is.
 *
 * Each template value also supports the replacement of the following placeholders:
 * - {TOKEN_ID}
 * - {INT_TOKEN_ID} (a value matching this string exactly will be replaced with the token ID as an integer value)
 * - {ASSET_URI} (please remember that the "image" key is already replaced by default!)
 *
 * Limitations: this updater supports first-level keys only.
 *
 * Template example:
 * {
 *   "name": "My awesome token #{TOKEN_ID}"
 * }
 *
 * @author Marco Lipparini <developer@liarco.net>
 */
final class TemplatedMetadataUpdater implements MetadataUpdaterInterface
{
    /**
     * @var string
     */
    private const TOKEN_ID_PLACEHOLDER = '{TOKEN_ID}';

    /**
     * @var string
     */
    private const INT_TOKEN_ID_PLACEHOLDER = '{INT_TOKEN_ID}';

    /**
     * @var string
     */
    private const ASSET_URI_PLACEHOLDER = '{ASSET_URI}';

    /**
     * @param array<string, mixed> $template
     */
    public function __construct(
        private readonly ?array $template,
    ) {
    }

    public function updateMetadata(array &$metadata, int $tokenId, string $assetUri): void
    {
        if (null === $this->template) {
            return;
        }

        foreach ($this->template as $key => $value) {
            if (is_array($value) || (isset($metadata[$key]) && is_array($metadata[$key]))) {
                throw new RuntimeException('Deep level replacement is not supported in METADATA_TEMPLATE.');
            }

            $metadata[$key] = $this->replacePlaceholders($value, $tokenId, $assetUri);
        }
    }

    private function replacePlaceholders(mixed $value, int $tokenId, string $assetUri): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        if (self::INT_TOKEN_ID_PLACEHOLDER === $value) {
            return $tokenId;
        }

        return str_replace(
            [self::TOKEN_ID_PLACEHOLDER, self::ASSET_URI_PLACEHOLDER],
            [(string) $tokenId, $assetUri],
            $value,
        );
    }
}
