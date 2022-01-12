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

use App\Service\CollectionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @author Marco Lipparini <developer@liarco.net>
 */
abstract class AbstractNftController extends AbstractController
{
    public function __construct(
        protected CollectionManager $collectionManager,
        protected TotalSupplyProviderInterface $totalSupplyProvider,
        protected UrlGeneratorInterface $urlGenerator,
        protected CacheInterface $cache,
    ) {
    }

    protected function isValidTokenId(int $tokenId): bool
    {
        return $tokenId > 0 && $tokenId <= $this->totalSupplyProvider->getTotalSupply();
    }

    protected function getDefaultCacheExpiration(): int
    {
        return (int) $this->getParameter('app.cache_expiration');
    }
}
