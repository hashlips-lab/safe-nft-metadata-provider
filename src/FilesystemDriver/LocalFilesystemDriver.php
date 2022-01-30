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

namespace App\FilesystemDriver;

use App\Contract\CollectionFilesystemDriverInterface;
use LogicException;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Marco Lipparini <developer@liarco.net>
 */
final class LocalFilesystemDriver implements CollectionFilesystemDriverInterface
{
    public function __construct(
        private readonly string $localCollectionPath,
        private readonly string $assetsExtension,
        private readonly string $hiddenAssetExtension,
    ) {
    }

    public function getAssetsExtension(): string
    {
        return $this->assetsExtension;
    }

    public function getHiddenAssetExtension(): string
    {
        return $this->hiddenAssetExtension;
    }

    /**
     * @inheritdoc
     */
    public function getMetadata(int $tokenId): array
    {
        $metadataPath = $this->localCollectionPath.self::METADATA_PATH.'/'.$tokenId.'.json';
        $metadata = Json::decode(FileSystem::read($metadataPath), Json::FORCE_ARRAY);

        if (! is_array($metadata)) {
            throw new LogicException('Unexpected metadata value (it must be an array).');
        }

        /** @var array<string, mixed> $metadata */

        return $metadata;
    }

    public function getAssetResponse(int $tokenId): Response
    {
        $binaryFileResponse = new BinaryFileResponse(
            $this->localCollectionPath.self::ASSETS_PATH.'/'.$tokenId.'.'.$this->assetsExtension,
        );
        $binaryFileResponse->setContentDisposition(
            HeaderUtils::DISPOSITION_INLINE,
            $tokenId.'.'.$this->assetsExtension,
        );

        return $binaryFileResponse;
    }

    /**
     * @inheritdoc
     */
    public function getHiddenMetadata(): array
    {
        $hiddenMetadataPath = $this->localCollectionPath.self::HIDDEN_METADATA_PATH;
        $metadata = Json::decode(FileSystem::read($hiddenMetadataPath), Json::FORCE_ARRAY);

        if (! is_array($metadata)) {
            throw new LogicException('Unexpected metadata value (it must be an array).');
        }

        /** @var array<string, mixed> $metadata */

        return $metadata;
    }

    public function getHiddenAssetResponse(): Response
    {
        $binaryFileResponse = new BinaryFileResponse(
            $this->localCollectionPath.self::HIDDEN_ASSET_PATH.$this->hiddenAssetExtension,
        );
        $binaryFileResponse->setContentDisposition(
            HeaderUtils::DISPOSITION_INLINE,
            'hidden.'.$this->assetsExtension,
        );

        return $binaryFileResponse;
    }

    /**
     * @return object[]
     */
    public function getAbi(): array
    {
        $abi = Json::decode(FileSystem::read($this->localCollectionPath.self::ABI_PATH));

        if (! is_array($abi)) {
            throw new LogicException('Unexpected ABI value (it must be an array).');
        }

        /** @var object[] $abi */

        return $abi;
    }

    public function getShuffleMapping(): ?array
    {
        $mappingPath = $this->localCollectionPath.self::MAPPING_PATH;

        if (! is_file($mappingPath)) {
            return null;
        }

        $shuffleMapping = Json::decode(FileSystem::read($mappingPath), Json::FORCE_ARRAY);

        if (! is_array($shuffleMapping)) {
            throw new LogicException('Unexpected shuffle mapping value (it must be an array).');
        }

        /** @var int[] $shuffleMapping */

        return $shuffleMapping;
    }

    public function storeNewShuffleMapping(array $newShuffleMapping): void
    {
        FileSystem::write($this->localCollectionPath.self::MAPPING_PATH, Json::encode($newShuffleMapping));
    }

    public function clearExportedMetadata(): void
    {
        FileSystem::delete($this->localCollectionPath.self::EXPORTED_METADATA_PATH);
    }

    public function clearExportedAssets(): void
    {
        FileSystem::delete($this->localCollectionPath.self::EXPORTED_ASSETS_PATH);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function storeExportedMetadata(int $tokenId, array $metadata): void
    {
        FileSystem::write(
            $this->localCollectionPath.self::EXPORTED_METADATA_PATH.'/'.$tokenId.'.json',
            Json::encode($metadata, Json::PRETTY),
            null,
        );
    }

    public function storeExportedAsset(int $sourceTokenId, int $targetTokenId): void
    {
        FileSystem::copy(
            $this->localCollectionPath.self::ASSETS_PATH.'/'.$sourceTokenId.'.'.$this->assetsExtension,
            $this->localCollectionPath.self::EXPORTED_ASSETS_PATH.'/'.$targetTokenId.'.'.$this->assetsExtension,
        );
    }
}
