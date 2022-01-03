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

use App\Contract\MetadataUpdaterInterface;
use LogicException;
use Nette\Utils\FileSystem as NetteFilesystem;
use Nette\Utils\Json;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @author Marco Lipparini <developer@liarco.net>
 */
final class CollectionManager
{
    /**
     * @var string
     */
    private const COLLECTION_PATH = __DIR__.'/../../collection';

    /**
     * @var string
     */
    private const ASSETS_PATH = self::COLLECTION_PATH.'/assets';

    /**
     * @var string
     */
    private const METADATA_PATH = self::COLLECTION_PATH.'/metadata';

    /**
     * @var string
     */
    private const HIDDEN_PATH = self::COLLECTION_PATH.'/hidden';

    private int $maxTokenId;

    private string $assetsExtension;

    /**
     * @param iterable<MetadataUpdaterInterface> $metadataUpdaters
     */
    public function __construct(
        private readonly iterable $metadataUpdaters,
        private readonly Filesystem $filesystem,
        private readonly CacheInterface $cache,
    ) {
    }

    public function createCollectionFolder(): void
    {
        if ($this->filesystem->exists(self::COLLECTION_PATH)) {
            throw new RuntimeException('The collection folder already exists in "'.realpath(
                self::COLLECTION_PATH,
            ).'".');
        }

        $this->filesystem->mkdir(self::COLLECTION_PATH);
        $this->filesystem->mkdir(self::ASSETS_PATH);
        $this->filesystem->mkdir(self::METADATA_PATH);
        $this->filesystem->mkdir(self::HIDDEN_PATH);
    }

    public function getMaxTokenId(): int
    {
        if (! isset($this->maxTokenId)) {
            /** @var int $cachedMaxTokenId */
            $cachedMaxTokenId = $this->cache->get('collection_manager.max_token_id', function (): int {
                $maxTokenId = 1;
                $metadataFinder = (new Finder())->files()->in(self::METADATA_PATH)->name('*.json');

                foreach ($metadataFinder->getIterator() as $metadataFile) {
                    $id = (int) $metadataFile->getFilenameWithoutExtension();

                    if ($id > $maxTokenId) {
                        $maxTokenId = $id;
                    }
                }

                return $maxTokenId;
            });

            $this->maxTokenId = $cachedMaxTokenId;
        }

        return $this->maxTokenId;
    }

    public function temporarlyMoveToken(int $oldId, int $newId): void
    {
        $this->filesystem->rename($this->getMetadataPath($oldId), $this->getMetadataPath('_'.$newId));
        $this->filesystem->rename($this->getAssetPath($oldId), $this->getAssetPath('_'.$newId));
    }

    public function cleanTemporaryNames(): void
    {
        // Metadata
        $metadataFinder = (new Finder())->files()->in(self::METADATA_PATH)->name('_*.json');

        foreach ($metadataFinder->getIterator() as $metadataFile) {
            $oldFile = $metadataFile->getRealPath();
            $newFile = $metadataFile->getPath().'/'.substr($metadataFile->getFilename(), 1);

            if (! is_string($oldFile)) {
                throw new RuntimeException('Invalid old file path.');
            }

            $this->filesystem->rename($oldFile, $newFile);
        }

        // Assets
        $assetsFinder = (new Finder())->files()->in(self::ASSETS_PATH)->name('_*.'.$this->getAssetsExtension());

        foreach ($assetsFinder->getIterator() as $assetFile) {
            $oldFile = $assetFile->getRealPath();
            $newFile = $assetFile->getPath().'/'.substr($assetFile->getFilename(), 1);

            if (! is_string($oldFile)) {
                throw new RuntimeException('Invalid old file path.');
            }

            $this->filesystem->rename($oldFile, $newFile);
        }
    }

    public function updateUris(?string $uriPrefix): void
    {
        $metadataFinder = (new Finder())->files()->in(self::METADATA_PATH)->name('*.json');

        foreach ($metadataFinder->getIterator() as $metadataFile) {
            $jsonFilePath = $metadataFile->getRealPath();
            $tokenId = $metadataFile->getFilenameWithoutExtension();

            if (! is_string($jsonFilePath)) {
                throw new RuntimeException('Invalid JSON file path.');
            }

            /** @var array<string, mixed> $jsonContent */
            $jsonContent = Json::decode(NetteFilesystem::read($jsonFilePath), Json::FORCE_ARRAY);

            $jsonContent['image'] = $uriPrefix.'/'.$tokenId.'.'.$this->getAssetsExtension();

            NetteFilesystem::write($jsonFilePath, Json::encode($jsonContent, Json::PRETTY));
        }
    }

    public function updateMetadata(string $uriPrefix): void
    {
        $metadataFinder = (new Finder())->files()->in(self::METADATA_PATH)->name('*.json');

        foreach ($metadataFinder->getIterator() as $metadataFile) {
            $jsonFilePath = $metadataFile->getRealPath();
            $tokenId = $metadataFile->getFilenameWithoutExtension();

            if (! is_string($jsonFilePath)) {
                throw new RuntimeException('Invalid JSON file path.');
            }

            $jsonContent = Json::decode(NetteFilesystem::read($jsonFilePath), Json::FORCE_ARRAY);

            foreach ($this->metadataUpdaters as $metadataUpdater) {
                $metadataUpdater->updateMetadata($jsonContent, $uriPrefix, $tokenId, $this);
            }

            NetteFilesystem::write($jsonFilePath, Json::encode($jsonContent, Json::PRETTY));
        }
    }

    public function getMetadataPath(int|string $tokenId): string
    {
        return self::METADATA_PATH.'/'.$tokenId.'.json';
    }

    public function getAssetPath(int|string $tokenId): string
    {
        return self::ASSETS_PATH.'/'.$tokenId.'.'.$this->getAssetsExtension();
    }

    public function getHiddenFilePath(): string
    {
        $cachedHiddenFilePath = $this->cache->get('collection_manager.hidden_file_path', function (): string {
            $hiddenMetadataFinder = (new Finder())->files()->in(__DIR__.'/../../collection/hidden')->notName('*.json');

            foreach ($hiddenMetadataFinder->getIterator() as $hiddenMetadataFile) {
                $hiddenMetadataFilePath = $hiddenMetadataFile->getRealPath();

                if (! is_string($hiddenMetadataFilePath)) {
                    throw new RuntimeException('Invalid JSON file path (hidden metadata).');
                }

                return $hiddenMetadataFilePath;
            }

            throw new RuntimeException('Unable to detect "hidden" file.');
        });

        if (! is_string($cachedHiddenFilePath)) {
            throw new LogicException('Unexpected value type (this should never happen).');
        }

        return $cachedHiddenFilePath;
    }

    public function getHiddenMetadataPath(): string
    {
        return self::COLLECTION_PATH.'/hidden/hidden.json';
    }

    public function getAbi(): string
    {
        return NetteFilesystem::read(self::COLLECTION_PATH.'/abi.json');
    }

    public function getAssetsExtension(): string
    {
        if (! isset($this->assetsExtension)) {
            /** @var string $cachedAssetsExtension */
            $cachedAssetsExtension = $this->cache->get('collection_manager.assets_extension', function (): string {
                $assetsFinder = (new Finder())->files()->in(self::ASSETS_PATH);

                foreach ($assetsFinder->getIterator() as $hiddenMetadataFile) {
                    return $hiddenMetadataFile->getExtension();
                }

                throw new RuntimeException('Unable to detect assets extension.');
            });

            $this->assetsExtension = $cachedAssetsExtension;
        }

        return $this->assetsExtension;
    }
}
