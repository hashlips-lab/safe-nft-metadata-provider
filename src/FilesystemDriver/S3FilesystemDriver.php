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
use App\FilesystemDriver\S3\FileObject;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Stream;
use LogicException;
use Nette\Utils\Json;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Marco Lipparini <developer@liarco.net>
 */
final class S3FilesystemDriver implements CollectionFilesystemDriverInterface
{
    /**
     * @var string
     */
    private const KEY_NOT_FOUND_ERROR_CODE = 'NoSuchKey';

    private readonly S3Client $s3Client;

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
        $metadata = Json::decode(
            $this->getObject(self::METADATA_PATH.'/'.$tokenId.'.json')->contents,
            Json::FORCE_ARRAY,
        );

        if (! is_array($metadata)) {
            throw new LogicException('Unexpected metadata value (it must be an array).');
        }

        /** @var array<string, mixed> $metadata */

        return $metadata;
    }

    public function getAssetResponse(int $tokenId): Response
    {
        $object = $this->getObject(self::ASSETS_PATH.'/'.$tokenId.'.'.$this->assetsExtension);
        $response = new Response(
            $object->contents,
            200,
            [
                'Content-Type' => $object->contentType,
            ],
        );
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_INLINE,
            $tokenId.'.'.$this->assetsExtension,
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function getHiddenMetadata(): array
    {
        $metadata = Json::decode($this->getObject(self::HIDDEN_METADATA_PATH)->contents, Json::FORCE_ARRAY);

        if (! is_array($metadata)) {
            throw new LogicException('Unexpected metadata value (it must be an array).');
        }

        /** @var array<string, mixed> $metadata */

        return $metadata;
    }

    public function getHiddenAssetResponse(): Response
    {
        $object = $this->getObject(self::HIDDEN_ASSET_PATH.$this->hiddenAssetExtension);
        $response = new Response(
            $object->contents,
            200,
            [
                'Content-Type' => $object->contentType,
            ],
        );
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_INLINE,
            'hidden.'.$this->hiddenAssetExtension,
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * @return object[]
     */
    public function getAbi(): array
    {
        $abi = Json::decode($this->getObject(self::ABI_PATH)->contents);

        if (! is_array($abi)) {
            throw new LogicException('Unexpected ABI value (it must be an array).');
        }

        /** @var object[] $abi */

        return $abi;
    }

    public function getShuffleMapping(): ?array
    {
        try {
            $shuffleMapping = Json::decode($this->getObject(self::MAPPING_PATH)->contents, Json::FORCE_ARRAY);

            if (! is_array($shuffleMapping)) {
                throw new LogicException('Unexpected shuffle mapping value (it must be an array).');
            }

            /** @var int[] $shuffleMapping */

            return $shuffleMapping;
        } catch (S3Exception $s3Exception) {
            if (self::KEY_NOT_FOUND_ERROR_CODE === $s3Exception->getAwsErrorCode()) {
                return null;
            }

            throw $s3Exception;
        }
    }

    public function storeNewShuffleMapping(array $newShuffleMapping): void
    {
        $this->putObject(self::MAPPING_PATH, Json::encode($newShuffleMapping));
    }

    public function clearExportedMetadata(): void
    {
        $this->s3Client->deleteMatchingObjects($this->bucketName, trim(self::EXPORTED_METADATA_PATH, '/'));
    }

    public function clearExportedAssets(): void
    {
        $this->s3Client->deleteMatchingObjects($this->bucketName, trim(self::EXPORTED_ASSETS_PATH, '/'));
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function storeExportedMetadata(int $tokenId, array $metadata): void
    {
        $this->putObject(self::EXPORTED_METADATA_PATH.'/'.$tokenId.'.json', Json::encode($metadata, Json::PRETTY));
    }

    public function storeExportedAsset(int $sourceTokenId, int $targetTokenId): void
    {
        $this->copyObject(
            self::ASSETS_PATH.'/'.$sourceTokenId.'.'.$this->assetsExtension,
            self::EXPORTED_ASSETS_PATH.'/'.$targetTokenId.'.'.$this->assetsExtension,
        );
    }

    private function getObject(string $relativePath): FileObject
    {
        $result = $this->s3Client->getObject($this->generateArgs($relativePath));

        $body = $result['Body'];
        $contentType = $result['ContentType'];

        if (! $body instanceof Stream) {
            throw new LogicException('Unexpected "Body"" type, it should be a "GuzzleHttp\Psr7\Stream".');
        }

        if (! is_string($contentType)) {
            throw new LogicException('Unexpected "ContentType" type, it should be a string.');
        }

        return new FileObject($contentType, $body->getContents());
    }

    private function putObject(string $relativePath, string $contents): void
    {
        $args = $this->generateArgs($relativePath);

        $args['Body'] = $contents;

        $this->s3Client->putObject($args);
    }

    private function copyObject(string $sourceRelativePath, string $targetRelativePath): void
    {
        $args = $this->generateArgs($targetRelativePath);

        $args['CopySource'] = $this->bucketName.'/'.$this->generateAbsolutePath($sourceRelativePath);

        $this->s3Client->copyObject($args);
    }

    /**
     * @return array<string, string>
     */
    private function generateArgs(string $relativePath): array
    {
        return [
            'Bucket' => $this->bucketName,
            'Key' => $this->generateAbsolutePath($relativePath),
        ];
    }

    private function generateAbsolutePath(string $relativePath): string
    {
        $keyPrefix = empty($this->objectsKeyPrefix) ? '' : trim($this->objectsKeyPrefix, '/').'/';

        return trim($keyPrefix.trim($relativePath, '/'), '/');
    }
}
