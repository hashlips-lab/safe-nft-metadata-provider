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

namespace App\TotalSupplyProvider;

use App\Contract\TotalSupplyProviderInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Marco Lipparini <developer@liarco.net>
 */
final class OpenSeaStatsProvider implements TotalSupplyProviderInterface
{
    private int $totalSupply;

    public function __construct(
        private readonly string $collectionSlug,
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly ParameterBagInterface $parameterBag,
    ) {
    }

    public function getTotalSupply(): int
    {
        if (! isset($this->totalSupply)) {
            /** @var int $cachedTotalSupply */
            $cachedTotalSupply = $this->cache->get(
                'open_sea_provider.total_supply',
                function (ItemInterface $item): int {
                    $item->expiresAfter((int) $this->parameterBag->get('app.cache_expiration'));

                    $response = $this->httpClient->request(
                        'GET',
                        'https://api.opensea.io/api/v1/collection/'.$this->collectionSlug,
                    );
                    $jsonContent = $response->toArray();

                    return (int) $jsonContent['collection']['stats']['total_supply'];
                },
            );

            $this->totalSupply = $cachedTotalSupply;
        }

        return $this->totalSupply;
    }
}
