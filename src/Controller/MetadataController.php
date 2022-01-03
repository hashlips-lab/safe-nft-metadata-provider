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

namespace App\Controller;

use App\Config\RouteName;
use App\Contract\AbstractNftController;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Marco Lipparini <developer@liarco.net>
 */
#[Route(
    '/metadata/{tokenId}.{_format}',
    name: RouteName::GET_METADATA,
    requirements: [
        '_format' => 'json',
    ],
)]
final class MetadataController extends AbstractNftController
{
    public function __invoke(string $tokenId): Response
    {
        if (! $this->isValidTokenId($tokenId)) {
            $hiddenMetadata = $this->cache->get('metadata_controller.hidden_metadata', function (): array {
                /** @var array<string, mixed> $hiddenMetadata */
                $hiddenMetadata = Json::decode(
                    FileSystem::read($this->collectionManager->getHiddenMetadataPath()),
                    Json::FORCE_ARRAY,
                );
                $hiddenMetadata['image'] = $this->urlGenerator->generate(
                    'hidden_asset',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                );

                return $hiddenMetadata;
            });

            return $this->json($hiddenMetadata)
                ->setPublic()
                ->setMaxAge($this->getDefaultCacheExpiration())
            ;
        }

        return $this->file(
            $this->collectionManager->getMetadataPath($tokenId),
            null,
            ResponseHeaderBag::DISPOSITION_INLINE,
        )
            ->setPublic()
            ->setMaxAge(self::YEAR_CACHE_EXPIRATION)
        ;
    }
}
