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

namespace App\Contract;

use SplFileInfo;

/**
 * @author Marco Lipparini <developer@liarco.net>
 */
interface CollectionFilesystemDriverInterface
{
    /**
     * @var string
     */
    final public const ASSETS_PATH = '/assets';

    /**
     * @var string
     */
    final public const METADATA_PATH = '/metadata';

    /**
     * @var string
     */
    final public const HIDDEN_METADATA_PATH = '/hidden/hidden.json';

    /**
     * @var string
     */
    final public const HIDDEN_ASSET_PATH = '/hidden/hidden.';

    /**
     * @var string
     */
    final public const ABI_PATH = '/abi.json';

    /**
     * @var string
     */
    final public const MAPPING_PATH = '/mapping.json';

    public function getAssetsExtension(): string;

    public function getHiddenAssetExtension(): string;

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(int $tokenId): array;

    public function getAssetFileInfo(int $tokenId): SplFileInfo;

    /**
     * @return array<string, mixed>
     */
    public function getHiddenMetadata(): array;

    public function getHiddenAssetFileInfo(): SplFileInfo;

    public function getAbi(): string;

    /**
     * @return null|int[]
     */
    public function getShuffleMapping(): ?array;

    /**
     * @param int[] $newShuffleMapping
     */
    public function storeNewShuffleMapping(array $newShuffleMapping): void;
}
