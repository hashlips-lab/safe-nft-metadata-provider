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
use Aws\S3\S3Client;
use LogicException;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use SplFileInfo;
use Symfony\Component\HttpFoundation\File\File;

/**
 * @author Marco Lipparini <developer@liarco.net>
 */
final class S3FilesystemDriver implements CollectionFilesystemDriverInterface
{
    private readonly S3Client $s3Client;

    /**
     * @var int[]
     */
    private array $shuffleMapping = [];

    public function __construct(
        readonly string $region,
        readonly string $endpointUrl,
        readonly string $accessKey,
        readonly string $secretKey,
        private readonly string $bucketName,
        private readonly string $objectsKeyPrefix,
        private readonly string $assetsExtension,
        private readonly string $hiddenAssetExtension,
    ) {
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region' => $region,
            'endpoint' => $endpointUrl,
            'credentials' => [
                'key' => $accessKey,
                'secret' => $secretKey,
            ],
            'http' => [
                'connect_timeout' => 5,
                'timeout' => 10,
            ],
        ]);

        $this->s3Client->registerStreamWrapper();
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
        $metadataPath = $this->generateS3Path(self::METADATA_PATH.'/'.$tokenId.'.json');
        $metadata = Json::decode(FileSystem::read($metadataPath), Json::FORCE_ARRAY);

        if (! is_array($metadata)) {
            throw new LogicException('Unexpected metadata value (it must be an array).');
        }

        /** @var array<string, mixed> $metadata */

        return $metadata;
    }

    public function getAssetFileInfo(int $tokenId): SplFileInfo
    {
        return new File($this->generateS3Path(self::ASSETS_PATH.'/'.$tokenId.'.'.$this->assetsExtension));
    }

    /**
     * @inheritdoc
     */
    public function getHiddenMetadata(): array
    {
        $metadata = Json::decode(
            FileSystem::read($this->generateS3Path(self::HIDDEN_METADATA_PATH)),
            Json::FORCE_ARRAY,
        );

        if (! is_array($metadata)) {
            throw new LogicException('Unexpected metadata value (it must be an array).');
        }

        /** @var array<string, mixed> $metadata */

        return $metadata;
    }

    public function getHiddenAssetFileInfo(): SplFileInfo
    {
        return new File($this->generateS3Path(self::HIDDEN_ASSET_PATH.$this->hiddenAssetExtension));
    }

    /**
     * @return object[]
     */
    public function getAbi(): array
    {
        $abi = Json::decode(FileSystem::read($this->generateS3Path(self::ABI_PATH)));

        if (! is_array($abi)) {
            throw new LogicException('Unexpected ABI value (it must be an array).');
        }

        /** @var object[] $abi */

        return $abi;
    }

    public function getShuffleMapping(): ?array
    {
        if (empty($this->shuffleMapping)) {
            $mappingPath = $this->generateS3Path(self::MAPPING_PATH);

            if (! is_file($mappingPath)) {
                return null;
            }

            /** @var int[] $shuffleMappingData */
            $shuffleMappingData = Json::decode(FileSystem::read($mappingPath), Json::FORCE_ARRAY);

            $this->shuffleMapping = $shuffleMappingData;
        }

        return $this->shuffleMapping;
    }

    public function storeNewShuffleMapping(array $newShuffleMapping): void
    {
        FileSystem::write($this->generateS3Path(self::MAPPING_PATH), Json::encode($newShuffleMapping), null);
    }

    public function clearExportedMetadata(): void
    {
        FileSystem::delete($this->generateS3Path(self::EXPORTED_METADATA_PATH));
    }

    public function clearExportedAssets(): void
    {
        FileSystem::delete($this->generateS3Path(self::EXPORTED_ASSETS_PATH));
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function storeExportedMetadata(int $tokenId, array $metadata): void
    {
        FileSystem::write(
            $this->generateS3Path(self::EXPORTED_METADATA_PATH.'/'.$tokenId.'.json'),
            Json::encode($metadata, Json::PRETTY),
            null,
        );
    }

    public function storeExportedAsset(int $tokenId, SplFileInfo $originalAsset): void
    {
        FileSystem::copy(
            $originalAsset->getPathname(),
            $this->generateS3Path(self::EXPORTED_ASSETS_PATH.'/'.$tokenId.'.'.$this->assetsExtension),
        );
    }

    private function generateS3Path(string $relativePath): string
    {
        $keyPrefix = empty($this->objectsKeyPrefix) ? '' : trim($this->objectsKeyPrefix, '/').'/';

        return 's3://'.$this->bucketName.'/'.$keyPrefix.trim($relativePath, '/');
    }
}
