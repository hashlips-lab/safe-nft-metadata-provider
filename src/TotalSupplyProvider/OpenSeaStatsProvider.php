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
    ) {
    }

    public function getTotalSupply(): int
    {
        if (! isset($this->totalSupply)) {
            $response = $this->httpClient->request(
                'GET',
                'https://api.opensea.io/api/v1/collection/'.$this->collectionSlug,
            );
            $jsonContent = $response->toArray();

            $this->totalSupply = (int) $jsonContent['collection']['stats']['total_supply'];
        }

        return $this->totalSupply;
    }
}
