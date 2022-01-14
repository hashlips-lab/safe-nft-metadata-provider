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

namespace App\Service;

use App\Config\RouteName;
use App\Contract\CollectionFilesystemDriverInterface;
use App\Contract\MetadataUpdaterInterface;
use App\Exception\InvalidTokenIdException;
use App\Exception\InvalidTokensRangeException;
use LogicException;
use SplFileInfo;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @author Marco Lipparini <developer@liarco.net>
 */
final class CollectionManager
{
    /**
     * @var string
     */
    private const CACHE_MAPPING = 'collection_manager.mapping';

    /**
     * @param iterable<MetadataUpdaterInterface> $metadataUpdaters
     */
    public function __construct(
        private readonly int $maxTokenId,
        private readonly iterable $metadataUpdaters,
        private readonly CollectionFilesystemDriverInterface $collectionFilesystemDriver,
        private readonly CacheInterface $cache,
        private readonly ParameterBagInterface $parameterBag,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getMaxTokenId(): int
    {
        return $this->maxTokenId;
    }

    public function shuffle(int $min, int $max): void
    {
        if ($min <= 0 || $min >= $max || $max > $this->maxTokenId) {
            throw new InvalidTokensRangeException($min, $max);
        }

        $tokenIds = range(1, $this->maxTokenId);

        $beforeRange = array_slice($tokenIds, 0, $min - 1);
        $range = array_slice($tokenIds, $min - 1, $max - ($min - 1));
        $afterRange = array_slice($tokenIds, $max);
        shuffle($range);

        $this->collectionFilesystemDriver->storeNewShuffleMapping(array_merge($beforeRange, $range, $afterRange));

        $this->cache->delete(self::CACHE_MAPPING);
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(int $tokenId, string $assetUri = null): array
    {
        $metadata = $this->collectionFilesystemDriver->getMetadata($this->getMappedTokenId($tokenId));

        foreach ($this->metadataUpdaters as $metadataUpdater) {
            $metadataUpdater->updateMetadata(
                $metadata,
                $tokenId,
                $assetUri ?? $this->urlGenerator->generate(
                    RouteName::GET_ASSET,
                    [
                        'tokenId' => $tokenId,
                        '_format' => $this->collectionFilesystemDriver->getAssetsExtension(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ),
            );
        }

        return $metadata;
    }

    public function getAssetFileInfo(int $tokenId): SplFileInfo
    {
        return $this->collectionFilesystemDriver->getAssetFileInfo($this->getMappedTokenId($tokenId));
    }

    /**
     * @return array<string, mixed>
     */
    public function getHiddenMetadata(string $assetUri = null): array
    {
        $hiddenMetadata = $this->collectionFilesystemDriver->getHiddenMetadata();

        $hiddenMetadata['image'] = $assetUri ?? $this->urlGenerator->generate(
            RouteName::GET_HIDDEN_ASSET,
            [
                '_format' => $this->collectionFilesystemDriver->getHiddenAssetExtension(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return $hiddenMetadata;
    }

    public function getHiddenAssetFileInfo(): SplFileInfo
    {
        return $this->collectionFilesystemDriver->getHiddenAssetFileInfo();
    }

    /**
     * @return object[]
     */
    public function getAbi(): array
    {
        return $this->collectionFilesystemDriver->getAbi();
    }

    /**
     * @return null|int[]
     */
    public function getShuffleMapping(): ?array
    {
        $shuffleMapping = $this->cache->get(self::CACHE_MAPPING, function (ItemInterface $item): ?array {
            $item->expiresAfter((int) $this->parameterBag->get('app.cache_expiration'));

            return $this->collectionFilesystemDriver->getShuffleMapping();
        });

        if (! is_array($shuffleMapping) && null !== $shuffleMapping) {
            throw new LogicException('Unexpected cache value (it must be an array or null).');
        }

        return $shuffleMapping;
    }

    public function clearShuffledMetadata(): void
    {
        $this->collectionFilesystemDriver->clearShuffledMetadata();
    }

    public function clearShuffledAssets(): void
    {
        $this->collectionFilesystemDriver->clearShuffledAssets();
    }

    public function storeShuffledMetadata(int $tokenId, string $uriPrefix): void
    {
        $this->collectionFilesystemDriver->storeShuffledMetadata(
            $tokenId,
            $this->getMetadata($tokenId, $uriPrefix.'/'.$tokenId.'.json'),
        );
    }

    public function storeShuffledAsset(int $tokenId): void
    {
        $this->collectionFilesystemDriver->storeShuffledAsset($tokenId, $this->getAssetFileInfo($tokenId));
    }

    private function getMappedTokenId(int $tokenId): int
    {
        $shuffleMapping = $this->collectionFilesystemDriver->getShuffleMapping();

        if (null === $shuffleMapping) {
            return $tokenId;
        }

        if (! isset($shuffleMapping[$tokenId - 1])) {
            throw new InvalidTokenIdException($tokenId);
        }

        return $shuffleMapping[$tokenId - 1];
    }
}
